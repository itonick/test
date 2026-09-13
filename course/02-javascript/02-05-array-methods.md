# 2-5 配列メソッド（map / filter / reduce）

> 🎯 **このレッスンのゴール**
> - `map` / `filter` / `reduce` を使い分けられる
> - `for` 文で書いていた処理を、宣言的に書き直せる
> - メソッドチェーンで、データ加工の流れを表現できる

所要 150分 / 難度 🔴
完成コード: [`code/02-05/`](../code/02-05/)

---

## 📖 「どうやるか」ではなく「何をしたいか」を書く

```javascript
const prices = [600, 600, 550];

// 手続き的：どうやるかを書く
const withTax = [];
for (let i = 0; i < prices.length; i++) {
  withTax.push(Math.floor(prices[i] * 1.1));
}

// 宣言的：何をしたいかを書く
const withTax = prices.map((p) => Math.floor(p * 1.1));
```

**同じ結果ですが、下は「各要素を変換したい」という意図が一目でわかります。**

初学者はここでつまずきますが、**乗り越えると別世界です。** 焦らず、1つずつ確実にいきましょう。

---

## ✍️ 手を動かす① ─ map（変換する）

**すべての要素を変換して、同じ長さの新しい配列を作る。**

```javascript
const nums = [1, 2, 3];

const doubled = nums.map((n) => n * 2);
console.log(doubled);   // [2, 4, 6]
console.log(nums);      // [1, 2, 3] ← 元は変わらない
```

```
[1, 2, 3]
 │  │  │     各要素に関数を適用
 ▼  ▼  ▼
[2, 4, 6]     長さは必ず同じ
```

### オブジェクトの配列で使う

```javascript
const menu = [
  { name: "ハンドドリップ", price: 600 },
  { name: "カフェラテ",     price: 600 },
  { name: "本日のケーキ",   price: 550 },
];

// 名前だけの配列にする
const names = menu.map((item) => item.name);
console.log(names);   // ["ハンドドリップ", "カフェラテ", "本日のケーキ"]

// 税込み価格を追加した新しい配列
const withTax = menu.map((item) => ({
  ...item,
  taxIncluded: Math.floor(item.price * 1.1),
}));

// HTMLの文字列を作る（DOM操作で多用します）
const html = menu.map((item) => `<li>${item.name} ¥${item.price}</li>`).join("");
console.log(html);
```

> 💡 **`map` + `join("")` で HTML を作る**のは、2-6以降で頻繁に使います。今のうちに慣れてください。

### インデックスも使える

```javascript
const ranked = menu.map((item, index) => `${index + 1}位: ${item.name}`);
```

### `map` の返り値を忘れない

```javascript
// ❌ return がないので undefined の配列になる
const bad = nums.map((n) => { n * 2; });
console.log(bad);   // [undefined, undefined, undefined]

// ✅ ブロックを使うなら return が必要
const good = nums.map((n) => { return n * 2; });

// ✅ 1行なら return 不要
const best = nums.map((n) => n * 2);
```

> ⚠️ **`{ }` を書いたら `return` が必要**です。これは頻出のミスです。

---

## ✍️ 手を動かす② ─ filter（絞り込む）

**条件に合う要素だけを取り出して、新しい配列を作る。**

```javascript
const nums = [1, 2, 3, 4, 5, 6];

const evens = nums.filter((n) => n % 2 === 0);
console.log(evens);   // [2, 4, 6]
```

```
[1, 2, 3, 4, 5, 6]
 ✗  ✓  ✗  ✓  ✗  ✓    条件が true のものだけ残る
    [2,    4,    6]    長さは減る（増えない）
```

### 実用例

```javascript
const menu = [
  { name: "ハンドドリップ", price: 600, category: "coffee", soldOut: false },
  { name: "カフェラテ",     price: 600, category: "coffee", soldOut: false },
  { name: "本日のケーキ",   price: 550, category: "food",   soldOut: true  },
  { name: "厚切りトースト", price: 450, category: "food",   soldOut: false },
];

// 売り切れでないもの
const available = menu.filter((item) => !item.soldOut);

// コーヒーだけ
const coffees = menu.filter((item) => item.category === "coffee");

// 500円以下
const cheap = menu.filter((item) => item.price <= 500);

// 複数条件
const cheapFood = menu.filter((item) => item.category === "food" && item.price <= 500);

// 検索（部分一致・大文字小文字を無視）
const keyword = "ラテ";
const found = menu.filter((item) => item.name.includes(keyword));

// null を取り除く定番
const errors = [null, "エラー1", null, "エラー2"];
const realErrors = errors.filter((e) => e !== null);
```

> ⚠️ **`filter` のコールバックは真偽値を返します。** `map` のように値を返しても、その値の truthy / falsy で判定されてしまいます。
>
> ```javascript
> menu.filter((item) => item.price);   // 価格が 0 の商品だけ除外される（意図と違うかも）
> ```

---

## ✍️ 手を動かす③ ─ reduce（1つにまとめる）

**最も難しく、最も強力なメソッドです。**

```javascript
const nums = [1, 2, 3, 4];

const sum = nums.reduce((acc, cur) => acc + cur, 0);
console.log(sum);   // 10
```

```
reduce((acc, cur) => 新しいacc, 初期値)
         ↑     ↑                  ↑
      蓄積値  現在の要素      accの最初の値
```

### 動きを1回ずつ追う

```javascript
[1, 2, 3, 4].reduce((acc, cur) => acc + cur, 0)

回  acc  cur  戻り値
1    0    1     1
2    1    2     3
3    3    3     6
4    6    4    10   ← 最終結果
```

> 💡 **`acc`（accumulator）は「これまでの結果」、`cur`（current）は「いまの要素」**です。
> 「前回の結果と今回の要素から、新しい結果を作る」を繰り返します。

### 初期値を必ず書く

```javascript
// ❌ 初期値なし → 空配列でエラーになる
[].reduce((acc, cur) => acc + cur);   // TypeError

// ✅ 初期値あり → 空配列でも 0 が返る
[].reduce((acc, cur) => acc + cur, 0);   // 0
```

**初期値は省略できますが、省略しないでください。** 空配列のときにクラッシュします。

### 実用パターン集

```javascript
const cart = [
  { name: "ハンドドリップ", price: 600, quantity: 2 },
  { name: "カフェラテ",     price: 600, quantity: 1 },
  { name: "本日のケーキ",   price: 550, quantity: 2 },
];

// ① 合計金額
const total = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
console.log(total);   // 2900

// ② 個数の合計
const count = cart.reduce((n, item) => n + item.quantity, 0);   // 5

// ③ 最大値を持つ要素
const mostExpensive = cart.reduce((max, item) => item.price > max.price ? item : max);

// ④ カテゴリごとにグループ化（超頻出）
const menu = [
  { name: "ドリップ", category: "coffee" },
  { name: "ラテ",     category: "coffee" },
  { name: "ケーキ",   category: "food" },
];

const grouped = menu.reduce((acc, item) => {
  const key = item.category;
  acc[key] = acc[key] ?? [];   // 無ければ空配列を作る
  acc[key].push(item);
  return acc;                   // ← return を忘れない
}, {});

console.log(grouped);
// { coffee: [{...}, {...}], food: [{...}] }

// ⑤ 数を数える
const counts = menu.reduce((acc, item) => {
  acc[item.category] = (acc[item.category] ?? 0) + 1;
  return acc;
}, {});
// { coffee: 2, food: 1 }

// ⑥ 配列をIDで引ける形に変換（検索を高速にする）
const byId = menu.reduce((acc, item) => {
  acc[item.id] = item;
  return acc;
}, {});
```

> ⚠️ **`reduce` で最も多いミスは `return` の書き忘れ**です。
> `{ }` を使ったら、必ず `return acc;` を書いてください。忘れると `undefined` が次の `acc` になり、エラーになります。

### `reduce` を使うべきか

**無理に `reduce` を使わないでください。** 読みにくくなるなら `for` 文のほうが良いコードです。

| やりたいこと | 推奨 |
| --- | --- |
| 合計・平均 | `reduce` |
| グループ化 | `reduce` |
| 数を数える | `reduce` |
| 複雑な条件分岐を含む集計 | **`for...of`** のほうが読みやすいことが多い |

---

## ✍️ 手を動かす④ ─ その他の重要メソッド

```javascript
const menu = [
  { id: "drip",  name: "ハンドドリップ", price: 600, soldOut: false },
  { id: "latte", name: "カフェラテ",     price: 600, soldOut: false },
  { id: "cake",  name: "本日のケーキ",   price: 550, soldOut: true  },
];

// find：条件に合う「最初の1つ」を返す（無ければ undefined）
const item = menu.find((m) => m.id === "latte");

// findIndex：その位置を返す（無ければ -1）
const idx = menu.findIndex((m) => m.id === "latte");   // 1

// some：1つでも条件に合えば true
const hasSoldOut = menu.some((m) => m.soldOut);        // true

// every：全部が条件に合えば true
const allAvailable = menu.every((m) => !m.soldOut);    // false

// flat：入れ子の配列を平らにする
console.log([[1, 2], [3, [4]]].flat());     // [1, 2, 3, [4]]
console.log([[1, 2], [3, [4]]].flat(2));    // [1, 2, 3, 4]

// flatMap：map してから flat
const tags = menu.flatMap((m) => m.tags ?? []);
```

### `find` と `filter` の使い分け

```javascript
const one  = menu.find((m) => m.id === "latte");     // オブジェクト or undefined
const many = menu.filter((m) => m.id === "latte");   // 配列（要素0個でも配列）
```

> 💡 **1つだけ欲しいなら `find`。** `filter(...)[0]` と書く人がいますが、`find` のほうが速く、意図も明確です。

---

## ✍️ 手を動かす⑤ ─ メソッドチェーン

**繋げて書くと、データの加工の流れがそのままコードになります。**

```javascript
const result = menu
  .filter((item) => !item.soldOut)          // ① 売り切れを除く
  .filter((item) => item.price <= 600)      // ② 600円以下
  .map((item) => ({                          // ③ 税込み価格を追加
    ...item,
    taxIncluded: Math.floor(item.price * 1.1),
  }))
  .sort((a, b) => a.price - b.price);        // ④ 安い順（※要注意、下記）

console.log(result);
```

> ⚠️ **`sort` は破壊的ですが、`map` が新しい配列を返しているので、この場合は安全です。**
> ただし、チェーンの**最初**に `sort` を書くと元の配列を壊します。
>
> ```javascript
> menu.sort(...).filter(...)      // ❌ menu が並び替わる
> [...menu].sort(...).filter(...) // ✅
> ```

### 読みやすく書くコツ

```javascript
// ❌ 1行に詰め込む
const r = menu.filter(i => !i.soldOut).map(i => i.name).join("、");

// ✅ 1メソッド1行にする
const r = menu
  .filter((item) => !item.soldOut)
  .map((item) => item.name)
  .join("、");
```

**メソッドが3つを超えたら改行**してください。

### 途中結果を確認するテクニック

```javascript
const result = menu
  .filter((item) => !item.soldOut)
  .map((item) => {
    console.log("map の入力:", item);   // 一時的に差し込む
    return item.name;
  });
```

または、途中で分割します。

```javascript
const available = menu.filter((item) => !item.soldOut);
console.log("絞り込み後:", available);

const names = available.map((item) => item.name);
console.log("変換後:", names);
```

> 💡 **チェーンが動かないときは、いったん分割してください。** どこで想定と違うかがすぐわかります。

---

## ✍️ 手を動かす⑥ ─ 実践：2-4の演習を書き直す

前回 `for` 文で書いた集計を、配列メソッドで書き直します。

```javascript
const MENU = [
  { id: "drip",  name: "ハンドドリップ", price: 600, category: "coffee", tags: ["おすすめ","ホット"], soldOut: false },
  { id: "latte", name: "カフェラテ",     price: 600, category: "coffee", tags: ["ホット","アイス"],   soldOut: false },
  { id: "cake",  name: "本日のケーキ",   price: 550, category: "food",   tags: ["数量限定"],         soldOut: true  },
  { id: "tea",   name: "季節のお茶",     price: 500, category: "tea",    tags: ["ホット"],           soldOut: false },
  { id: "toast", name: "厚切りトースト", price: 450, category: "food",   tags: [],                   soldOut: false },
];

// ① 売り切れでない商品の名前
const availableNames = MENU
  .filter((m) => !m.soldOut)
  .map((m) => m.name);

// ② coffee カテゴリの合計金額
const coffeeTotal = MENU
  .filter((m) => m.category === "coffee")
  .reduce((sum, m) => sum + m.price, 0);

// ③ 最も高い商品
const mostExpensive = MENU.reduce((max, m) => (m.price > max.price ? m : max));

// ④ "ホット" タグが付いている商品の名前
const hotItems = MENU
  .filter((m) => m.tags.includes("ホット"))
  .map((m) => m.name);

// ⑤ カテゴリごとの商品数
const countByCategory = MENU.reduce((acc, m) => {
  acc[m.category] = (acc[m.category] ?? 0) + 1;
  return acc;
}, {});

// ⑥ カテゴリごとにグループ化
const byCategory = MENU.reduce((acc, m) => {
  (acc[m.category] ??= []).push(m);
  return acc;
}, {});

// ⑦ 価格の平均
const avg = MENU.reduce((sum, m) => sum + m.price, 0) / MENU.length;

console.log({ availableNames, coffeeTotal, mostExpensive, hotItems, countByCategory, avg });
console.log(byCategory);
```

> 💡 `(acc[key] ??= []).push(m)` は `acc[key] = acc[key] ?? []; acc[key].push(m);` の短縮形です。
> **読みにくいと感じたら、無理に使わなくて構いません。** 短さより読みやすさが優先です。

### HTML生成の練習（2-6への橋渡し）

```javascript
const renderMenuList = (items) => items
  .filter((item) => !item.soldOut)
  .map((item) => `
    <li class="menu-item">
      <h3>${item.name}</h3>
      <p class="price">¥${item.price.toLocaleString()}</p>
      ${item.tags.map((t) => `<span class="tag">${t}</span>`).join("")}
    </li>
  `)
  .join("");

console.log(renderMenuList(MENU));
```

**この形が、次のレッスンで画面に表示するときの基本パターン**になります。

> ⚠️ **ユーザーが入力した文字列をこの方法でHTMLにすると、XSS の危険があります。**
> 対策は 2-6 と 3-7 で扱います。いまは「固定データだから安全」と理解しておいてください。

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| `map` の結果が `[undefined, ...]` | `{ }` を使って `return` を書き忘れた | `return` を書くか、`{ }` を外す |
| `reduce` が `undefined` になる | `return acc` を忘れた | 必ず `return` |
| `reduce` で空配列がエラー | 初期値を省略した | 初期値を必ず書く |
| `filter` で意図しないものが残る | 真偽値以外を返している | `=== ` などで明示的に真偽値にする |
| チェーンで元の配列が変わる | `sort` / `reverse` が破壊的 | `[...arr].sort()` |
| `find` が `undefined` | 条件に合うものが無い | 使う前に存在チェック |
| チェーンが複雑で読めない | 詰め込みすぎ | 途中で変数に分ける |

---

## 🤖 AIに聞いてみよう

### ① for 文からの書き換えを検証させる

```text
以下の for 文を、配列メソッド（map / filter / reduce）で書き直しました。

【元のコード】
（for 文を貼る）

【書き直したコード】
（自分が書いたコードを貼る）

1. 書き換えは正しくできていますか？挙動に違いはありますか？
2. より読みやすい書き方はありますか？
3. この処理は、そもそも配列メソッドにすべきですか？for のままのほうが
   読みやすいケースではありませんか？

判断の理由も含めて教えてください。
```

> 💡 **3番目の質問が重要です。** 何でもメソッドチェーンにするのが正解ではありません。

### ② 練習問題を出させる

```text
JavaScript の配列メソッド（map / filter / reduce / find / some / every）を
学びました。実務で使えるレベルにしたいです。

以下の条件で練習問題を8問作ってください。

- 題材は「ECサイトの商品データ」または「社員名簿」など実務的なもの
- サンプルデータを最初に1つ提示し、全問でそれを使う
- 難易度は易しい順に。後半は複数のメソッドを組み合わせる問題に
- 各問題に「期待する出力」を明記
- 解答はまだ書かないでください

私が回答したら1問ずつ採点し、間違いは「どう考えるべきだったか」を
説明してください。
```

---

## 🔧 やってみよう（演習）

**AIに頼らず、自力でやってください。**

### 演習1（必須）

以下のデータを使って、それぞれを1行（またはチェーン）で書いてください。

```javascript
const students = [
  { name: "山田", score: 82, club: "野球" },
  { name: "佐藤", score: 55, club: "吹奏楽" },
  { name: "鈴木", score: 91, club: "野球" },
  { name: "田中", score: 78, club: "美術" },
  { name: "高橋", score: 45, club: "吹奏楽" },
];

// ① 合格者（60点以上）の名前の配列
// ② 全員の平均点
// ③ 最高点の生徒のオブジェクト
// ④ 部活ごとの人数（{ 野球: 2, 吹奏楽: 2, 美術: 1 }）
// ⑤ 1人でも不合格者がいるか（true / false）
// ⑥ 点数の高い順に並べた名前の配列（元データは変えない）
// ⑦ 「山田(82点)、鈴木(91点)」のような文字列（合格者のみ、点数順）
```

<details>
<summary>答えを見る</summary>

```javascript
// ①
const passed = students.filter((s) => s.score >= 60).map((s) => s.name);

// ②
const avg = students.reduce((sum, s) => sum + s.score, 0) / students.length;

// ③
const top = students.reduce((max, s) => (s.score > max.score ? s : max));

// ④
const byClub = students.reduce((acc, s) => {
  acc[s.club] = (acc[s.club] ?? 0) + 1;
  return acc;
}, {});

// ⑤
const hasFailed = students.some((s) => s.score < 60);

// ⑥
const rankedNames = [...students]
  .sort((a, b) => b.score - a.score)
  .map((s) => s.name);

// ⑦
const text = students
  .filter((s) => s.score >= 60)
  .sort((a, b) => b.score - a.score)
  .map((s) => `${s.name}(${s.score}点)`)
  .join("、");
```

**⑥のポイント**：`[...students]` でコピーしてから `sort` する。
これをしないと、元の `students` の順序が変わってしまいます。

</details>

### 演習2（必須）

以下のコードのバグを、それぞれ指摘して直してください。

```javascript
// ①
const doubled = [1, 2, 3].map((n) => { n * 2; });

// ②
const grouped = items.reduce((acc, item) => {
  acc[item.type] = item;
}, {});

// ③
const total = [].reduce((sum, n) => sum + n);

// ④
const sorted = menu.sort((a, b) => a.price - b.price);
const cheapest = menu[0];   // menu の順序が変わっている

// ⑤
const found = users.filter((u) => u.id === targetId)[0];
```

<details>
<summary>答えを見る</summary>

① **`return` がない** → `[undefined, undefined, undefined]` になる
```javascript
const doubled = [1, 2, 3].map((n) => n * 2);
```

② **`return acc` がない** → 2回目で `acc` が `undefined` になりエラー
```javascript
const grouped = items.reduce((acc, item) => {
  acc[item.type] = item;
  return acc;
}, {});
```

③ **初期値がない** → 空配列で `TypeError`
```javascript
const total = [].reduce((sum, n) => sum + n, 0);
```

④ **`sort` が破壊的** → 元の `menu` の順序が変わっている
```javascript
const sorted = [...menu].sort((a, b) => a.price - b.price);
const cheapest = sorted[0];
```

⑤ **`filter` + `[0]` は非効率で、意図も不明確** → `find` を使う
```javascript
const found = users.find((u) => u.id === targetId);
```

</details>

### 演習3（挑戦）

**注文データの分析関数**を、配列メソッドだけで書いてください（`for` 文禁止）。

```javascript
const ORDERS = [
  { id: 1, date: "2026-04-01", customer: "山田", items: [{ name: "ドリップ", price: 600, qty: 2 }, { name: "ケーキ", price: 550, qty: 1 }] },
  { id: 2, date: "2026-04-01", customer: "佐藤", items: [{ name: "ラテ", price: 600, qty: 1 }] },
  { id: 3, date: "2026-04-02", customer: "山田", items: [{ name: "ドリップ", price: 600, qty: 1 }, { name: "ラテ", price: 600, qty: 2 }] },
  { id: 4, date: "2026-04-02", customer: "鈴木", items: [{ name: "ケーキ", price: 550, qty: 3 }] },
];

// ① 全注文の総売上
// ② 日付ごとの売上（{ "2026-04-01": 2350, "2026-04-02": 3450 }）
// ③ 商品ごとの販売数ランキング（多い順の配列）
// ④ 顧客ごとの購入回数と総額
// ⑤ 最も売れた商品名
```

> 💡 ヒント：`flatMap` を使うと、全注文の商品を1つの配列にまとめられます。
>
> ```javascript
> const allItems = ORDERS.flatMap((o) => o.items);
> ```

---

## ✅ 章末チェック

- [ ] `map` は「変換」、長さは変わらないと説明できる
- [ ] `filter` は「絞り込み」、長さは減ると説明できる
- [ ] `reduce` の `acc` と `cur` の意味を言える
- [ ] `reduce` に初期値を必ず書く理由を説明できる
- [ ] `map` / `reduce` で `return` を忘れるとどうなるか知っている
- [ ] `find` と `filter` の使い分けを説明できる
- [ ] `sort` の前に `[...arr]` を書く理由を言える
- [ ] メソッドチェーンを1メソッド1行で書ける
- [ ] `reduce` を無理に使わない判断ができる

---

**前 → [2-4 配列とオブジェクト](02-04-array-object.md)　｜　次 → [2-6 DOM操作で画面を書き換える](02-06-dom.md)**
