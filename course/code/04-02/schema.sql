-- ==========================================================
-- 掲示板アプリ — テーブル定義（MySQL 8.0 / MariaDB 10.4+）
--
-- phpMyAdmin の「SQL」タブに貼って実行するか、
--   mysql -u root board_app < schema.sql
-- で実行してください。
-- ==========================================================

-- データベースを作る（すでにある場合は作らない）
CREATE DATABASE IF NOT EXISTS board_app
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE board_app;


-- ==========================================================
-- users — 利用者
-- ==========================================================
CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(50)  NOT NULL,
    -- UNIQUE により、同じメールアドレスでの二重登録をDBが拒否する。
    -- PHPでの重複チェックは同時アクセスで通り抜けるため、この制約が最後の砦になる。
    email         VARCHAR(255) NOT NULL UNIQUE,
    -- ハッシュのアルゴリズムが変わると長さも変わるため 255 で余裕を持たせる
    password_hash VARCHAR(255) NOT NULL,
    avatar        VARCHAR(255) NULL DEFAULT NULL,
    bio           TEXT         NULL DEFAULT NULL,
    is_admin      BOOLEAN      NOT NULL DEFAULT FALSE,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ==========================================================
-- posts — 投稿
-- ==========================================================
CREATE TABLE IF NOT EXISTS posts (
    id         INT AUTO_INCREMENT PRIMARY KEY,

    -- 会員投稿なら users.id、ゲスト投稿なら NULL
    user_id    INT          NULL DEFAULT NULL,

    name       VARCHAR(30)  NOT NULL,
    body       TEXT         NOT NULL,

    -- ログインしていないゲストが「自分の投稿だけ削除」できるようにするトークン
    edit_token CHAR(32)     NOT NULL,

    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME     NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    -- 一覧は created_at の降順で取るため、インデックスを張る
    INDEX idx_posts_created_at (created_at),
    INDEX idx_posts_user_id (user_id),

    -- 投稿者が削除されたら、投稿の user_id は NULL にする（投稿自体は残す）
    CONSTRAINT fk_posts_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ==========================================================
-- comments — 投稿への返信
-- ==========================================================
CREATE TABLE IF NOT EXISTS comments (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    post_id    INT          NOT NULL,
    name       VARCHAR(30)  NOT NULL,
    body       TEXT         NOT NULL,
    edit_token CHAR(32)     NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_comments_post_id (post_id),

    -- 親の投稿が削除されたら、返信も一緒に削除する
    CONSTRAINT fk_comments_post
        FOREIGN KEY (post_id) REFERENCES posts(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ==========================================================
-- tags / post_tag — タグ（多対多の例）
-- ==========================================================
CREATE TABLE IF NOT EXISTS tags (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS post_tag (
    post_id INT NOT NULL,
    tag_id  INT NOT NULL,

    -- 2つのカラムの組み合わせを主キーにする（同じタグを二重に付けられない）
    PRIMARY KEY (post_id, tag_id),

    INDEX idx_post_tag_tag_id (tag_id),

    CONSTRAINT fk_post_tag_post
        FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_post_tag_tag
        FOREIGN KEY (tag_id)  REFERENCES tags(id)  ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
