# 2-4 配列とオブジェクト

> 🎯 **このレッスンのゴール**
> - 配列とオブジェクトを使い分けられる
> - 分割代入とスプレッド構文を使える
> - 「参照渡し」の落とし穴を理解し、事故を防げる
> - データ構造を自分で設計できる

所要 150分 / 難度 🟡
完成コード: [`code/02-04/`](../code/02-04/)

---

## 📖 データを「まとめて持つ」2つの方法

| | 配列 | オブジェクト |
| --- | --- | --- |
| 書き方 | `[1, 2, 3]` | `{ name: "太郎", age: 20 }` |
| 取り出し方 | 番号（添字） | 名前（キー） |
| 順序 | **ある** | 基本的に気にしない |
| 向いているもの | **同じ種類**のものの集まり | **1つのもの**の複数の属性 |
| 例 | 商品リスト、ユーザー一覧 | 1人のユーザー、1つの商品 |

**組み合わせて使うのが普通です。**

```javascript
// 「商品オブジェクト」の「配列」
const menu = [
  { id: 1, name: "ハンドドリップ", price: 600 },
  { id: 2, name: "カフェラテ",     price: 600 },
  { id: 3, name: "本日のケーキ",   price: 550 },
];
```

**これが、Webアプリで最もよく使うデータの形**です。サーバーから受け取るデータも、ほぼこの形です。

---

## ✍️ 手を動かす① ─ 配列の基本

```javascript
const fruits = ["りんご", "みかん", "ぶどう"];

// 取り出す
console.log(fruits[0]);             // りんご（添字は0から）
console.log(fruits[fruits.length-1]); // ぶどう（最後）
console.log(fruits.at(-1));         // ぶどう（新しい書き方。こちらが楽）

// 長さ
console.log(fruits.length);         // 3

// 追加・削除
fruits.push("もも");        // 末尾に追加 → ["りんご","みかん","ぶどう","もも"]
fruits.pop();               // 末尾を削除 → "もも" が返る
fruits.unshift("いちご");   // 先頭に追加
fruits.shift();             // 先頭を削除

// 探す
console.log(fruits.includes("みかん"));  // true
console.log(fruits.indexOf("みかん"));   // 1（無ければ -1）

// 変換
console.log(fruits.join("、"));   // "りんご、みかん、ぶどう"
console.log("a,b,c".split(","));  // ["a", "b", "c"]
```

### 元を変えるメソッド / 変えないメソッド

**これが最重要の区別です。**

| 元の配列を**変える**（破壊的） | 元の配列を**変えない**（非破壊的） |
| --- | --- |
| `push` `pop` `shift` `unshift` | `concat` `slice` |
| `splice` `sort` `reverse` | `map` `filter` `join` |
| `fill` | `toSorted` `toReversed`（新しい） |

```javascript
const a = [3, 1, 2];

const b = a.slice();      // コピーを作る（a は変わらない）
const c = a.sort();       // ❌ a 自身が並び替わる！c と a は同じもの

console.log(a);   // [1, 2, 3] ← 元が変わってしまった
```

> ⚠️ **`sort()` と `reverse()` は元の配列を変えます。**
> 「並び替えて表示しただけなのに、元データの順序が変わった」というバグの原因です。
>
> ```javascript
> const sorted = [...items].sort((a, b) => a.price - b.price);  // ✅ コピーしてから
> ```

### `sort()` の落とし穴

```javascript
const nums = [10, 9, 100, 1];
nums.sort();
console.log(nums);   // [1, 10, 100, 9] 😱
```

**`sort()` は初期状態では、値を文字列として比較します。** `"10" < "9"` なので、この順序になります。

```javascript
// ✅ 数値として並べる
nums.sort((a, b) => a - b);   // 昇順 [1, 9, 10, 100]
nums.sort((a, b) => b - a);   // 降順 [100, 10, 9, 1]

// ✅ 文字列を並べる（日本語対応）
names.sort((a, b) => a.localeCompare(b, "ja"));

// ✅ オブジェクトの配列を、あるキーで並べる
menu.sort((a, b) => a.price - b.price);
```

---

## ✍️ 手を動かす② ─ オブジェクトの基本

```javascript
const user = {
  name: "山田太郎",
  age: 20,
  email: "taro@example.com",
  isMember: true,
};

// 取り出す
console.log(user.name);       // ドット記法（基本こちら）
console.log(user["name"]);    // ブラケット記法

// 変数でキーを指定したいときは、ブラケット記法しか使えない
const key = "email";
console.log(user[key]);       // taro@example.com
console.log(user.key);        // undefined（"key" というキーを探してしまう）

// 追加・変更・削除
user.tel = "090-1234-5678";   // 追加
user.age = 21;                // 変更
delete user.email;            // 削除

// 存在確認
console.log("name" in user);              // true
console.log(user.name !== undefined);     // true
console.log(user.hoge?.fuga);             // undefined（エラーにならない）
```

### 入れ子（ネスト）

```javascript
const order = {
  id: 1001,
  customer: {
    name: "山田太郎",
    address: {
      zip: "123-4567",
      city: "東京都〇〇区",
    },
  },
  items: [
    { name: "ハンドドリップ", price: 600, quantity: 2 },
    { name: "カフェラテ",     price: 600, quantity: 1 },
  ],
};

console.log(order.customer.address.city);   // 東京都〇〇区
console.log(order.items[0].name);           // ハンドドリップ
console.log(order.items.length);            // 2
```

> ⚠️ **途中が存在しないとエラーになります。**
>
> ```javascript
> console.log(order.shipping.date);    // ❌ TypeError: Cannot read properties of undefined
> console.log(order.shipping?.date);   // ✅ undefined（オプショナルチェーン）
> ```
>
> **サーバーから受け取ったデータには、必ず `?.` を使ってください。** 項目が欠けていることは日常的にあります。

### キーと値の一覧

```javascript
const user = { name: "太郎", age: 20 };

console.log(Object.keys(user));    // ["name", "age"]
console.log(Object.values(user));  // ["太郎", 20]
console.log(Object.entries(user)); // [["name","太郎"], ["age",20]]

// 全部のキーと値を回す
for (const [key, value] of Object.entries(user)) {
  console.log(`${key}: ${value}`);
}
```

---

## ✍️ 手を動かす③ ─ 分割代入

**オブジェクトや配列から、必要な値だけ取り出す書き方**です。

```javascript
const user = { name: "太郎", age: 20, email: "a@b.com" };

// 従来
const name = user.name;
const age = user.age;

// 分割代入
const { name, age } = user;

// 別名を付ける
const { name: userName } = user;   // userName に入る

// デフォルト値
const { tel = "未登録" } = user;   // tel が無ければ "未登録"

// 残りをまとめる
const { name, ...rest } = user;
console.log(rest);   // { age: 20, email: "a@b.com" }
```

### 配列でも使える

```javascript
const [first, second] = ["A", "B", "C"];
console.log(first, second);   // A B

// 飛ばす
const [, , third] = ["A", "B", "C"];
console.log(third);   // C

// 残りをまとめる
const [head, ...tail] = [1, 2, 3, 4];
console.log(head, tail);   // 1 [2, 3, 4]

// 値の入れ替え（一時変数が不要）
let a = 1, b = 2;
[a, b] = [b, a];
console.log(a, b);   // 2 1
```

### 関数の引数で使うと強力

```javascript
// ❌ 引数の順番を覚えないといけない
const createUser = (name, age, email, isMember) => { };
createUser("太郎", 20, "a@b.com", true);   // 順番を間違えやすい

// ✅ オブジェクトで受け取り、分割代入する
const createUser = ({ name, age, email, isMember = false }) => {
  console.log(name, age, email, isMember);
};

createUser({ email: "a@b.com", name: "太郎", age: 20 });   // 順番は自由
```

> 💡 **引数が3つを超えたら、オブジェクトで受け取る**のが実務での定石です。
> 呼び出し側のコードが自己説明的になり、順番の間違いもなくなります。

---

## ✍️ 手を動かす④ ─ スプレッド構文（`...`）

**中身を展開する**演算子です。

```javascript
// 配列のコピー
const a = [1, 2, 3];
const b = [...a];           // 新しい配列（a とは別物）

// 配列の結合
const c = [...a, 4, 5];     // [1, 2, 3, 4, 5]
const d = [...a, ...b];

// オブジェクトのコピー
const user = { name: "太郎", age: 20 };
const copy = { ...user };

// オブジェクトの結合・上書き
const updated = { ...user, age: 21 };        // age だけ変えた新しいオブジェクト
const merged = { ...defaults, ...userInput }; // 後ろが優先される

// 関数の引数に展開
const nums = [3, 1, 4];
console.log(Math.max(...nums));   // 4
```

> 💡 **`{ ...user, age: 21 }` は、実務で最頻出のパターン**です。
> 「元のデータを変えずに、一部だけ変えた新しいデータを作る」。React などのフレームワークでも基本になります。

---

## ✍️ 手を動かす⑤ ─ 参照渡しの落とし穴（最重要）

**これを知らないと、原因不明のバグに何時間も溶かします。**

### 値渡しと参照渡し

```javascript
// プリミティブ型（数値・文字列・真偽値）は「値」がコピーされる
let a = 1;
let b = a;
b = 2;
console.log(a);   // 1 ← 影響なし

// オブジェクト・配列は「参照（場所）」がコピーされる
let x = { n: 1 };
let y = x;
y.n = 2;
console.log(x.n);   // 2 😱 ← 影響する！
```

> 🆘 **ここで詰まったら**（触っていないはずの変数が、なぜか書き換わっている）
> - **これがまさに参照渡しのバグ**です。「コピーしたつもり」のオブジェクト/配列が、実は**同じ中身を指している**ために起きます
> - **対処**：コピーして別物にしたいときは `const y = { ...x }`（オブジェクト）や `const arr2 = [...arr]`（配列）で**新しい箱**を作る。※これは1階層だけの浅いコピーである点も頭の隅に
> - **原因不明のバグでAIに聞くとき**：
>   「あるオブジェクト（配列）を変更したら、別の変数まで一緒に変わってしまいます。参照渡しが原因かもしれません。私のコードのどこで参照を共有しているか教えてください（コードを貼る）」

```
値渡し                       参照渡し
┌─────┐  ┌─────┐            ┌─────┐  ┌─────┐
│ a=1 │  │ b=1 │            │  x  │  │  y  │
└─────┘  └─────┘            └──┬──┘  └──┬──┘
 別の箱                        └────┬───┘
                                    ▼
                              ┌───────────┐
                              │ { n: 1 }  │  同じ箱を指している
                              └───────────┘
```

### 実際に起きる事故

```javascript
const original = [
  { name: "太郎", age: 20 },
  { name: "花子", age: 22 },
];

// 「コピーして年齢を+1したい」
const copy = original;         // ❌ コピーではない
copy[0].age = 21;

console.log(original[0].age);  // 21 😱 元も変わってしまった
```

### 対処法

```javascript
// ① 浅いコピー（1段目だけコピー）
const copy1 = [...original];
const copy2 = { ...user };
const copy3 = original.slice();
const copy4 = Object.assign({}, user);

// ⚠️ ただし、入れ子の中身は共有されたまま
const shallow = [...original];
shallow[0].age = 99;
console.log(original[0].age);   // 99 😱 まだ影響する

// ② 深いコピー（完全に別物にする）
const deep = structuredClone(original);   // ✅ モダンブラウザで使える
deep[0].age = 99;
console.log(original[0].age);   // 20 ✅ 影響しない

// ③ 古い方法（JSON経由。関数やDateは失われる）
const deep2 = JSON.parse(JSON.stringify(original));
```

> 💡 **判断基準**
> - 1階層だけの配列・オブジェクト → **スプレッド構文（`[...arr]` / `{...obj}`）で十分**
> - 入れ子がある → **`structuredClone()`**

> ⚠️ **`structuredClone()` は、関数・DOM要素・Symbol をコピーできません**（エラーになります）。
> 純粋なデータのコピーに使ってください。

### `const` との関係

```javascript
const arr = [1, 2, 3];
arr.push(4);      // ✅ 中身の変更はOK（参照は変わっていない）
arr = [5];        // ❌ 参照の入れ替えはNG
```

**`const` が守るのは「参照」であって「中身」ではありません。** 2-1で学んだこの話が、ここでつながります。

---

## ✍️ 手を動かす⑥ ─ 実践：メニューデータを設計する

`js/app.js` に書いてください（📋 完成形は `code/02-04/js/app.js`）。

```javascript
// ==========================================================
// メニューデータ
// ==========================================================

const MENU = [
  { id: "drip",  name: "ハンドドリップ", price: 600, category: "coffee", tags: ["おすすめ", "ホット"], soldOut: false },
  { id: "latte", name: "カフェラテ",     price: 600, category: "coffee", tags: ["ホット", "アイス"],   soldOut: false },
  { id: "cake",  name: "本日のケーキ",   price: 550, category: "food",   tags: ["数量限定"],          soldOut: true  },
  { id: "tea",   name: "季節のお茶",     price: 500, category: "tea",    tags: ["ホット"],            soldOut: false },
  { id: "toast", name: "厚切りトースト", price: 450, category: "food",   tags: [],                    soldOut: false },
];

// ==========================================================
// カート
// ==========================================================

// カートの中身（{ id, quantity } の配列）
let cart = [];

/**
 * カートに商品を追加する
 * すでに入っていれば数量を増やす
 */
const addToCart = (id, quantity = 1) => {
  const item = MENU.find((m) => m.id === id);

  if (!item) {
    console.error(`商品が見つかりません: ${id}`);
    return;
  }
  if (item.soldOut) {
    console.warn(`${item.name} は売り切れです`);
    return;
  }

  const existing = cart.find((c) => c.id === id);
  if (existing) {
    // 元の配列を書き換えず、新しい配列を作る
    cart = cart.map((c) =>
      c.id === id ? { ...c, quantity: c.quantity + quantity } : c
    );
  } else {
    cart = [...cart, { id, quantity }];
  }
};

/**
 * カートから削除する
 */
const removeFromCart = (id) => {
  cart = cart.filter((c) => c.id !== id);
};

/**
 * カートの中身を、商品情報つきで取得する
 */
const getCartDetail = () => {
  return cart.map((c) => {
    const item = MENU.find((m) => m.id === c.id);
    return {
      ...item,
      quantity: c.quantity,
      subtotal: item.price * c.quantity,
    };
  });
};

/**
 * 合計金額
 */
const getCartTotal = () => {
  const detail = getCartDetail();
  let sum = 0;
  for (const d of detail) {
    sum += d.subtotal;
  }
  return sum;
};

// ==========================================================
// 動作確認
// ==========================================================

addToCart("drip", 2);
addToCart("latte");
addToCart("drip");        // 既存なので数量が増える
addToCart("cake");        // 売り切れ → 警告
addToCart("unknown");     // 存在しない → エラー

console.table(getCartDetail());
console.log("合計:", getCartTotal().toLocaleString() + "円");

removeFromCart("latte");
console.table(getCartDetail());
```

**この設計のポイント**

| 工夫 | 理由 |
| --- | --- |
| `MENU` は `const` で、変更しない | マスターデータは不変にする |
| `cart` は `{id, quantity}` だけ持つ | 商品情報を二重に持たない（**正規化**） |
| `cart = cart.map(...)` で作り直す | 元の配列を破壊しない |
| `getCartDetail()` で結合する | 表示に必要な形は、都度組み立てる |
| 売り切れ・不存在を早期リターン | 異常系を先に弾く |

> 💡 **「カートに商品名と価格をコピーして持たない」**のが重要です。
> コピーすると、価格改定のときにカートの中だけ古い価格が残ります。
> **IDだけ持ち、表示時にマスターから引く**——これがデータ設計の基本です。第4部（DB設計）でもう一度出てきます。

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| コピーしたはずが元も変わる | 参照渡し | スプレッド構文か `structuredClone()` |
| スプレッドしたのに入れ子が共有される | 浅いコピー | `structuredClone()` |
| `sort()` で数値の順序がおかしい | 文字列として比較されている | `(a,b) => a-b` を渡す |
| `sort()` すると元の配列も変わる | 破壊的メソッド | `[...arr].sort()` |
| `Cannot read properties of undefined` | 入れ子の途中が存在しない | `?.` を使う |
| `user.key` が undefined | 変数をキーに使うにはブラケット記法 | `user[key]` |
| 配列の最後が取れない | `arr[arr.length]` は範囲外 | `arr.at(-1)` |

---

## 🤖 AIに聞いてみよう

### ① データ構造を設計させる

```text
JavaScript でカフェの注文管理データを設計しています。

【必要な情報】
- メニュー（商品名、価格、カテゴリ、売り切れフラグ、タグ）
- 注文（注文番号、注文日時、顧客名、商品と数量、合計金額、ステータス）
- 顧客（名前、メール、電話、会員かどうか、ポイント）

1. どんなデータ構造にすべきか提案してください（配列とオブジェクトの組み合わせ）
2. 「同じ情報を2か所に持たない」観点で、注意すべき点を教えてください
3. この設計で困りそうなケースを3つ挙げてください

コードは最小限のサンプルデータだけで構いません。設計の考え方を中心に。
```

### ② 参照渡しの事故を体験する

```text
JavaScript の「参照渡し」による事故を、手を動かして理解したいです。

以下を含む、実行して確認できる1ファイルのHTMLを作ってください。

- プリミティブ型のコピー（影響しない例）
- オブジェクトのコピー（影響する例）
- スプレッド構文での浅いコピー（1階層は解決、入れ子は未解決）
- structuredClone での深いコピー（完全に解決）

各ケースで、console.log の出力に「なぜそうなるか」のコメントを添えてください。
```

> 💡 これはAIに書かせてよい「学習用の実験環境」です。ただし、**1行ずつ読んでから実行**してください。

---

## 🔧 やってみよう（演習）

**AIに頼らず、自力でやってください。**

### 演習1（必須）

以下の出力を予想してから、実行して確認してください。

```javascript
const a = [1, 2, 3];
const b = a;
b.push(4);
console.log(a);

const c = { x: 1, nested: { y: 2 } };
const d = { ...c };
d.x = 99;
d.nested.y = 99;
console.log(c.x, c.nested.y);

const e = [10, 9, 100, 1];
console.log([...e].sort());
console.log([...e].sort((p, q) => p - q));
console.log(e);
```

<details>
<summary>答えを見る</summary>

```
[1, 2, 3, 4]        ← b は a と同じ配列を指している
1 99                ← x は独立したが、nested は共有されている（浅いコピー）
[1, 10, 100, 9]     ← 文字列として比較されている
[1, 9, 10, 100]     ← 数値として比較
[10, 9, 100, 1]     ← [...e] でコピーしたので、e 自身は変わっていない
```

</details>

### 演習2（必須）

`MENU` を使って、以下を出力してください。**`for` 文を使って構いません**（`filter` などは次のレッスンで学びます）。

- [ ] 売り切れでない商品の名前だけを、配列で
- [ ] カテゴリが `"coffee"` の商品の合計金額
- [ ] 最も高い商品のオブジェクト
- [ ] `"ホット"` タグが付いている商品の名前
- [ ] カテゴリごとの商品数（`{ coffee: 2, food: 2, tea: 1 }` の形）

<details>
<summary>ヒント</summary>

最後の問題は、空のオブジェクトを用意してループで数えます。

```javascript
const counts = {};
for (const item of MENU) {
  counts[item.category] = (counts[item.category] ?? 0) + 1;
}
```

`?? 0` がないと、最初の1回で `undefined + 1 = NaN` になります。

</details>

### 演習3（挑戦）

**注文履歴データ**を設計し、集計する関数を作ってください。

```javascript
const ORDERS = [
  { id: 1, date: "2026-04-01", customerId: "c1", items: [{ menuId: "drip", quantity: 2 }, { menuId: "cake", quantity: 1 }] },
  { id: 2, date: "2026-04-01", customerId: "c2", items: [{ menuId: "latte", quantity: 1 }] },
  { id: 3, date: "2026-04-02", customerId: "c1", items: [{ menuId: "drip", quantity: 1 }, { menuId: "latte", quantity: 2 }] },
];

const CUSTOMERS = [
  { id: "c1", name: "山田太郎", isMember: true },
  { id: "c2", name: "佐藤花子", isMember: false },
];

// 以下を実装してください

// 1. 注文1件の合計金額を返す
const getOrderTotal = (order) => { };

// 2. 顧客ごとの購入合計金額を返す（{ c1: 3350, c2: 600 } の形）
const getTotalByCustomer = (orders) => { };

// 3. 商品ごとの販売数を、多い順に返す
//    [{ name: "ハンドドリップ", count: 3 }, ...]
const getRanking = (orders) => { };

// 4. 指定日の売上合計を返す
const getDailySales = (orders, date) => { };
```

> 💡 **これは実務でよくある「集計処理」です。** ループとオブジェクトを組み合わせて解いてください。
> 次のレッスン（2-5）で `reduce` を学ぶと、もっと短く書けるようになります。**まずは `for` で解いてみてください。**

---

## ✅ 章末チェック

- [ ] 配列とオブジェクトの使い分けを説明できる
- [ ] 破壊的メソッドと非破壊的メソッドの違いを言える
- [ ] `sort()` の落とし穴と対処を説明できる
- [ ] 分割代入を書ける
- [ ] スプレッド構文で「一部だけ変えた新しいオブジェクト」を作れる
- [ ] 参照渡しで何が起きるか、図で説明できる
- [ ] 浅いコピーと深いコピーの違いを説明できる
- [ ] `?.`（オプショナルチェーン）を使える
- [ ] 「同じ情報を2か所に持たない」設計原則を理解した

---

**前 → [2-3 関数とスコープ](02-03-functions.md)　｜　次 → [2-5 配列メソッド（map / filter / reduce）](02-05-array-methods.md)**
