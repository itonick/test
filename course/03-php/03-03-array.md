# 3-3 条件分岐・繰り返し・配列

> 🎯 **このレッスンのゴール**
> - PHPの制御構文を書ける
> - 配列（インデックス配列・連想配列）を自在に扱える
> - 配列関数で集計・変換ができる

所要 150分 / 難度 🟢
完成コード: [`code/03-03/`](../code/03-03/)

---

## ✍️ 手を動かす① ─ 条件分岐

```php
<?php
declare(strict_types=1);

$score = 82;

if ($score >= 90) {
    echo "優";
} elseif ($score >= 70) {     // ← else if ではなく elseif（1語）
    echo "良";
} elseif ($score >= 60) {
    echo "可";
} else {
    echo "不可";
}
```

> 💡 PHP では `elseif`（1語）が推奨です。`else if`（2語）も動きますが、
> HTMLと混ぜる書き方（後述）では `elseif` でないと動きません。**`elseif` で統一してください。**

### match 式（PHP 8以降・推奨）

```php
<?php
$topic = "reserve";

// switch（従来）
switch ($topic) {
    case "reserve": $label = "ご予約"; break;
    case "bean":    $label = "豆の販売"; break;
    default:        $label = "その他";
}

// match（PHP 8以降）— こちらが安全で短い
$label = match ($topic) {
    "reserve" => "ご予約",
    "bean"    => "豆の販売",
    "event", "other" => "その他",     // 複数まとめられる
    default   => "未選択",
};
```

**`match` が `switch` より優れている点**

| 点 | 説明 |
| --- | --- |
| `break` が不要 | フォールスルーしない |
| **厳密比較（`===`）** | `switch` はゆるい比較（`==`）で危険 |
| 値を返す | 変数に直接代入できる |
| 網羅していないとエラー | `default` がないと `UnhandledMatchError` |

> ⚠️ **`switch` はゆるい比較を使います。**
> ```php
> switch ("1abc") {
>     case 1: echo "マッチしてしまう"; break;   // PHP 7 では真
> }
> ```
> **`match` を使ってください。** PHP 8 以降が使える環境なら、`switch` を書く理由はほぼありません。

---

## ✍️ 手を動かす② ─ 繰り返し

```php
<?php
// for
for ($i = 0; $i < 5; $i++) {
    echo $i;
}

// while
$n = 0;
while ($n < 5) {
    echo $n;
    $n++;
}

// do-while（最低1回は実行される）
do {
    echo "1回は実行";
} while (false);

// foreach（配列を回す。最頻出）
$fruits = ["りんご", "みかん", "ぶどう"];
foreach ($fruits as $fruit) {
    echo $fruit;
}

// キーも取る
$user = ["name" => "太郎", "age" => 20];
foreach ($user as $key => $value) {
    echo "{$key}: {$value}<br>";
}

// break / continue
foreach ([1, 2, 3, 4, 5] as $n) {
    if ($n === 3) continue;   // この回だけスキップ
    if ($n === 5) break;      // ループを抜ける
    echo $n;                  // 1, 2, 4
}
```

### `foreach` で元の配列を書き換える

```php
<?php
$prices = [100, 200, 300];

// ❌ これでは元の配列は変わらない
foreach ($prices as $price) {
    $price = $price * 2;
}
print_r($prices);   // [100, 200, 300]

// ✅ 参照（&）を使う
foreach ($prices as &$price) {
    $price = $price * 2;
}
unset($price);      // ← ★ 必ず unset する
print_r($prices);   // [200, 400, 600]

// ✅ キーで書き換える（こちらが安全）
foreach ($prices as $i => $price) {
    $prices[$i] = $price * 2;
}

// ✅ array_map を使う（最も安全）
$prices = array_map(fn($p) => $p * 2, $prices);
```

> ⚠️ **`foreach ($arr as &$v)` の後は、必ず `unset($v);` してください。**
>
> ```php
> $arr = [1, 2, 3];
> foreach ($arr as &$v) {}     // $v は最後の要素への参照のまま残る
> foreach ($arr as $v) {}      // ← ここで $arr[2] が上書きされていく
> print_r($arr);               // [1, 2, 2] 😱
> ```
>
> **PHPで最も有名なバグの1つです。** `&` を使ったら `unset`。セットで覚えてください。
> そもそも `array_map` を使えば、この問題は起きません。

---

## ✍️ 手を動かす③ ─ 配列の基本

PHPの配列は、**インデックス配列と連想配列が同じもの**です（JavaScript の配列とオブジェクトが1つになったイメージ）。

```php
<?php
// インデックス配列
$fruits = ["りんご", "みかん", "ぶどう"];
echo $fruits[0];        // りんご
echo count($fruits);    // 3

// 連想配列
$user = [
    "name"  => "山田太郎",
    "age"   => 20,
    "email" => "taro@example.com",
];
echo $user["name"];

// 入れ子
$menu = [
    ["id" => "drip",  "name" => "ハンドドリップ", "price" => 600],
    ["id" => "latte", "name" => "カフェラテ",     "price" => 600],
];
echo $menu[0]["name"];
```

> 💡 **`$menu` の形（連想配列の配列）が、実務で最もよく使う形**です。
> データベースから取得したデータも、この形になります（第4部）。

> 🆘 **ここで詰まったら**（`Warning: Undefined array key` ／中身が思ったのと違う）
> - **まず中身を見る**：`var_dump($menu);` または `echo "<pre>"; print_r($menu); echo "</pre>";` で**実際の構造とキー名**を確認する。これがPHP配列デバッグの基本動作です
> - **キーが無いと言われる**：そのキーのつづり違い、または存在しない階層にアクセスしている。取り出しは `$user["name"] ?? ""` のように `?? ` で守る
> - **直らなければ、AIにこう聞く**（`var_dump` の出力と、取り出したい値を貼る）：
>   「この配列（var_dumpの結果を貼る）から○○を取り出したいのですが、Undefined array key になります。正しいアクセス方法を教えてください」

### 追加・削除

```php
<?php
$arr = [1, 2, 3];

$arr[] = 4;              // 末尾に追加
array_push($arr, 5, 6);  // 複数追加
$last = array_pop($arr); // 末尾を取り出して削除
$first = array_shift($arr);   // 先頭を取り出して削除
array_unshift($arr, 0);  // 先頭に追加

// 連想配列
$user["tel"] = "090-1234-5678";   // 追加
unset($user["age"]);              // 削除
```

> ⚠️ **`unset()` は、インデックス配列で使うと「穴が空きます」。**
>
> ```php
> $arr = ["a", "b", "c"];
> unset($arr[1]);
> print_r($arr);   // [0 => "a", 2 => "c"]  ← キー 1 が抜けている
>
> $arr = array_values($arr);   // 振り直す → [0 => "a", 1 => "c"]
> ```
>
> **削除後に `array_values()` で振り直す**のを忘れないでください。
> JSON に変換したとき、配列ではなくオブジェクトになってしまいます。

### 存在確認

```php
<?php
$user = ["name" => "太郎", "age" => null];

var_dump(isset($user["name"]));               // true
var_dump(isset($user["age"]));                // false ← 値が null だと false
var_dump(array_key_exists("age", $user));     // true  ← キーの存在だけ見る
var_dump(in_array("太郎", $user));             // true  ← 値が含まれるか
var_dump(array_search("太郎", $user));         // "name"（キーが返る。無ければ false）

// デフォルト値
$tel = $user["tel"] ?? "未登録";
```

> ⚠️ **`in_array()` はデフォルトでゆるい比較（`==`）です。**
> ```php
> in_array("abc", [0, 1, 2]);        // PHP 7では true 😱
> in_array("abc", [0, 1, 2], true);  // false ✅ 第3引数に true
> ```
> **第3引数に `true` を付けて厳密比較にしてください。**

---

## ✍️ 手を動かす④ ─ 配列関数（JavaScript との対応）

| やりたいこと | JavaScript | PHP |
| --- | --- | --- |
| 変換 | `arr.map(f)` | `array_map(f, $arr)` |
| 絞り込み | `arr.filter(f)` | `array_filter($arr, f)` |
| 集計 | `arr.reduce(f, 0)` | `array_reduce($arr, f, 0)` |
| 検索（1件） | `arr.find(f)` | （なし。`array_filter` + `reset`） |
| 含むか | `arr.includes(x)` | `in_array($x, $arr, true)` |
| 結合 | `arr.join(",")` | `implode(",", $arr)` |
| 分割 | `str.split(",")` | `explode(",", $str)` |
| 件数 | `arr.length` | `count($arr)` |
| 並び替え | `arr.sort(f)` | `usort($arr, f)` |

> ⚠️ **引数の順番に注意。** `array_map` は**関数が先**、`array_filter` は**配列が先**です。
> これは PHP の歴史的な不統一で、全員が混乱します。**IDEの補完に頼ってください。**

### 実例

```php
<?php
declare(strict_types=1);

$menu = [
    ["id" => "drip",  "name" => "ハンドドリップ", "price" => 600, "category" => "coffee", "sold_out" => false],
    ["id" => "latte", "name" => "カフェラテ",     "price" => 600, "category" => "coffee", "sold_out" => false],
    ["id" => "cake",  "name" => "本日のケーキ",   "price" => 550, "category" => "food",   "sold_out" => true],
    ["id" => "tea",   "name" => "季節のお茶",     "price" => 500, "category" => "tea",    "sold_out" => false],
];

// ① 名前だけの配列
$names = array_map(fn($m) => $m["name"], $menu);

// ② 売り切れでないものだけ
$available = array_filter($menu, fn($m) => !$m["sold_out"]);
$available = array_values($available);   // ★ キーを振り直す

// ③ 合計金額
$total = array_reduce($menu, fn($sum, $m) => $sum + $m["price"], 0);

// ④ 1件検索（PHPに find がないので自作するのが定石）
function array_find(array $arr, callable $fn): mixed
{
    foreach ($arr as $item) {
        if ($fn($item)) return $item;
    }
    return null;
}
$latte = array_find($menu, fn($m) => $m["id"] === "latte");

// ⑤ カテゴリごとにグループ化
$grouped = [];
foreach ($menu as $m) {
    $grouped[$m["category"]][] = $m;
}

// ⑥ IDをキーにした配列に変換（検索を高速にする）
$by_id = array_column($menu, null, "id");
echo $by_id["latte"]["name"];   // カフェラテ

// ⑦ 特定のカラムだけ取り出す
$prices = array_column($menu, "price");            // [600, 600, 550, 500]
$name_by_id = array_column($menu, "name", "id");   // ["drip" => "ハンドドリップ", ...]

// ⑧ 並び替え（価格の安い順）
usort($menu, fn($a, $b) => $a["price"] <=> $b["price"]);
```

> ⚠️ **`array_filter` はキーを保持します。**
>
> ```php
> $r = array_filter([1, 2, 3, 4], fn($n) => $n % 2 === 0);
> print_r($r);   // [1 => 2, 3 => 4]  ← キーが飛んでいる
> json_encode($r);   // {"1":2,"3":4}  ← 配列でなくオブジェクトになる 😱
>
> $r = array_values($r);   // ✅ [2, 4]
> ```
>
> **`array_filter` の後は `array_values()`。** これは実務でも頻出の罠です。

> 💡 **`array_column()` は非常に強力です。** 覚えておくと、ループを書く回数が激減します。

### その他のよく使う配列関数

```php
<?php
$a = [3, 1, 4, 1, 5];

sort($a);                    // 昇順（元を変える）
rsort($a);                   // 降順
$b = $a; sort($b);           // 元を残したいならコピーしてから

$unique = array_unique($a);          // 重複を除く
$sum = array_sum($a);                // 合計
$max = max($a);  $min = min($a);     // 最大・最小
$avg = array_sum($a) / count($a);    // 平均
$sliced = array_slice($a, 1, 2);     // 切り出し
$merged = array_merge([1,2], [3,4]); // 結合 → [1,2,3,4]
$merged = [...[1,2], ...[3,4]];      // スプレッド（PHP 8.1以降）
$reversed = array_reverse($a);
$keys = array_keys($user);
$values = array_values($user);
$flipped = array_flip(["a" => 1]);   // [1 => "a"]

// 連想配列の結合（後ろが優先）
$config = array_merge($defaults, $user_config);
$config = [...$defaults, ...$user_config];   // PHP 8.1以降
```

### ソート関数の使い分け

| 関数 | 並べる基準 | キーは保持される？ |
| --- | --- | --- |
| `sort()` | 値・昇順 | されない（振り直される） |
| `rsort()` | 値・降順 | されない |
| `asort()` | 値・昇順 | **される** |
| `arsort()` | 値・降順 | **される** |
| `ksort()` | **キー**・昇順 | される |
| `usort()` | 自分で定義 | されない |
| `uasort()` | 自分で定義 | **される** |

> 💡 **連想配列を値で並べたいなら `asort`、インデックス配列なら `sort`、複雑な条件なら `usort`。**
> `u` は user-defined（自分で定義）、`a` は associative（連想）、`k` は key の頭文字です。

---

## ✍️ 手を動かす⑤ ─ 実践：メニューの集計

`php-lesson/menu.php`（📋 完成形は `code/03-03/menu.php`）

```php
<?php
declare(strict_types=1);

$menu = [
    ["id" => "drip",  "name" => "ハンドドリップ", "price" => 600, "category" => "coffee", "sold_out" => false],
    ["id" => "latte", "name" => "カフェラテ",     "price" => 600, "category" => "coffee", "sold_out" => false],
    ["id" => "cake",  "name" => "本日のケーキ",   "price" => 550, "category" => "food",   "sold_out" => true],
    ["id" => "tea",   "name" => "季節のお茶",     "price" => 500, "category" => "tea",    "sold_out" => false],
    ["id" => "toast", "name" => "厚切りトースト", "price" => 450, "category" => "food",   "sold_out" => false],
];

$category_labels = [
    "coffee" => "コーヒー",
    "food"   => "フード",
    "tea"    => "お茶",
];

// ---------- 集計 ----------

$available = array_values(array_filter($menu, fn($m) => !$m["sold_out"]));
$total     = array_sum(array_column($menu, "price"));
$average   = (int) round($total / count($menu));

// カテゴリごとにグループ化
$grouped = [];
foreach ($menu as $item) {
    $grouped[$item["category"]][] = $item;
}

// カテゴリごとの件数
$counts = array_map("count", $grouped);

// 最も高い商品
usort($menu, fn($a, $b) => $b["price"] <=> $a["price"]);
$most_expensive = $menu[0];

// ---------- 出力 ----------

echo "<h2>集計結果</h2>";
echo "<p>全 " . count($menu) . " 品／販売中 " . count($available) . " 品</p>";
echo "<p>合計 " . number_format($total) . " 円／平均 " . number_format($average) . " 円</p>";
echo "<p>最高値: {$most_expensive['name']}（" . number_format($most_expensive['price']) . "円）</p>";

echo "<h3>カテゴリ別</h3><ul>";
foreach ($counts as $category => $count) {
    $label = $category_labels[$category] ?? $category;
    echo "<li>{$label}: {$count}品</li>";
}
echo "</ul>";
```

**動作確認**：`http://localhost/php-lesson/menu.php`

> ⚠️ **`echo` の中で `{$most_expensive['name']}` と波括弧で囲んでいる**点に注目してください。
> 囲まないと Parse error になります（3-2で学習済み）。

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| `foreach` で元が変わらない | 値のコピーを回している | `&` を使うか `array_map` |
| `&` の後に配列が壊れる | `unset()` を忘れた | 必ず `unset($v);` |
| `array_filter` の結果がJSONでオブジェクトになる | キーが飛んでいる | `array_values()` |
| `unset()` の後に穴が空く | 同上 | `array_values()` |
| `in_array` で意図しないマッチ | ゆるい比較 | 第3引数に `true` |
| `switch` で意図しないマッチ | ゆるい比較 | `match` を使う |
| `array_map` の引数順を間違える | PHPの不統一 | `array_map(関数, 配列)`、`array_filter(配列, 関数)` |
| `Undefined array key` | キーが存在しない | `??` か `isset()` |
| ソートしたら連想配列のキーが消えた | `sort()` を使った | `asort()` / `uasort()` |

---

## 🤖 AIに聞いてみよう

### ① JavaScript の配列メソッドとの対応表を作らせる

```text
JavaScript の配列メソッドに慣れています。PHP で同じことをする方法を知りたいです。

以下のそれぞれについて、PHP での書き方を教えてください。
引数の順番にも注意して、実際に動くコード例を付けてください。

1. arr.map(x => x * 2)
2. arr.filter(x => x > 0)
3. arr.reduce((s, x) => s + x, 0)
4. arr.find(x => x.id === 5)
5. arr.some(x => x.done)
6. arr.every(x => x.done)
7. arr.includes(x)
8. arr.flat()
9. [...new Set(arr)]（重複除去）
10. Object.entries(obj)

PHP に対応する関数がないものは、「自作する場合の実装」も示してください。
また、PHPで特に注意すべき点（キーの保持、引数順など）を明記してください。
```

> 💡 **この対応表は、第3部の間ずっと使えます。** 印刷して手元に置くレベルで有用です。

### ② 配列の罠を体験させる

```text
PHP の配列でハマりやすい挙動を、実際に手を動かして理解したいです。

以下を含む、1ファイルで実行できる PHP スクリプトを作ってください。

- foreach の参照（&）と unset 忘れによるバグ
- array_filter 後のキーの飛び
- unset 後の穴と json_encode の結果の違い
- in_array のゆるい比較
- sort と asort の違い

各ケースについて、「何が起きるか」「なぜそうなるか」「正しい書き方」を
コメントで説明してください。
```

---

## 🔧 やってみよう（演習）

**AIに頼らず、自力でやってください。**

### 演習1（必須）

以下の出力を予想してから、実行して確認してください。

```php
<?php
$a = [1, 2, 3];
foreach ($a as &$v) {}
foreach ($a as $v) {}
print_r($a);

$b = array_filter([1, 2, 3, 4], fn($n) => $n % 2 === 0);
echo json_encode($b);
echo json_encode(array_values($b));

$c = ["a", "b", "c"];
unset($c[1]);
echo json_encode($c);

var_dump(in_array("1", [1, 2, 3]));
var_dump(in_array("1", [1, 2, 3], true));

$d = ["c" => 3, "a" => 1, "b" => 2];
sort($d);
print_r($d);
```

<details>
<summary>答えを見る</summary>

```
Array ( [0] => 1 [1] => 2 [2] => 2 )   ← 参照が残った有名なバグ
{"1":2,"3":4}                          ← キーが飛んでオブジェクトになる
[2,4]                                  ← array_values で配列に戻る
{"0":"a","2":"c"}                      ← unset で穴が空く
bool(true)                             ← "1" == 1
bool(false)                            ← 厳密比較
Array ( [0] => 1 [1] => 2 [2] => 3 )   ← sort でキーが消える
```

</details>

### 演習2（必須）

`$menu` を使って、以下を実装してください。**すべて配列関数を使ってください（`foreach` の直書きは最小限に）。**

- [ ] 500円以下の商品名を「、」区切りの文字列にする
- [ ] カテゴリ別の合計金額を連想配列で返す（`["coffee" => 1200, ...]`）
- [ ] 価格の高い順に並べ、上位3件の `["name" => ..., "price" => ...]` を返す
- [ ] 売り切れの商品IDだけを配列で返す
- [ ] 商品名をキー、価格を値とする連想配列を作る（`array_column` を使う）

### 演習3（挑戦）

**注文データの集計**を実装してください。

```php
<?php
declare(strict_types=1);

$orders = [
    ["id" => 1, "date" => "2026-04-01", "customer" => "山田",
     "items" => [["name" => "ドリップ", "price" => 600, "qty" => 2],
                 ["name" => "ケーキ",   "price" => 550, "qty" => 1]]],
    ["id" => 2, "date" => "2026-04-01", "customer" => "佐藤",
     "items" => [["name" => "ラテ", "price" => 600, "qty" => 1]]],
    ["id" => 3, "date" => "2026-04-02", "customer" => "山田",
     "items" => [["name" => "ドリップ", "price" => 600, "qty" => 1],
                 ["name" => "ラテ",     "price" => 600, "qty" => 2]]],
];

// ① 注文1件の合計金額を返す
function order_total(array $order): int { }

// ② 日付ごとの売上（["2026-04-01" => 2350, ...]）
function sales_by_date(array $orders): array { }

// ③ 商品ごとの販売数を、多い順に返す（[["name" => "ラテ", "count" => 3], ...]）
function item_ranking(array $orders): array { }

// ④ 顧客ごとの購入回数と総額（["山田" => ["count" => 2, "total" => 3350], ...]）
function customer_summary(array $orders): array { }

// ⑤ 最も売れた商品名を返す
function best_seller(array $orders): string { }
```

> 💡 ヒント：`array_merge(...array_column($orders, "items"))` で、全注文の商品を1つの配列にまとめられます。
> `...` はスプレッド演算子です（PHP 5.6以降）。

---

## ✅ 章末チェック

- [ ] `elseif`（1語）を使う
- [ ] `match` が `switch` より安全な理由を言える
- [ ] `foreach` の `&` の後に `unset` が必要な理由を説明できる
- [ ] `array_filter` の後に `array_values()` が必要な理由を言える
- [ ] `in_array` の第3引数の意味を知っている
- [ ] `array_map` と `array_filter` の引数順の違いを知っている
- [ ] `array_column` を使える
- [ ] `sort` / `asort` / `usort` を使い分けられる
- [ ] JavaScript の配列メソッドとの対応が頭に入った

---

**前 → [3-2 変数・型・演算子](03-02-variables.md)　｜　次 → [3-4 関数と組み込み関数の調べ方](03-04-functions.md)**
