# 2-2 条件分岐と繰り返し

> 🎯 **このレッスンのゴール**
> - `if` / `switch` / 三項演算子を使い分けられる
> - `for` / `while` / `for...of` を使い分けられる
> - 早期リターンでネストを浅くできる
> - 無限ループを避けられる

所要 120分 / 難度 🟢
完成コード: [`code/02-02/`](../code/02-02/)

---

## ✍️ 手を動かす① ─ if 文

```javascript
const age = 20;

if (age >= 20) {
  console.log("成人です");
} else if (age >= 13) {
  console.log("中高生です");
} else {
  console.log("子どもです");
}
```

### 条件式で使う演算子

```javascript
a === b    // 等しい（型も）
a !== b    // 等しくない（型も）
a >  b     // より大きい
a >= b     // 以上
a <  b     // より小さい
a <= b     // 以下

a && b     // かつ
a || b     // または
!a         // 否定
```

> 🆘 **ここで詰まったら**（条件がいつも true／false になる、意図と逆に動く）
> - **最頻出の原因**：比較のつもりで `=`（代入）を書いている。`if (x = 5)` は「xに5を代入」で常に真。**比較は必ず `===`**（型も含めて等しい）を使う
> - **その次に多い**：`==` による型のゆるい比較（`0 == ""` が true など）。この教材では**常に `===` / `!==`**。`&&`（かつ）と `||`（または）の取り違えもよく確認する
> - **直らなければ、AIにこう聞く**（条件式とやりたいことを貼る）：
>   「この if の条件が意図どおりに分岐しません。私はこう動いてほしいのですが（説明）、どこが間違っているか教えてください」

### 複数条件の書き方

```javascript
// かつ
if (age >= 20 && hasTicket) {
  console.log("入場できます");
}

// または
if (day === "土" || day === "日") {
  console.log("週末です");
}

// 複雑な条件は、変数に切り出す
const isAdult = age >= 20;
const canEnter = isAdult && hasTicket && !isBanned;

if (canEnter) {
  console.log("入場できます");
}
```

> 💡 **条件が3つ以上になったら、変数に切り出してください。**
> `if (age >= 20 && hasTicket && !isBanned && time < 22)` は読めません。
> 名前を付けることで、条件の意味がコードに残ります。

### 波括弧は省略しない

```javascript
// ❌ 省略すると事故る
if (isValid)
  console.log("OK");
  console.log("これは常に実行される");  // if の外！

// ✅ 必ず { } を書く
if (isValid) {
  console.log("OK");
  console.log("これも条件内");
}
```

**1行でも `{ }` を書いてください。** 後から行を追加したときに事故ります。

---

## ✍️ 手を動かす② ─ 三項演算子

```javascript
// if 文
let label;
if (score >= 60) {
  label = "合格";
} else {
  label = "不合格";
}

// 三項演算子（1行で書ける）
const label = score >= 60 ? "合格" : "不合格";
```

```
条件 ? 真のときの値 : 偽のときの値
```

**「値を決めるだけ」の分岐に使います。**

```javascript
// ✅ 良い使い方
const message = count === 0 ? "件数なし" : `${count}件`;
const cls = isActive ? "btn active" : "btn";

// ❌ 悪い使い方（ネストして読めない）
const x = a ? (b ? 1 : 2) : (c ? 3 : 4);

// ❌ 悪い使い方（処理を書く）
isValid ? save() : showError();   // if を使うべき
```

> 💡 **判断基準**：「値を返すなら三項演算子、処理を実行するなら if」。
> ネストしたくなったら、`if` に戻してください。

---

## ✍️ 手を動かす③ ─ switch 文

```javascript
const topic = "reserve";

switch (topic) {
  case "reserve":
    console.log("ご予約について");
    break;
  case "bean":
    console.log("豆の販売について");
    break;
  case "event":
  case "other":                // 複数まとめられる
    console.log("その他のお問い合わせ");
    break;
  default:
    console.log("未選択");
}
```

> ⚠️ **`break` を忘れると、下の case も実行されます（フォールスルー）。**
> これは仕様ですが、意図しない場合はバグになります。**意図的に複数まとめる場合以外は、必ず `break`** を書いてください。

### switch を使うべき場面

| 場面 | 使うもの |
| --- | --- |
| 1つの値を、多数の候補と比較する | **switch** |
| 範囲や複雑な条件で分ける | **if** |
| 分岐が2つだけ | **if** または三項演算子 |

> 💡 分岐が非常に多い場合は、**オブジェクトで置き換える**とすっきりします（2-4で扱います）。
>
> ```javascript
> const labels = {
>   reserve: "ご予約について",
>   bean: "豆の販売について",
>   event: "イベントについて",
> };
> console.log(labels[topic] ?? "未選択");
> ```

---

## ✍️ 手を動かす④ ─ 早期リターンでネストを浅くする

**深いネストは、バグの温床です。**

```javascript
// ❌ ネストが深い（読みにくい）
function checkUser(user) {
  if (user) {
    if (user.isActive) {
      if (user.age >= 20) {
        return "OK";
      } else {
        return "未成年です";
      }
    } else {
      return "退会済みです";
    }
  } else {
    return "ユーザーが存在しません";
  }
}

// ✅ 早期リターン（読みやすい）
function checkUser(user) {
  if (!user) return "ユーザーが存在しません";
  if (!user.isActive) return "退会済みです";
  if (user.age < 20) return "未成年です";
  return "OK";
}
```

**「異常なケースを先に弾いて、正常なケースを最後に書く」** のがコツです。

> 💡 **ネストは2段まで**を目安にしてください。3段以上になったら、早期リターンか関数の切り出しを検討します。
> これは実務でもレビューで指摘される、重要な観点です。

---

## ✍️ 手を動かす⑤ ─ 繰り返し

### for 文

```javascript
for (let i = 0; i < 5; i++) {
  console.log(i);   // 0, 1, 2, 3, 4
}
```

```
for (初期化; 継続条件; 更新) { 処理 }
       ↓        ↓        ↓
    最初に1回  毎回判定  毎回最後に実行
```

> ⚠️ **`i` は `let` で宣言します。** `const` だと `i++` でエラーになります。

### for...of（配列を回す・推奨）

```javascript
const fruits = ["りんご", "みかん", "ぶどう"];

for (const fruit of fruits) {
  console.log(fruit);
}
```

**添字（`i`）が不要なときは、こちらが圧倒的に読みやすいです。**

### インデックスも欲しいとき

```javascript
for (const [index, fruit] of fruits.entries()) {
  console.log(`${index + 1}. ${fruit}`);
}
// 1. りんご
// 2. みかん
// 3. ぶどう
```

### forEach（配列のメソッド）

```javascript
fruits.forEach((fruit, index) => {
  console.log(`${index}: ${fruit}`);
});
```

> ⚠️ **`forEach` の中では `break` できません。** 途中で抜けたい場合は `for...of` を使ってください。

### while 文

```javascript
let count = 0;
while (count < 5) {
  console.log(count);
  count++;      // ← これを忘れると無限ループ
}
```

**回数が決まっていないとき**に使います。

```javascript
// 例：ランダムで6が出るまで振り続ける
let dice = 0;
let times = 0;
while (dice !== 6) {
  dice = Math.floor(Math.random() * 6) + 1;
  times++;
}
console.log(`${times}回で6が出ました`);
```

> ⚠️ **無限ループに注意**
> `while` の条件が永久に真だと、**ブラウザが固まります**。
> - 条件が変化するコードを、ループの中に必ず入れる
> - 固まったら、タブを閉じる（`Ctrl + W`）
>
> 心配なら、安全装置を入れてください。
>
> ```javascript
> let guard = 0;
> while (condition) {
>   if (++guard > 10000) { console.error("無限ループ検出"); break; }
>   // 処理
> }
> ```

### break と continue

```javascript
for (const n of [1, 2, 3, 4, 5]) {
  if (n === 3) continue;   // この回だけスキップ
  if (n === 5) break;      // ループを抜ける
  console.log(n);          // 1, 2, 4
}
```

| キーワード | 意味 |
| --- | --- |
| `break` | **ループを終了**する |
| `continue` | **この回だけスキップ**して次へ |

### 使い分けまとめ

| やりたいこと | 使うもの |
| --- | --- |
| 配列の全要素を処理 | `for...of` |
| 配列の全要素 + インデックス | `forEach` または `for...of` + `entries()` |
| 回数が決まっている | `for` |
| 回数が決まっていない | `while` |
| 途中で抜けたい | `for` / `for...of`（`forEach` は不可） |
| 変換して新しい配列を作る | `map`（2-5で学習） |
| 条件で絞り込む | `filter`（2-5で学習） |

---

## ✍️ 手を動かす⑥ ─ 実践：営業時間の判定

`js/app.js` に書いて、Console で確認してください。

> 🖊 **手で打ってください。** 完成形は `code/02-02/js/app.js`（📋 コピペ可）。

```javascript
// カフェの営業判定
// 営業時間: 8:00 - 18:00 / 定休日: 水曜

function getShopStatus(date) {
  const day = date.getDay();     // 0=日, 1=月, ... 6=土
  const hour = date.getHours();

  // 早期リターンで、閉まっているケースを先に弾く
  if (day === 3) return "本日は定休日です";
  if (hour < 8)  return `本日は8:00から営業します（あと${8 - hour}時間）`;
  if (hour >= 18) return "本日の営業は終了しました";

  // ここまで来たら営業中
  const rest = 18 - hour;
  return `営業中です（閉店まであと${rest}時間）`;
}

// 動作確認
console.log(getShopStatus(new Date()));

// いろいろな時刻でテストする
const testCases = [
  new Date("2026-04-13T07:00"),  // 月曜 7時 → 開店前
  new Date("2026-04-13T10:00"),  // 月曜 10時 → 営業中
  new Date("2026-04-13T19:00"),  // 月曜 19時 → 終了
  new Date("2026-04-15T10:00"),  // 水曜 → 定休日
];

for (const d of testCases) {
  console.log(d.toLocaleString("ja-JP"), "→", getShopStatus(d));
}
```

### 曜日の日本語表示を追加

```javascript
const DAY_NAMES = ["日", "月", "火", "水", "木", "金", "土"];

function formatDate(date) {
  const y = date.getFullYear();
  const m = date.getMonth() + 1;   // ← 0始まりなので +1
  const d = date.getDate();
  const w = DAY_NAMES[date.getDay()];
  return `${y}年${m}月${d}日(${w})`;
}

console.log(formatDate(new Date()));
```

> ⚠️ **`getMonth()` は 0 から始まります。** 1月が `0`、12月が `11` です。
> **必ず `+1` してください。** これは JavaScript の有名な罠です。

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| ブラウザが固まる | 無限ループ | ループ内で条件が変化するか確認。タブを閉じる |
| `switch` で複数の case が実行される | `break` の書き忘れ | 各 case に `break` |
| `if` の後の行が常に実行される | `{ }` を省略した | 必ず `{ }` を書く |
| `for` で `Assignment to constant` | `const i` にした | `let i` にする |
| `forEach` で `break` できない | 仕様 | `for...of` を使う |
| 月が1つずれる | `getMonth()` は0始まり | `+1` する |
| 配列の最後だけ処理されない | `i <= arr.length` にしている | `i < arr.length` が正しい |
| ネストが深くて読めない | 早期リターンを使っていない | 異常系を先に弾く |

### `i < length` と `i <= length`

```javascript
const arr = ["a", "b", "c"];   // length は 3、添字は 0, 1, 2

for (let i = 0; i < arr.length; i++) { }   // ✅ 0,1,2
for (let i = 0; i <= arr.length; i++) { }  // ❌ 0,1,2,3 → arr[3] は undefined
```

**添字は 0 から始まるので、最後は `length - 1`。** だから条件は `<` です。

---

## 🤖 AIに聞いてみよう

### ① ネストの深いコードをレビューさせる

```text
以下は、私が書いた JavaScript の条件分岐です。

（コードを貼る）

次の観点でレビューしてください。

1. ネストが深くなっている箇所と、早期リターンで浅くできるか
2. 条件式が複雑で、変数に切り出したほうがよい箇所
3. if で書いているが switch や三項演算子のほうが適切な箇所
4. バグになりうる箇所（break 忘れ、境界値の誤りなど）

修正後のコードは書かず、指摘だけをお願いします。私が自分で直します。
```

### ② 境界値のテストケースを作らせる

```text
以下の関数について、テストすべきケースを網羅的に挙げてください。

（関数のコードを貼る）

観点:
- 正常系
- 境界値（ちょうど境目の値）
- 異常系（null、undefined、想定外の型、極端な値）

各ケースについて「入力」と「期待する出力」を表形式で示してください。
私が自分で実行して確認します。
```

> 💡 **境界値バグは初学者が最も多く作るバグです。**
> `>=` と `>` の1文字違いで、20歳の人が成人と判定されなくなります。
> テストケースを作る習慣をつけてください。

---

## 🔧 やってみよう（演習）

**AIに頼らず、自力でやってください。**

### 演習1（必須）─ FizzBuzz

1 から 30 までの数を出力してください。ただし、

- 3の倍数のときは `Fizz`
- 5の倍数のときは `Buzz`
- **3と5の両方の倍数**のときは `FizzBuzz`

```javascript
for (let i = 1; i <= 30; i++) {
  // ここに書く
}
```

<details>
<summary>答えを見る</summary>

```javascript
for (let i = 1; i <= 30; i++) {
  if (i % 15 === 0) {
    console.log("FizzBuzz");
  } else if (i % 3 === 0) {
    console.log("Fizz");
  } else if (i % 5 === 0) {
    console.log("Buzz");
  } else {
    console.log(i);
  }
}
```

**ポイント**：`FizzBuzz` の判定を**最初**に書くこと。
`i % 3 === 0` を先に書くと、15のときに `Fizz` になってしまいます。
`15` の代わりに `i % 3 === 0 && i % 5 === 0` と書いても正解です。

</details>

### 演習2（必須）

以下の関数を、**早期リターンを使って**書き直してください。

```javascript
function getDeliveryFee(order) {
  if (order) {
    if (order.total > 0) {
      if (order.total >= 5000) {
        return 0;
      } else {
        if (order.isMember) {
          return 300;
        } else {
          return 600;
        }
      }
    } else {
      return null;
    }
  } else {
    return null;
  }
}
```

<details>
<summary>答えを見る</summary>

```javascript
function getDeliveryFee(order) {
  if (!order) return null;
  if (order.total <= 0) return null;
  if (order.total >= 5000) return 0;
  return order.isMember ? 300 : 600;
}
```

7段のネストが、4行のフラットなコードになりました。
**「異常系を先に弾く → 特殊ケース → 通常ケース」**の順で並べるのがコツです。

</details>

### 演習3（挑戦）─ カフェの料金計算

以下の仕様で、料金を計算する関数を書いてください。

**仕様**
- ドリンク1杯 600円
- 2杯目以降は 100円引き（1杯目は定価）
- 会員は全体から 10% 引き
- 合計が 3000円以上なら、さらに 200円引き
- 端数は切り捨て

```javascript
function calcTotal(cups, isMember) {
  // ここに書く
}

// テストケース
console.log(calcTotal(1, false));  // 600
console.log(calcTotal(3, false));  // 1600
console.log(calcTotal(3, true));   // 1440
console.log(calcTotal(6, true));   // ?
```

<details>
<summary>答えを見る</summary>

```javascript
function calcTotal(cups, isMember) {
  if (cups <= 0) return 0;

  // 1杯目は600円、2杯目以降は500円
  let total = 600 + (cups - 1) * 500;

  if (isMember) {
    total = total * 0.9;
  }

  if (total >= 3000) {
    total = total - 200;
  }

  return Math.floor(total);
}

console.log(calcTotal(1, false));  // 600
console.log(calcTotal(3, false));  // 1600
console.log(calcTotal(3, true));   // 1440
console.log(calcTotal(6, true));   // 6杯 = 600 + 2500 = 3100 → ×0.9 = 2790 → 3000未満なので割引なし → 2790
```

**注意点**
- `cups` が 0 や負の数のときを先に弾く（**境界値**）
- 「3000円以上」の判定は、**会員割引の後**か前か？ 仕様が曖昧な箇所です
- 実務では、こういう曖昧さを**実装前に確認**します。勝手に決めないでください

</details>

---

## ✅ 章末チェック

- [ ] `if` の `{ }` を省略してはいけない理由を言える
- [ ] 三項演算子を使うべき場面を説明できる
- [ ] `switch` で `break` を忘れると何が起きるか知っている
- [ ] 早期リターンでネストを浅くできる
- [ ] `for` / `for...of` / `forEach` / `while` を使い分けられる
- [ ] `forEach` で `break` できないことを知っている
- [ ] 無限ループになる条件と、止め方を知っている
- [ ] `getMonth()` が0始まりであることを知っている
- [ ] FizzBuzz を自力で書けた

---

**前 → [2-1 JavaScriptを動かす・値と変数](02-01-js-basics.md)　｜　次 → [2-3 関数とスコープ](02-03-functions.md)**
