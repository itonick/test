<?php
declare(strict_types=1);

/**
 * 安全な設定でセッションを開始する
 */
function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        "lifetime" => 0,
        "path"     => "/",
        "domain"   => "",
        "secure"   => !empty($_SERVER["HTTPS"]),  // HTTPS のときだけ送信
        "httponly" => true,                       // JavaScript から読めない（XSS対策）
        "samesite" => "Lax",                      // CSRF 対策
    ]);

    session_start();
}

/**
 * フラッシュメッセージを設定する（次のリクエストで1回だけ表示）
 */
function set_flash(string $type, string $message): void
{
    $_SESSION["_flash"] = ["type" => $type, "message" => $message];
}

/**
 * フラッシュメッセージを取り出す（取り出したら消える）
 * @return array{type: string, message: string}|null
 */
function take_flash(): ?array
{
    $flash = $_SESSION["_flash"] ?? null;
    unset($_SESSION["_flash"]);
    return $flash;
}

/**
 * 入力値を一時保存する（エラー時に戻すため）
 */
function set_old(array $input): void
{
    $_SESSION["_old"] = $input;
}

/**
 * 一時保存した入力値を取り出す
 */
function take_old(): array
{
    $old = $_SESSION["_old"] ?? [];
    unset($_SESSION["_old"]);
    return $old;
}

/**
 * エラーを一時保存する
 */
function set_errors(array $errors): void
{
    $_SESSION["_errors"] = $errors;
}

/**
 * エラーを取り出す
 */
function take_errors(): array
{
    $errors = $_SESSION["_errors"] ?? [];
    unset($_SESSION["_errors"]);
    return $errors;
}
