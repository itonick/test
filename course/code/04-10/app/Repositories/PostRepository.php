<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Post;
use PDO;

/**
 * 投稿の保存・取得を担当する（DB版）
 *
 * 第3部（JSON版）とメソッドの名前・引数・戻り値は同じ。
 * 中身だけを PDO に差し替えたので、呼び出し側（index.php）はほぼ変わらない。
 * これがリポジトリパターンの狙い。
 */
final class PostRepository
{
    public function __construct(private readonly PDO $pdo) {}

    /**
     * ページ単位で取得する（投稿者名・返信数つき）
     *
     * @return array{posts: Post[], total: int, page: int, totalPages: int}
     */
    public function paginate(int $page, int $perPage = 10, string $keyword = ""): array
    {
        $where  = "";
        $params = [];

        if ($keyword !== "") {
            $where = "WHERE p.name LIKE :kw OR p.body LIKE :kw";
            // LIKE のワイルドカードをエスケープする（4-8）
            $params["kw"] = "%" . addcslashes($keyword, "\\%_") . "%";
        }

        // ① 総件数（ページ送りの計算に必要）
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM posts p {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $totalPages = max(1, (int) ceil($total / $perPage));
        $page       = max(1, min($page, $totalPages));
        $offset     = ($page - 1) * $perPage;

        // ② 該当ページの投稿を取得
        //    返信数は相関サブクエリで取る（複数の1対多を JOIN する掛け算を避ける・4-6）
        $sql = "
            SELECT
                p.id, p.user_id, p.name, p.body, p.edit_token, p.created_at,
                u.name AS user_name,
                (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count
            FROM posts p
            LEFT JOIN users u ON u.id = p.user_id
            {$where}
            ORDER BY p.created_at DESC, p.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue("limit",  $perPage, PDO::PARAM_INT);
        $stmt->bindValue("offset", $offset,  PDO::PARAM_INT);
        $stmt->execute();

        $posts = array_map(
            static fn(array $row) => Post::fromRow($row),
            $stmt->fetchAll()
        );

        return compact("posts", "total", "page", "totalPages");
    }

    public function find(int $id): ?Post
    {
        $stmt = $this->pdo->prepare("SELECT * FROM posts WHERE id = :id");
        $stmt->execute(["id" => $id]);

        $row = $stmt->fetch();
        return $row === false ? null : Post::fromRow($row);
    }

    /**
     * 保存して、採番されたIDを返す
     */
    public function save(Post $post): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO posts (user_id, name, body, edit_token)
             VALUES (:user_id, :name, :body, :edit_token)"
        );
        $stmt->execute([
            "user_id"    => $post->userId,
            "name"       => $post->name,
            "body"       => $post->body,
            "edit_token" => $post->editToken,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function delete(int $id): bool
    {
        // comments は ON DELETE CASCADE なので、返信も一緒に消える
        $stmt = $this->pdo->prepare("DELETE FROM posts WHERE id = :id");
        $stmt->execute(["id" => $id]);
        return $stmt->rowCount() > 0;
    }

    public function count(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    }
}
