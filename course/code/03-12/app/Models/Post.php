<?php
declare(strict_types=1);

namespace App\Models;

use InvalidArgumentException;

/**
 * 掲示板の投稿1件
 *
 * 設計方針:
 *  - private __construct + static create() により、
 *    「検証を通っていないインスタンス」が存在できないようにする
 *  - readonly により、作成後に不正な状態へ変化できないようにする
 */
final class Post
{
    public const MAX_NAME_LENGTH = 30;
    public const MAX_BODY_LENGTH = 1000;
    public const DEFAULT_NAME = "名無しさん";

    private function __construct(
        public readonly string  $id,
        public readonly string  $name,
        public readonly string  $body,
        public readonly int     $createdAt,
        public readonly ?string $editToken,
    ) {}

    /**
     * 新規投稿を作る（検証つき）
     *
     * @throws InvalidArgumentException 検証に失敗した場合
     */
    public static function create(?string $name, ?string $body): self
    {
        $name = self::normalize($name ?? "");
        $body = self::normalize($body ?? "", keepNewlines: true);

        // 名前は省略可。空なら「名無しさん」
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
            id:        bin2hex(random_bytes(8)),
            name:      $name,
            body:      $body,
            createdAt: time(),
            // 投稿者だけが削除できるようにするためのトークン
            editToken: bin2hex(random_bytes(16)),
        );
    }

    /**
     * 保存データから復元する
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id:        (string) ($row["id"] ?? ""),
            name:      (string) ($row["name"] ?? self::DEFAULT_NAME),
            body:      (string) ($row["body"] ?? ""),
            createdAt: (int)    ($row["created_at"] ?? 0),
            editToken: isset($row["edit_token"]) ? (string) $row["edit_token"] : null,
        );
    }

    /**
     * 保存用の配列にする
     */
    public function toArray(): array
    {
        return [
            "id"         => $this->id,
            "name"       => $this->name,
            "body"       => $this->body,
            "created_at" => $this->createdAt,
            "edit_token" => $this->editToken,
        ];
    }

    /**
     * この投稿を削除する権限があるか
     * （ログイン機能がないため、投稿時に発行したトークンで判定する）
     */
    public function canDeleteWith(?string $token): bool
    {
        if ($this->editToken === null || $token === null || $token === "") {
            return false;
        }
        return hash_equals($this->editToken, $token);
    }

    public function formattedDate(): string
    {
        return date("Y年n月j日 H:i", $this->createdAt);
    }

    /**
     * 前後の空白を除去し、制御文字を取り除く
     */
    private static function normalize(string $value, bool $keepNewlines = false): string
    {
        $value = trim($value, " \t\n\r\0\x0B　");

        $pattern = $keepNewlines
            ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'   // 改行(\x0A)とタブ(\x09)は残す
            : '/[\x00-\x1F\x7F]/u';

        return preg_replace($pattern, "", $value) ?? "";
    }
}
