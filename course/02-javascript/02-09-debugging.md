# 2-9 エラーの読み方とデバッグ手順

> 🎯 **このレッスンのゴール**
> - エラーメッセージを3要素に分解して読める
> - ブレークポイントを使って、実行を止めながら調べられる
> - 「切り分け」で原因を絞り込める
> - `try...catch` で例外を扱える

所要 120分 / 難度 🟡

---

## 📖 デバッグは「推測」ではなく「切り分け」

初学者と経験者の差が最も出るのが、ここです。

```
❌ 推測ベース
   「たぶんここが悪い」→ 直す → 動かない → 「じゃあここか」→ …
   運が悪いと何時間も溶ける

✅ 切り分けベース
   「どこまでは正しく動いているか」を確定させる
   → 動く範囲と動かない範囲の境界を見つける
   → そこが原因
```

**このレッスンで、切り分けの技術を身につけます。**

---

## ✍️ 手を動かす① ─ エラーメッセージを読む

Console に出るエラーは、**3つの要素**でできています。

```
Uncaught TypeError: Cannot read properties of null (reading 'textContent')
    at app.js:12:14
└──①──┘└───②────┘└──────────③──────────────────────┘  └───④───┘
```

| 要素 | 内容 | 読み取れること |
| --- | --- | --- |
| ① `Uncaught` | 捕まえられていない例外 | `try...catch` されていない |
| ② `TypeError` | エラーの**種類** | 型に関する問題 |
| ③ メッセージ | 何が起きたか | `null` に `.textContent` しようとした |
| ④ `app.js:12:14` | **場所**（ファイル:行:桁） | クリックで飛べる |

### 主なエラーの種類

| 種類 | 意味 | よくある原因 |
| --- | --- | --- |
| **`TypeError`** | 型が想定と違う | `null` / `undefined` に対する操作 |
| **`ReferenceError`** | 存在しないものを参照 | 変数名のスペルミス、定義前の使用 |
| **`SyntaxError`** | 文法エラー | 括弧・クォートの対応、全角文字 |
| `RangeError` | 範囲外 | 無限再帰、配列サイズ |
| `Error` | 一般的なエラー | `throw new Error()` で自分で投げた |

### 頻出エラー トップ5

#### 1. `Cannot read properties of null (reading 'xxx')`

**意味**：`null` に対して `.xxx` しようとした

**原因はこの2つだけ**

```javascript
// ① セレクタが間違っている
const el = document.querySelector("#btn");   // 存在しないIDや、つづりミス
console.log(el);   // null

// ② script が HTML より先に実行されている
// → <script> に defer を付ける
```

#### 2. `Cannot read properties of undefined (reading 'xxx')`

**意味**：`undefined` に対して `.xxx` しようとした

```javascript
const user = { name: "太郎" };
console.log(user.address.city);   // ❌ address が undefined

// 対処①：オプショナルチェーン
console.log(user.address?.city);  // undefined（エラーにならない）

// 対処②：デフォルト値
console.log(user.address?.city ?? "未登録");
```

配列でもよく起きます。

```javascript
const items = [];
console.log(items[0].name);    // ❌ items[0] が undefined
console.log(items[0]?.name);   // ✅
```

#### 3. `xxx is not defined`（ReferenceError）

```javascript
console.log(userName);   // ❌ 定義されていない

// 原因
// - スペルミス（username と userName）
// - 定義より前で使っている
// - スコープの外から使っている
// - ファイルの読み込み順が違う
```

#### 4. `xxx is not a function`（TypeError）

```javascript
btn.addEventListner("click", f);   // ❌ Listner → Listener
[1,2,3].map()                      // ❌ 引数がない
const x = 5; x();                  // ❌ 数値は関数ではない
nodeList.map(...)                  // ❌ NodeList に map はない
```

#### 5. `Unexpected token` / `Unexpected end of input`（SyntaxError）

**文法エラー。** 括弧・クォートの対応、全角文字が原因です。

```javascript
if (a === 1) {
  console.log("a");
// } ← 閉じ忘れ
```

> ⚠️ **SyntaxError は、ファイル全体が実行されません。**
> 「console.log すら出ない」場合は、まず SyntaxError を疑ってください。
>
> **VS Code の括弧の色分け**（第0部で設定した `bracketPairColorization`）が、これを防ぎます。

---

## ✍️ 手を動かす② ─ console の使い分け

```javascript
console.log("通常のログ");
console.error("エラー（赤・スタックトレース付き）");
console.warn("警告（黄）");
console.info("情報");

// 表で見る（オブジェクトの配列に最適）
console.table([{ name: "太郎", age: 20 }, { name: "花子", age: 22 }]);

// グループ化
console.group("処理開始");
console.log("ステップ1");
console.log("ステップ2");
console.groupEnd();

// 実行時間を測る
console.time("処理");
// 何か重い処理
console.timeEnd("処理");   // 処理: 123.45ms

// 条件付きで出す
console.assert(items.length > 0, "items が空です");

// 呼ばれた回数を数える
console.count("render");   // render: 1, render: 2, ...

// 呼び出し元をたどる
console.trace("ここまでの経路");
```

### 変数名も一緒に出す

```javascript
const name = "太郎", age = 20, items = [1,2,3];

console.log(name, age, items);        // 太郎 20 [1,2,3] ← どれが何かわからない
console.log({ name, age, items });    // { name: "太郎", age: 20, items: [1,2,3] } ✅
```

> 💡 **`console.log({ 変数 })` は、覚えておくと一生使えるテクニックです。**

---

## ✍️ 手を動かす③ ─ ブレークポイント（本命）

`console.log` を撒くより、**実行を止めて中身を覗く**ほうが速い場面が多くあります。

### 設定の仕方

1. 開発者ツール → **Sources** タブ
2. 左のファイル一覧から `app.js` を開く
3. **行番号をクリック** → 青いマークが付く（ブレークポイント）
4. ページをリロード、または該当の操作をする
5. **その行で実行が止まる**

### 止まったら何ができるか

| できること | 方法 |
| --- | --- |
| **変数の値を見る** | 変数にマウスを乗せる。または右の Scope パネル |
| **式を評価する** | Console タブに切り替えて、その場で式を打つ |
| **呼び出し元をたどる** | 右の Call Stack パネル |
| **1行ずつ進める** | `F10`（ステップオーバー） |
| **関数の中に入る** | `F11`（ステップイン） |
| **関数から出る** | `Shift + F11` |
| **次のブレークポイントまで進む** | `F8` |

> 💡 **止まっている間、Console でその場のスコープの変数にアクセスできます。**
> `items` と打てば中身が見えます。`items.filter(x => x.done)` のように式も試せます。
> **これがブレークポイントの最大の利点です。**

### コードから止める

```javascript
const calc = (items) => {
  debugger;      // ← ここで止まる（開発者ツールが開いているときだけ）
  return items.reduce((s, i) => s + i.price, 0);
};
```

> ⚠️ **`debugger` は本番コードに残さないでください。** 消し忘れると、ユーザーの環境で止まります。

### 条件付きブレークポイント

行番号を**右クリック** → 「Add conditional breakpoint」

```javascript
item.id === 5
```

**条件が真のときだけ止まります。** ループの100回目だけ調べたい、というときに必須です。

### イベントで止める

Sources タブ右側の **Event Listener Breakpoints** で、
`Mouse → click` にチェックを入れると、**あらゆるクリックで止まります**。

「どこでイベントが処理されているかわからない」ときに便利です。

---

## ✍️ 手を動かす④ ─ 切り分けの技術

### 手順

```
1. 再現手順を固定する
   → 「たまに起きる」を「必ず起きる」にする
   → 再現しないバグは直せない

2. 期待と実際を明確にする
   → 「動かない」ではなく
      「〇〇したとき、△△になるはずが、□□になる」

3. 通っている経路を確認する
   → 関数の先頭に console.log("関数A 開始") を置く
   → そもそも呼ばれているか？

4. 値を確認する
   → 各ステップで、変数がどうなっているか

5. 範囲を半分に絞る
   → 前半をコメントアウト → 動く？
   → 動くなら原因は後半にある
   → さらに半分に…
```

### 実践例

```javascript
const updateTotal = () => {
  const items = getCartItems();
  const total = items.reduce((s, i) => s + i.price * i.quantity, 0);
  document.querySelector("#total").textContent = `${total}円`;
};
// 「合計が NaN になる」という症状
```

**切り分け**

```javascript
const updateTotal = () => {
  const items = getCartItems();
  console.log("① items:", items);                    // ← 配列は取れているか？

  const total = items.reduce((s, i) => {
    console.log("② item:", i, "price:", i.price, typeof i.price);   // ← 型は？
    return s + i.price * i.quantity;
  }, 0);
  console.log("③ total:", total);                     // ← どこで NaN になった？

  document.querySelector("#total").textContent = `${total}円`;
};
```

出力を見ると、`price` が `"600"`（文字列）だった、`quantity` が `undefined` だった、などが即座にわかります。

> 💡 **番号付きの `console.log` を撒く**のがコツです。どの順で実行されたかもわかります。

### 「動いていたのに動かなくなった」とき

```
1. 直前に何を変えたか？（最重要）
   → その変更を戻すと動くか確認

2. Git を使っているなら
   → git diff で変更点を確認
   → git stash で一時的に戻して確認

3. ブラウザのキャッシュではないか
   → スーパーリロード（Ctrl + Shift + R）
```

> 💡 **「小さく変えて、都度確認する」**のが、そもそもの予防策です。
> 100行書いてから動かすと、どこが原因かわかりません。**10行書いたら確認**してください。

---

## ✍️ 手を動かす⑤ ─ try...catch で例外を扱う

```javascript
try {
  // エラーが起きるかもしれない処理
  const data = JSON.parse(text);
  console.log(data);
} catch (error) {
  // エラーが起きたときの処理
  console.error("パースに失敗:", error.message);
} finally {
  // 成功・失敗にかかわらず必ず実行
  hideLoading();
}
```

### 使うべき場面

| 場面 | 使うか |
| --- | --- |
| `JSON.parse()` | ✅ 不正なJSONで落ちる |
| `fetch()` の通信 | ✅ ネットワークエラーで落ちる（2-10） |
| `localStorage` | ✅ プライベートモードで落ちることがある（2-11） |
| 単純な計算やDOM操作 | ❌ **使わない**。バグを隠してしまう |

> ⚠️ **`try...catch` で全部を囲まないでください。**
>
> ```javascript
> // ❌ バグが握りつぶされる
> try {
>   // 100行の処理
> } catch (e) {
>   // 何もしない
> }
> ```
>
> エラーが出ないので「動いている」ように見えますが、**実際は途中で止まっています**。
> **例外が起きうる箇所だけを、狭く囲む**のが正解です。

### 自分でエラーを投げる

```javascript
const divide = (a, b) => {
  if (b === 0) {
    throw new Error("0で割ることはできません");
  }
  return a / b;
};

try {
  divide(10, 0);
} catch (e) {
  console.error(e.message);   // 0で割ることはできません
}
```

**「ここから先は処理を続けられない」ときに投げます。** 呼び出し側が対処を決められます。

---

## ✍️ 手を動かす⑥ ─ よくあるバグのパターン

### ① 非同期の順序

```javascript
let data = null;

fetch("/api/items")
  .then((res) => res.json())
  .then((json) => { data = json; });

console.log(data);   // null ← まだ来ていない
```

**通信は時間がかかるので、次の行はすぐ実行されます。** 詳しくは 2-10 で扱います。

### ② ループ内の非同期

```javascript
for (var i = 0; i < 3; i++) {
  setTimeout(() => console.log(i), 100);
}
// 3, 3, 3 ← var のせい。let にすれば 0, 1, 2
```

### ③ 型の取り違え

```javascript
const input = document.querySelector("#age").value;   // 常に文字列
if (input > 18) { }        // "5" > 18 は false（文字列比較）
if (Number(input) > 18) { }  // ✅
```

### ④ 参照の共有

```javascript
const template = { name: "", items: [] };
const a = { ...template };
const b = { ...template };
a.items.push("x");
console.log(b.items);   // ["x"] ← 配列は共有されている
```

### ⑤ 浮動小数点

```javascript
console.log(0.1 + 0.2);            // 0.30000000000000004
console.log(0.1 + 0.2 === 0.3);    // false 😱

// 対処：整数にして計算する
console.log((0.1 * 10 + 0.2 * 10) / 10 === 0.3);   // true

// 金額は「円」ではなく「銭」など、最小単位の整数で持つのが定石
```

> 💡 **金額計算では、小数を避けてください。** 消費税の計算で1円ズレるバグは、実務でよく起きます。

---

## 🤖 AIに聞いてみよう

### ① エラーの原因を切り分けさせる

```text
JavaScript でエラーが出て解決できません。

【やろうとしたこと】

【エラーメッセージ】
（Console の出力を、スタックトレースも含めて丸ごと貼る）

【該当コード】
（ファイル全体を貼る）

【自分で試したこと】
-
-

【お願い】
1. 原因として考えられるものを、可能性の高い順に3つ挙げてください
2. それぞれについて、どこに console.log を置けば確認できるか教えてください
3. すぐに修正コードを出すのではなく、私が原因を特定できる手順を示してください
```

### ② デバッグの練習問題を作らせる

```text
JavaScript のデバッグ力を鍛えたいです。

「バグのあるコード」を5問作ってください。

条件:
- 初学者が実際に踏みやすいバグ（型の取り違え、非同期、参照渡し、
  イベントリスナー、スコープ など）
- 各問題は20行以内
- 「期待する動作」と「実際の動作」を明記
- バグの箇所と原因は、まだ書かないでください

私が回答したら、正解かどうかと、見つけ方のコツを教えてください。
```

---

## 🔧 やってみよう（演習）

**AIに頼らず、自力でやってください。**

### 演習1（必須）─ エラーを読む

以下のエラーが出たとき、**何を確認すべきか**を答えてください。

```
1. Uncaught TypeError: Cannot read properties of null (reading 'addEventListener')
       at app.js:5:12

2. Uncaught ReferenceError: renderList is not defined
       at handleClick (app.js:23:5)

3. Uncaught TypeError: items.filter is not a function
       at app.js:15:20

4. Uncaught SyntaxError: Unexpected token '}'
       app.js:42

5. Uncaught TypeError: Cannot read properties of undefined (reading 'name')
       at app.js:30:25
```

<details>
<summary>答えを見る</summary>

1. **5行目で要素が取得できていない。** セレクタのつづり、`defer` の有無を確認。`console.log(el)` で `null` か確認。

2. **`renderList` という関数が存在しない。** 定義のつづり、定義の位置（`const` は巻き上げされない）、ファイルの読み込み順を確認。

3. **`items` が配列ではない。** `console.log(items, typeof items)` で確認。NodeList、オブジェクト、`undefined` などの可能性。NodeList なら `[...items]` で変換。

4. **42行目付近の文法エラー。** 括弧の対応を確認。指摘行の**手前**に原因があることが多い。VS Code の括弧の色分けで確認。

5. **30行目で、`undefined` の `.name` を読もうとした。** 配列の範囲外アクセス（`arr[5]` が存在しない）、オブジェクトのキー違い、`find` が見つからなかった、などの可能性。`?.` で暫定回避しつつ、根本原因を探る。

</details>

### 演習2（必須）─ バグを見つけて直す

以下のコードには**5つのバグ**があります。すべて見つけて直してください。
**実際に動かして確認**してください。

```html
<div id="app">
  <input type="number" id="price" value="1000">
  <input type="number" id="qty" value="3">
  <button id="calc">計算</button>
  <p id="result"></p>
</div>
<script>
  const priceEl = document.querySelector("#price");
  const qtyEl = document.querySelector("#qty");
  const btn = document.querySelector("#Calc");
  const result = document.querySelector("#result");

  function calculate() {
    const price = priceEl.value;
    const qty = qtyEl.value;
    const total = price * qty;
    const tax = total * 0.1;
    result.textContent = "合計: " + total + tax + "円";
  }

  btn.addEventListener("click", calculate());
</script>
```

<details>
<summary>答えを見る</summary>

1. **`#Calc` の大文字**（`#calc` が正しい）→ `btn` が `null` になり、最終行でエラー
2. **`<script>` が要素より後にあるが、`defer` がない**
   → この例では `</div>` の後なので実は動くが、`<head>` に移すと壊れる。
   外部ファイル化して `defer` を付けるのが安全
3. **`addEventListener("click", calculate())` の `()`** → 即実行される
4. **`total + tax` が文字列連結になっていない**が、`"合計: " + total` で文字列になった後に `+ tax` するため、
   `"合計: 30003000.0000000004円"` のようになる → `(total + tax)` と括る必要がある
5. **税額の小数** → `Math.floor()` で丸める

修正版：

```javascript
const priceEl = document.querySelector("#price");
const qtyEl = document.querySelector("#qty");
const btn = document.querySelector("#calc");        // ①
const result = document.querySelector("#result");

function calculate() {
  const price = Number(priceEl.value);              // 型変換（念のため）
  const qty = Number(qtyEl.value);
  const total = price * qty;
  const tax = Math.floor(total * 0.1);              // ⑤
  result.textContent = `合計: ${(total + tax).toLocaleString()}円`;  // ④
}

btn.addEventListener("click", calculate);           // ③
```

> `price * qty` は `*` なので文字列でも動きますが、`Number()` しておくのが安全です。

</details>

### 演習3（挑戦）─ ブレークポイントを使う

演習2の修正版に対して、**ブレークポイントを使って**以下を確認してください。

- [ ] `calculate` 関数の先頭にブレークポイントを設定する
- [ ] ボタンをクリックして、止まることを確認する
- [ ] `F10` で1行ずつ進め、`price` `qty` `total` `tax` の値の変化を Scope パネルで追う
- [ ] 止まっている状態で、Console に `price * qty * 1.1` と打って結果を確認する
- [ ] Call Stack パネルで、この関数がどこから呼ばれたか確認する
- [ ] 条件付きブレークポイント（`total > 5000`）を設定し、条件を満たすときだけ止まることを確認する

> 💡 **ブレークポイントに慣れると、`console.log` を撒く回数が激減します。**
> 最初は面倒に感じますが、必ず身につけてください。

---

## ✅ 章末チェック

- [ ] エラーメッセージの3要素（種類 / 内容 / 場所）を読める
- [ ] `TypeError` と `ReferenceError` の違いを言える
- [ ] `Cannot read properties of null` の原因2つを言える
- [ ] `console.log({ 変数 })` の利点を知っている
- [ ] ブレークポイントを設定して、変数を確認できる
- [ ] 条件付きブレークポイントを使える
- [ ] 「切り分け」の手順を説明できる
- [ ] `try...catch` を使うべき場面と、使うべきでない場面を言える
- [ ] `0.1 + 0.2 !== 0.3` になる理由を知っている

---

**前 → [2-8 フォームバリデーション実装](02-08-validation.md)　｜　次 → [2-10 非同期処理と fetch でAPIを叩く](02-10-async-fetch.md)**
