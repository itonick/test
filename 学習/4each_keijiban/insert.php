<?php
mb_internal_encoding("utf8");
$pdo=new PDO("mysql:dbname=lesson100;host=localhost;","root","mysql");
$pdo->exec("insert into contactform(name,title,comments)
values('".$_POST['name']."','".$_POST['title']."','".$_POST['comments']."');");
header("Location:http://localhost/4each_keijiban/index.php");
?>

<doctype HTML>
<html lang="ja">
   
<head>
<meta charset="utf-8">
   <title>4each 掲示板</title>
<link rel="stylesheet" type="text/css" href="style2.css">
</head>   
<body>
   <h1>お問い合わせフォーム</h1>
   <div class="confirm">
   <p>お問い合わせ有難うございました。<br>3営業日以内に担当者よりご連絡差し上げます。
   </p>
   </div>
</body>
</html>