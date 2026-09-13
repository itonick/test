<?php
declare(strict_types=1);

namespace App\Models;

use InvalidArgumentException;

/**
 * 掲示板の投稿1件（DB版）
 *
 * 第3部の Post とほぼ同じ。違いは id が int（AUTO_INCREMENT）になり、
 * commentCount と userName（一覧取得時に JOIN で付く値）を持てる点。
 */
final class Post
{
    public const MAX_NAME_LENGTH = 30;
    public const MAX_BODY_LENGTH = 1000;
    public const DEFAULT_NAME = "名無しさん";

    private function __construct(
        public readonly ?int    $id,
        public readonly ?int    $userId,
        public readonly string  $name,
        public readonly string  $body,
        public readonly string  $editToken,
        public readonly ?string $createdAt,
        public readonly ?string $userName = null,
        public readonly int     $commentCount = 0,
    ) {}

    /**
     * 新規投稿を作る（検証つき・IDはDBが採番するので null）
     *
     * @throws InvalidArgumentException
     */
    public static function create(?string $name, ?string $body, ?int $userId = null): self
    {
        $name = self::normalize($name ?? "");
        $body = self::normalize($body ?? "", keepNewlines: true);

        if ($name === "") {
            $name = self::DEFAULT_NAME;
        }
        if (mb_strlen($name) > self::MAX_NAME_LENGTH) {
            throw new InvalidArgumentException(
                sprintf("お名前は%d文字以内で入力してください（現在%d文字）",
                    self::MAX_NAME_LENGTH, mb_strlen($name))
            );
        }
        if ($body === "") {
            throw new InvalidArgumentException("本文を入力してください");
        }
        if (mb_strlen($body) > self::MAX_BODY_LENGTH) {
            throw new InvalidArgumentException(
                sprintf("本文は%d文字以内で入力してください（現在%d文字）",
                    self::MAX_BODY_LENGTH, mb_strlen($body))
            );
        }

        return new self(
            id:        null,
            userId:    $userId,
            name:      $name,
            body:      $body,
            editToken: bin2hex(random_bytes(16)),
            createdAt: null,
        );
    }

    /**
     * DBの行から復元する
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id:           isset($row["id"]) ? (int) $row["id"] : null,
            userId:       isset($row["user_id"]) ? (int) $row["user_id"] : null,
            name:         (string) ($row["name"] ?? self::DEFAULT_NAME),
            body:         (string) ($row["body"] ?? ""),
            editToken:    (string) ($row["edit_token"] ?? ""),
            createdAt:    $row["created_at"] ?? null,
            userName:     $row["user_name"] ?? null,
            commentCount: isset($row["comment_count"]) ? (int) $row["comment_count"] : 0,
        );
    }

    public function canDeleteWith(?string $token): bool
    {
        if ($this->editToken === "" || $token === null || $token === "") {
            return false;
        }
        return hash_equals($this->editToken, $token);
    }

    public function formattedDate(): string
    {
        if ($this->createdAt === null) {
            return "";
        }
        $ts = strtotime($this->createdAt);
        return $ts === false ? "" : date("Y年n月j日 H:i", $ts);
    }

    private static function normalize(string $value, bool $keepNewlines = false): string
    {
        // 全角スペースを含む前後の空白を除去（trim の第2引数はバイト単位で
        // 動き、"ラテ" のような文字列の端を壊すため、正規表現の /u で行う）
        $value = preg_replace('/\A[\s　]+|[\s　]+\z/u', "", $value) ?? "";
        $pattern = $keepNewlines
            ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'
            : '/[\x00-\x1F\x7F]/u';
        return preg_replace($pattern, "", $value) ?? "";
    }
}
