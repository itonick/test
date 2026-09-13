<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Post;
use RuntimeException;

/**
 * 投稿の保存・取得を担当する
 *
 * 保存先の詳細（いまはJSONファイル）をこのクラスに閉じ込めることで、
 * 第4部でデータベースに移行するとき、呼び出し側を変えずに済む。
 */
final class PostRepository
{
    public function __construct(private readonly string $filePath) {}

    /**
     * すべての投稿を新しい順で返す
     *
     * @return Post[]
     */
    public function all(): array
    {
        $rows  = $this->load();
        $posts = array_map(static fn(array $row) => Post::fromArray($row), $rows);

        usort($posts, static fn(Post $a, Post $b) => $b->createdAt <=> $a->createdAt);

        return $posts;
    }

    /**
     * ページ単位で取得する
     *
     * @return array{posts: Post[], total: int, page: int, totalPages: int}
     */
    public function paginate(int $page, int $perPage = 10): array
    {
        $all   = $this->all();
        $total = count($all);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page  = max(1, min($page, $totalPages));

        return [
            "posts"      => array_slice($all, ($page - 1) * $perPage, $perPage),
            "total"      => $total,
            "page"       => $page,
            "totalPages" => $totalPages,
        ];
    }

    /**
     * キーワードで検索する
     *
     * @return Post[]
     */
    public function search(string $keyword): array
    {
        if ($keyword === "") {
            return $this->all();
        }

        return array_values(array_filter(
            $this->all(),
            static fn(Post $p) =>
                mb_stripos($p->body, $keyword) !== false
                || mb_stripos($p->name, $keyword) !== false
        ));
    }

    public function find(string $id): ?Post
    {
        foreach ($this->all() as $post) {
            if ($post->id === $id) {
                return $post;
            }
        }
        return null;
    }

    public function save(Post $post): void
    {
        $this->withLock(function (array $rows) use ($post): array {
            $rows[] = $post->toArray();
            return $rows;
        });
    }

    /**
     * 削除する。削除できたら true
     */
    public function delete(string $id): bool
    {
        $deleted = false;

        $this->withLock(function (array $rows) use ($id, &$deleted): array {
            $filtered = array_values(array_filter(
                $rows,
                static fn(array $row) => ($row["id"] ?? "") !== $id
            ));
            $deleted = count($filtered) !== count($rows);
            return $filtered;
        });

        return $deleted;
    }

    public function count(): int
    {
        return count($this->load());
    }

    // ==========================================================
    // 内部処理
    // ==========================================================

    /**
     * ファイルをロックしたまま「読み込み → 変更 → 書き込み」を行う。
     * 同時アクセスでデータが失われるのを防ぐ。
     *
     * @param callable(array): array $mutator
     */
    private function withLock(callable $mutator): void
    {
        $this->ensureDirectory();

        $handle = fopen($this->filePath, "c+");
        if ($handle === false) {
            throw new RuntimeException("データファイルを開けません: {$this->filePath}");
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException("ファイルをロックできません");
            }

            $size = filesize($this->filePath) ?: 0;
            $raw  = $size > 0 ? (string) fread($handle, $size) : "";
            $rows = $this->decode($raw);

            $rows = $mutator($rows);

            $json = json_encode(
                $rows,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
            if ($json === false) {
                throw new RuntimeException("JSONへの変換に失敗しました");
            }

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, $json);
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function load(): array
    {
        if (!is_file($this->filePath)) {
            return [];
        }
        $raw = file_get_contents($this->filePath);
        return $this->decode($raw === false ? "" : $raw);
    }

    private function decode(string $raw): array
    {
        if (trim($raw) === "") {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private function ensureDirectory(): void
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException("保存先ディレクトリを作成できません: {$dir}");
        }
    }
}
