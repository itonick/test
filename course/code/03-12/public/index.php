<?php
declare(strict_types=1);

/**
 * 掲示板 — 一覧・投稿・削除
 * 第3部 総合演習の完成版
 */

require_once __DIR__ . "/../lib/session.php";
start_secure_session();

require_once __DIR__ . "/../lib/functions.php";
require_once __DIR__ . "/../lib/csrf.php";
require_once __DIR__ . "/../app/Models/Post.php";
require_once __DIR__ . "/../app/Repositories/PostRepository.php";

use App\Models\Post;
use App\Repositories\PostRepository;

send_security_headers();

// storage は公開ディレクトリ（public）の外に置く
$repository = new PostRepository(__DIR__ . "/../storage/posts.json");

const PER_PAGE = 10;

// ==========================================================
// POST: 投稿 / 削除
// ==========================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_post_with_csrf();

    $action = $_POST["action"] ?? "";

    // ---------- 投稿 ----------
    if ($action === "create") {
        try {
            $post = Post::create($_POST["name"] ?? "", $_POST["body"] ?? "");
            $repository->save($post);

            // 自分が投稿したものを削除できるよう、トークンをセッションに保存
            $_SESSION["my_posts"][$post->id] = $post->editToken;

            set_flash("success", "投稿しました。");
        } catch (InvalidArgumentException $e) {
            set_errors(["body" => $e->getMessage()]);
            set_old(["name" => $_POST["name"] ?? "", "body" => $_POST["body"] ?? ""]);
        } catch (Throwable $e) {
            error_log("投稿の保存に失敗: " . $e->getMessage());
            set_flash("error", "投稿の保存に失敗しました。時間をおいてお試しください。");
        }

        redirect("index.php");
    }

    // ---------- 削除 ----------
    if ($action === "delete") {
        $id = (string) ($_POST["id"] ?? "");
        $post = $repository->find($id);

        if ($post === null) {
            set_flash("error", "投稿が見つかりませんでした。");
            redirect("index.php");
        }

        // ★ 認可チェック：自分の投稿かどうかを確認する
        //    これがないと、IDを書き換えるだけで他人の投稿を削除できてしまう
        $my_token = $_SESSION["my_posts"][$id] ?? null;

        if (!$post->canDeleteWith($my_token)) {
            http_response_code(403);
            set_flash("error", "この投稿を削除する権限がありません。");
            redirect("index.php");
        }

        try {
            $repository->delete($id);
            unset($_SESSION["my_posts"][$id]);
            set_flash("success", "投稿を削除しました。");
        } catch (Throwable $e) {
            error_log("投稿の削除に失敗: " . $e->getMessage());
            set_flash("error", "削除に失敗しました。");
        }

        redirect("index.php");
    }

    redirect("index.php");
}

// ==========================================================
// GET: 表示
// ==========================================================

$keyword = trim_ja($_GET["q"] ?? "");
$page    = max(1, (int) ($_GET["page"] ?? 1));

if ($keyword !== "") {
    $found      = $repository->search($keyword);
    $total      = count($found);
    $totalPages = max(1, (int) ceil($total / PER_PAGE));
    $page       = min($page, $totalPages);
    $posts      = array_slice($found, ($page - 1) * PER_PAGE, PER_PAGE);
} else {
    $result     = $repository->paginate($page, PER_PAGE);
    $posts      = $result["posts"];
    $total      = $result["total"];
    $page       = $result["page"];
    $totalPages = $result["totalPages"];
}

$flash  = take_flash();
$errors = take_errors();
$old    = take_old();

$my_posts = $_SESSION["my_posts"] ?? [];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ひとこと掲示板</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="site-header">
  <div class="inner">
    <h1><a href="index.php">ひとこと掲示板</a></h1>
    <p class="count"><?= number_format($total) ?>件の投稿</p>
  </div>
</header>

<main class="container">

  <?php if ($flash !== null): ?>
    <p class="alert alert-<?= e($flash["type"]) ?>"
       role="<?= $flash["type"] === "error" ? "alert" : "status" ?>">
      <?= e($flash["message"]) ?>
    </p>
  <?php endif; ?>

  <!-- ========== 投稿フォーム ========== -->
  <section class="card" aria-labelledby="form-heading">
    <h2 id="form-heading">投稿する</h2>

    <form method="post" class="post-form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create">

      <div class="field">
        <label for="name">お名前<span class="optional">（省略可）</span></label>
        <input type="text" id="name" name="name"
               value="<?= e($old["name"] ?? "") ?>"
               maxlength="<?= Post::MAX_NAME_LENGTH ?>"
               placeholder="<?= e(Post::DEFAULT_NAME) ?>"
               autocomplete="nickname">
      </div>

      <div class="field">
        <label for="body">本文<span class="required">必須</span></label>
        <textarea id="body" name="body" rows="5"
                  maxlength="<?= Post::MAX_BODY_LENGTH ?>"
                  placeholder="ご自由にどうぞ"
                  aria-describedby="err-body"
                  required><?= e($old["body"] ?? "") ?></textarea>
        <p class="error" id="err-body" role="alert"><?= e($errors["body"] ?? "") ?></p>
      </div>

      <button type="submit" class="btn">投稿する</button>
    </form>
  </section>

  <!-- ========== 検索 ========== -->
  <form method="get" class="search-form" role="search">
    <label for="q" class="visually-hidden">投稿を検索</label>
    <input type="search" id="q" name="q" value="<?= e($keyword) ?>"
           placeholder="投稿を検索">
    <button type="submit" class="btn btn-sub">検索</button>
    <?php if ($keyword !== ""): ?>
      <a href="index.php" class="link-btn">検索を解除</a>
    <?php endif; ?>
  </form>

  <?php if ($keyword !== ""): ?>
    <p class="search-result" aria-live="polite">
      「<?= e($keyword) ?>」の検索結果: <?= number_format($total) ?>件
    </p>
  <?php endif; ?>

  <!-- ========== 一覧 ========== -->
  <section aria-label="投稿一覧">
    <?php if (count($posts) === 0): ?>
      <p class="empty">
        <?php if ($keyword !== ""): ?>
          条件に一致する投稿が見つかりませんでした。<br>
          別のキーワードでお試しください。
        <?php else: ?>
          まだ投稿がありません。<br>
          上のフォームから、最初の投稿をしてみてください。
        <?php endif; ?>
      </p>
    <?php else: ?>
      <ul class="post-list">
        <?php foreach ($posts as $post): ?>
          <li class="post">
            <div class="post-head">
              <span class="post-name"><?= e($post->name) ?></span>
              <time class="post-date" datetime="<?= date("c", $post->createdAt) ?>"
                    title="<?= e($post->formattedDate()) ?>">
                <?= e(time_ago($post->createdAt)) ?>
              </time>
            </div>

            <!-- white-space: pre-wrap で改行を保つ（HTMLを生成しないので安全） -->
            <p class="post-body"><?= e($post->body) ?></p>

            <?php if (isset($my_posts[$post->id])): ?>
              <form method="post" class="delete-form"
                    onsubmit="return confirm('この投稿を削除します。よろしいですか？');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= e($post->id) ?>">
                <button type="submit" class="link-btn danger">削除</button>
              </form>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <!-- ========== ページ送り ========== -->
  <?php if ($totalPages > 1): ?>
    <nav class="pagination" aria-label="ページ送り">
      <?php
      $query = static function (int $p) use ($keyword): string {
          $params = ["page" => $p];
          if ($keyword !== "") {
              $params["q"] = $keyword;
          }
          return "index.php?" . http_build_query($params);
      };
      ?>

      <?php if ($page > 1): ?>
        <a href="<?= e($query($page - 1)) ?>" rel="prev">前へ</a>
      <?php else: ?>
        <span class="disabled">前へ</span>
      <?php endif; ?>

      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i === $page): ?>
          <span class="current" aria-current="page"><?= $i ?></span>
        <?php else: ?>
          <a href="<?= e($query($i)) ?>"><?= $i ?></a>
        <?php endif; ?>
      <?php endfor; ?>

      <?php if ($page < $totalPages): ?>
        <a href="<?= e($query($page + 1)) ?>" rel="next">次へ</a>
      <?php else: ?>
        <span class="disabled">次へ</span>
      <?php endif; ?>
    </nav>
  <?php endif; ?>

</main>

<footer class="site-footer">
  <p>&copy; <?= date("Y") ?> ひとこと掲示板</p>
</footer>

</body>
</html>
