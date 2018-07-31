<!DOCTYPE html>
<html lang="ja">
 <head>
 <meta charset="UTF-8">
 <title>4eachblog 掲示板</title>
 <link rel="stylesheet" type="text/css" href="style.css">>
 </head>
 <body>
    <header>
        <img src="4eachblog_logo.jpg">
        <ul>
         <li>トップ</li>
         <li>プロフィール</li>
         <li>4eachについて</li>
         <li>登録フォーム</li>
         <li>問い合わせ</li>
         <li>その他</li>
        </ul>
    </header>
    <main>
     <div class="main-container">
        <div class="title">
         <h2>プログラミングに役立つ掲示板</h2>
        <div class="left">
   <form method="post" action="mail_confirm.php">
   <div class="nyuryoku">
   　<h1>入力フォーム</h1>
    <div>
      <lable>ハンドルネーム</lable>
      <br>
      <input type="text" name="handlename" size="35">
    </div>
   </div>
    <div>
      <lable>タイトル</lable>
      <br>
      <input type="text" name="title" size="35">
    </div>
      <div>
         <lable>コメント</lable>
         <br>
         <textarea name="comments" rows="7" cols="35"></textarea>
      </div>
      <div>
         <input type="submit" class="submit" value="送信する">
      </div>
   </form>
   <div class="taitoru">
   <div><h3>タイトル</h3>
     <p>記事の中身</p>
   </div>        
   <div><h3>タイトル</h3>
     <p>記事の中身</p>
   </div>     
   </div>
     <div class="right">
        <h2>人気の記事</h2>
        <ul>
            <li>PHP オススメ本</li>
            <li>PHP MyAdminの使い方</li>
            <li>今人気のエディタTop5</li>
            <li>HTMLの基礎</li>
        </ul>
        <br>
        <h2>オススメリンク</h2>
        <ul>
            <li>インターノウス株式会社</li>
            <li>XAMPPのダウンロード</li>
            <li>Eclipseのダウンロード</li>
            <li>Bracketsのダウンロード</li>
        </ul>
        <br>
        <h2>カテゴリ</h2>
        <ul>
            <li>HTML</li>
            <li>PHP</li>
            <li>MySQL</li>
            <li>JavaScript</li>
        </ul>
        <br>
     </div>
     </div>
   </div>
    </main>
    <footer>
     copyright (C) internous | 4each blog is the one which provides A to Z about programming.
    </footer>
 </body>
</html>