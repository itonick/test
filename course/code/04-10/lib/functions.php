<?php
declare(strict_types=1);

/**
 * 共通ユーティリティ
 * 第3部で作った関数をまとめたもの
 */

/**
 * HTMLエスケープ（XSS対策）
 * すべての出力に必ず通すこと。
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? "", ENT_QUOTES, "UTF-8");
}

/**
 * 全角スペースも含めて前後の空白を除去する
 *
 * ⚠️ trim() の第2引数に "　"（全角スペース）を渡してはいけない。
 *    PHP の trim() はバイト単位で動くため、全角スペース(E3 80 80)の
 *    バイト E3 / 80 が、"ラテ"(E3 83 A9…) のような文字列の端を壊す。
 *    正規表現の /u フラグで「文字単位」に処理する。
 */
function trim_ja(?string $value): string
{
    return preg_replace('/\A[\s　]+|[\s　]+\z/u', "", $value ?? "") ?? "";
}

/**
 * 未入力かどうか（"0" は未入力ではない）
 */
function is_blank(?string $value): bool
{
    return trim_ja($value) === "";
}

/**
 * 制御文字を除去する（改行とタブは残す）
 */
function strip_control_chars(string $value): string
{
    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', "", $value) ?? "";
}

/**
 * 文字列を指定文字数で切り詰める
 */
function truncate(string $text, int $length, string $suffix = "…"): string
{
    return mb_strlen($text) <= $length
        ? $text
        : mb_substr($text, 0, $length) . $suffix;
}

/**
 * 金額を「1,234円」の形式にする
 */
function yen(int $amount): string
{
    return number_format($amount) . "円";
}

/**
 * 安全なURLだけを返す（http / https 以外は null）
 * javascript: スキームによるXSSを防ぐ
 */
function safe_url(?string $url): ?string
{
    $url = trim($url ?? "");
    if ($url === "") {
        return null;
    }

    $scheme = parse_url($url, PHP_URL_SCHEME);
    if (!in_array(strtolower((string) $scheme), ["http", "https"], true)) {
        return null;
    }
    return $url;
}

/**
 * 相対的な日時表現（「3分前」など）
 */
function time_ago(int $timestamp): string
{
    $diff = time() - $timestamp;

    return match (true) {
        $diff < 0         => date("Y年n月j日 H:i", $timestamp),
        $diff < 60        => "たった今",
        $diff < 3600      => floor($diff / 60) . "分前",
        $diff < 86400     => floor($diff / 3600) . "時間前",
        $diff < 86400 * 7 => floor($diff / 86400) . "日前",
        default           => date("Y年n月j日 H:i", $timestamp),
    };
}

/**
 * リダイレクトして終了する
 */
function redirect(string $path, int $status = 303): never
{
    header("Location: " . $path, true, $status);
    exit;
}

/**
 * セキュリティヘッダーを送る（出力より前に呼ぶ）
 */
function send_security_headers(): void
{
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("Referrer-Policy: strict-origin-when-cross-origin");
}
