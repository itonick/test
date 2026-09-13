<?php
declare(strict_types=1);

/**
 * CSRF対策
 * session_start() の後に読み込むこと。
 */

/**
 * CSRFトークンを取得する（無ければ生成）
 */
function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException("セッションが開始されていません");
    }

    if (empty($_SESSION["_csrf_token"])) {
        // random_bytes は暗号学的に安全な乱数。rand() は使わない
        $_SESSION["_csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["_csrf_token"];
}

/**
 * フォームに埋め込む hidden タグを返す
 */
function csrf_field(): string
{
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, "UTF-8");
    return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
}

/**
 * 送信されたトークンを検証する
 */
function csrf_verify(?string $token): bool
{
    $saved = $_SESSION["_csrf_token"] ?? "";

    if ($saved === "" || $token === null || $token === "") {
        return false;
    }

    // hash_equals はタイミング攻撃に強い比較
    return hash_equals($saved, $token);
}

/**
 * POST かつトークンが正しいことを確認する。
 * 満たさない場合は 419 で終了する。
 */
function require_post_with_csrf(): void
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        http_response_code(405);
        exit("このURLへは POST でのみアクセスできます。");
    }

    if (!csrf_verify($_POST["_csrf_token"] ?? null)) {
        http_response_code(419);
        exit("セッションの有効期限が切れました。お手数ですが、もう一度お試しください。");
    }
}
