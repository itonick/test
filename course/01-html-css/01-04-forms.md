# 1-4 フォームの部品をひと通り

> 🎯 **このレッスンのゴール**
> - フォーム部品を一通り書ける
> - `label` と `id` の紐付けが「なぜ必須か」を説明できる
> - ブラウザ標準のバリデーションを使える
> - カフェLPにお問い合わせフォームを追加する

所要 120分 / 難度 🟡
完成コード: [`code/01-04/`](../code/01-04/)

---

## 📸 完成イメージ

```
┌──────────────────────────────────┐
│ CONTACT                          │
│                                  │
│ お名前 ※                         │
│ [_________________________]      │
│                                  │
│ メールアドレス ※                 │
│ [_________________________]      │
│                                  │
│ ご用件                            │
│ [ご予約について        ▼]        │
│                                  │
│ 希望日                            │
│ [2026/04/20            ]         │
│                                  │
│ お問い合わせ内容 ※               │
│ [                        ]       │
│ [                        ]       │
│                                  │
│ ☐ プライバシーポリシーに同意する  │
│                                  │
│        [ 送信する ]              │
└──────────────────────────────────┘
```

---

## 📖 フォームは「入力の受け皿」

第3部（PHP）で、このフォームから送られたデータを**実際に受け取ります**。いま作るのは、その受け皿です。

送信の仕組みは、こうなっています。

```
ブラウザ                        サーバー
  │                                │
  │  ①ユーザーが入力して送信       │
  │───────────────────────────────>│
  │   name="email" の値が届く      │
  │                                │  ②PHPが受け取って処理
  │<───────────────────────────────│
  │  ③結果のHTMLが返る             │
```

**重要な事実**：サーバーに届くのは `name` 属性の値だけです。`id` も `class` も届きません。

```html
<input type="text" id="user-name" name="name" class="form-input">
                   ↑届かない      ↑これが届く  ↑届かない
```

> ⚠️ **`name` を書き忘れると、そのデータは永遠にサーバーに届きません。**
> 「フォームは送れているのに、値が空」の原因の第1位です。

---

## ✍️ 手を動かす① ─ `<form>` の骨格

```html
<form action="contact.php" method="post">
  <!-- 入力部品 -->
  <button type="submit">送信する</button>
</form>
```

| 属性 | 意味 |
| --- | --- |
| `action` | 送信先のURL。空にすると自分自身に送る |
| `method` | 送り方。`get` か `post` |

### GET と POST の違い

| | GET | POST |
| --- | --- | --- |
| データの送り先 | **URLに付く**（`?name=太郎`） | **本文に隠れる** |
| 見えるか | ブラウザのアドレスバーに丸見え | 見えない |
| 履歴に残るか | 残る | 残らない |
| ブックマーク | できる | できない |
| データ量の上限 | ある（約2000文字） | 実質なし |
| 使う場面 | **検索**、絞り込み、ページ送り | **登録**、ログイン、問い合わせ |

> ⚠️ **パスワードや個人情報は必ず POST。**
> GET で送ると、URLに平文で残り、ブラウザ履歴・サーバーのアクセスログに記録されます。
>
> ただし、**POSTでも暗号化はされません。** 通信の暗号化はHTTPS（SSL）の仕事です。混同しないでください。

> 💡 **判断基準**：「同じURLを何度開いても安全か」で決めます。
> 検索結果は何度開いても安全 → GET。注文の確定は2回押したら二重注文 → POST。

---

## ✍️ 手を動かす② ─ `<label>` を必ず書く

これがフォームで最も重要なルールです。

```html
<!-- ❌ ラベルがない -->
お名前
<input type="text" name="name">

<!-- ❌ ラベルはあるが紐付いていない -->
<label>お名前</label>
<input type="text" name="name">

<!-- ✅ for と id で紐付いている -->
<label for="name">お名前</label>
<input type="text" id="name" name="name">
```

### なぜ紐付けが必要か

| 効果 | 具体的に何が起きるか |
| --- | --- |
| **クリック範囲が広がる** | ラベルの文字をクリックしても、入力欄にカーソルが入る |
| **チェックボックスが押しやすい** | 小さな四角だけでなく、文字をタップしても切り替わる |
| **スクリーンリーダーが読む** | 入力欄にフォーカスすると「お名前、編集テキスト」と読み上げる |

紐付けがないと、**目の見えない人はその入力欄が何を求めているかわかりません。**

> ⚠️ **`for` の値と `id` の値を一致させます。** `name` ではありません。ここを間違える人が非常に多いです。
>
> ```html
> <label for="user-mail">メール</label>
> <input type="email" id="user-mail" name="email">
>              for と id が一致 ↑        ↑ name は別でよい
> ```

> 💡 `id` はページ内で**重複禁止**です。同じ `id` が2つあると、ラベルの紐付けが壊れます。

---

## ✍️ 手を動かす③ ─ 入力部品カタログ

### テキスト入力（`<input>`）

```html
<label for="name">お名前</label>
<input type="text" id="name" name="name" placeholder="山田 太郎" required>

<label for="email">メールアドレス</label>
<input type="email" id="email" name="email" placeholder="you@example.com" required>

<label for="tel">電話番号</label>
<input type="tel" id="tel" name="tel" placeholder="03-1234-5678">

<label for="pass">パスワード</label>
<input type="password" id="pass" name="password" minlength="8" required>

<label for="num">人数</label>
<input type="number" id="num" name="guests" min="1" max="10" value="2">

<label for="date">希望日</label>
<input type="date" id="date" name="visit_date">
```

**`type` を正しく選ぶと、スマホでキーボードが変わります。**

| type | スマホのキーボード | ブラウザの自動チェック |
| --- | --- | --- |
| `text` | 通常 | なし |
| `email` | @ 付き | `@` を含むか |
| `tel` | テンキー | なし |
| `number` | 数字 | 数値か / min・max の範囲内か |
| `url` | .com 付き | URL形式か |
| `date` | 日付ピッカー | 日付として妥当か |

> 💡 `type="email"` にするだけで、スマホユーザーの入力が格段に楽になります。**細かいようでいて、離脱率に効きます。**

> ⚠️ **`placeholder` をラベル代わりにしないでください。**
> 入力を始めると消えるため、「何を入れる欄だったか」がわからなくなります。
> `placeholder` は**入力例**、`label` は**項目名**。役割が違います。

### 複数行（`<textarea>`）

```html
<label for="message">お問い合わせ内容</label>
<textarea id="message" name="message" rows="6" placeholder="ご自由にお書きください" required></textarea>
```

> ⚠️ **`<textarea>` に `value` 属性はありません。** 初期値はタグの間に書きます。
> また、**開始タグの直後から終了タグまでが中身**になるので、改行やスペースを入れると、それがそのまま初期値になります。
>
> ```html
> <!-- ❌ 空白が初期値として入ってしまう -->
> <textarea>
> </textarea>
>
> <!-- ✅ 隙間なく閉じる -->
> <textarea></textarea>
> ```

### 選択肢（`<select>`）

```html
<label for="topic">ご用件</label>
<select id="topic" name="topic">
  <option value="">選択してください</option>
  <option value="reserve">ご予約について</option>
  <option value="bean">豆の販売について</option>
  <option value="other" selected>その他</option>
</select>
```

- `value` がサーバーに届く値。表示テキストとは別にできる
- `selected` を付けると初期選択になる
- 先頭に空の `<option value="">` を置くのが定石（未選択を表現できる）

### ラジオボタン（1つだけ選ぶ）

```html
<fieldset>
  <legend>ご来店経験</legend>

  <input type="radio" id="first-yes" name="first_visit" value="yes" checked>
  <label for="first-yes">はじめて</label>

  <input type="radio" id="first-no" name="first_visit" value="no">
  <label for="first-no">2回目以降</label>
</fieldset>
```

> ⚠️ **ラジオボタンは `name` を揃えることでグループになります。**
> `name` がバラバラだと、全部選べてしまいます。逆に `id` は**それぞれ違う値**にします。
>
> ```
> name  → グループ名（揃える）
> id    → 個体識別（全部違う）
> value → 送られる値（全部違う）
> ```

### チェックボックス（複数選べる）

```html
<input type="checkbox" id="agree" name="agree" value="1" required>
<label for="agree">プライバシーポリシーに同意する</label>
```

複数選択させたい場合は、`name` に `[]` を付けます（PHPで配列として受け取れます）。

```html
<input type="checkbox" id="c1" name="interests[]" value="bean">
<label for="c1">豆の販売</label>
<input type="checkbox" id="c2" name="interests[]" value="event">
<label for="c2">イベント情報</label>
```

### `<fieldset>` と `<legend>`

関連する入力をまとめる箱です。ラジオやチェックボックスのグループには**必ず使ってください**。

```html
<fieldset>
  <legend>ご希望の時間帯</legend>
  ...
</fieldset>
```

スクリーンリーダーが「ご希望の時間帯、はじめて、ラジオボタン」のように読み上げてくれます。

### ボタン

```html
<button type="submit">送信する</button>
<button type="reset">リセット</button>
<button type="button">ただのボタン（JSで使う）</button>
```

> ⚠️ **`<button>` の `type` を省略すると `submit` になります。**
> フォームの中に置いたボタンをJavaScript用に使いたい場合、`type="button"` を明示しないと**ページがリロードされます**。
> 第2部で必ず踏む地雷なので、いま覚えてください。

---

## ✍️ 手を動かす④ ─ ブラウザ標準のバリデーション

HTMLだけで、入力チェックがある程度できます。

```html
<input type="email" name="email" required>
<input type="text" name="name" required minlength="1" maxlength="50">
<input type="number" name="guests" min="1" max="10">
<input type="text" name="zip" pattern="[0-9]{3}-[0-9]{4}" placeholder="123-4567">
```

| 属性 | チェック内容 |
| --- | --- |
| `required` | 未入力を許さない |
| `minlength` / `maxlength` | 文字数の下限・上限 |
| `min` / `max` | 数値・日付の範囲 |
| `pattern` | 正規表現にマッチするか |

送信ボタンを押すと、ブラウザが自動でエラーを表示してくれます。

> ⚠️ **これはあくまで「親切機能」です。セキュリティ対策にはなりません。**
> 開発者ツールで `required` を消せば、いくらでも送信できます。
> **サーバー側（PHP）でも必ず同じチェックをします。** 第3部（3-7）で扱います。
>
> **鉄則：クライアント側のチェックは信用しない。**

---

## ✍️ 手を動かす⑤ ─ カフェLPにフォームを追加

`index.html` の `<main>` の最後（ACCESS の後）に、以下を追加してください。

> 🖊 **手で打ってください。** 完成形は `code/01-04/index.html` にあります（📋 コピペ可）。

```html
    <section id="contact" class="contact">
      <h2>CONTACT</h2>
      <p>ご予約・お問い合わせはこちらから。<span class="req-note">※は必須項目です。</span></p>

      <form action="contact.php" method="post" class="contact-form">

        <div class="field">
          <label for="name">お名前 <span class="req">※</span></label>
          <input type="text" id="name" name="name"
                 placeholder="山田 太郎" maxlength="50" required>
        </div>

        <div class="field">
          <label for="email">メールアドレス <span class="req">※</span></label>
          <input type="email" id="email" name="email"
                 placeholder="you@example.com" required>
        </div>

        <div class="field">
          <label for="tel">電話番号</label>
          <input type="tel" id="tel" name="tel" placeholder="03-1234-5678">
        </div>

        <div class="field">
          <label for="topic">ご用件</label>
          <select id="topic" name="topic">
            <option value="">選択してください</option>
            <option value="reserve">ご予約について</option>
            <option value="bean">豆の販売について</option>
            <option value="event">イベントについて</option>
            <option value="other">その他</option>
          </select>
        </div>

        <div class="field">
          <label for="visit-date">ご希望日</label>
          <input type="date" id="visit-date" name="visit_date">
        </div>

        <fieldset class="field">
          <legend>ご希望の時間帯</legend>
          <div class="radio-group">
            <input type="radio" id="time-am" name="time_slot" value="am" checked>
            <label for="time-am">午前（8:00-12:00）</label>

            <input type="radio" id="time-pm" name="time_slot" value="pm">
            <label for="time-pm">午後（12:00-18:00）</label>
          </div>
        </fieldset>

        <div class="field">
          <label for="message">お問い合わせ内容 <span class="req">※</span></label>
          <textarea id="message" name="message" rows="6"
                    placeholder="ご自由にお書きください" required></textarea>
        </div>

        <div class="field field-check">
          <input type="checkbox" id="agree" name="agree" value="1" required>
          <label for="agree">プライバシーポリシーに同意する <span class="req">※</span></label>
        </div>

        <button type="submit" class="btn btn-submit">送信する</button>

      </form>
    </section>
```

ナビゲーションにも CONTACT を追加してください。

```html
        <li><a href="#contact">CONTACT</a></li>
```

### 動作確認

Live Server で開き、以下を試してください。

- [ ] ラベルの**文字**をクリックすると、入力欄にカーソルが入る
- [ ] 何も入力せず「送信する」を押すと、ブラウザが警告を出す
- [ ] メール欄に `abc` と入れて送信すると、「@を含めてください」と出る
- [ ] チェックボックスは**文字をクリック**しても切り替わる
- [ ] スマホ表示（開発者ツールのデバイスモード）で、メール欄をタップするとキーボードが変わる

> 💡 いま送信すると `contact.php` が無いので404になります。**それで正解です。** 第3部で作ります。

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| 送信しても値が届かない | `name` を書き忘れている | すべての入力部品に `name` を書く |
| ラベルをクリックしても反応しない | `for` と `id` が一致していない | `for` は `name` ではなく `id` と対応させる |
| ラジオが全部選べてしまう | `name` がバラバラ | グループ内で `name` を揃える |
| ラジオが1つしか表示されない | `id` が重複している | `id` は全部違う値にする |
| ボタンを押すとページがリロードされる | `<button>` の `type` 省略 | JS用なら `type="button"` |
| textarea に空白が入っている | 開始タグと終了タグの間に改行がある | `<textarea></textarea>` と隙間なく書く |
| 日付入力がただのテキスト欄になる | 古いブラウザ | 気にしなくてよい（モダンブラウザは対応済み） |

---

## 🤖 AIに聞いてみよう

### ① フォームのアクセシビリティをレビューさせる

```text
以下は、私が書いたお問い合わせフォームのHTMLです。

（フォーム部分を貼る）

アクセシビリティの観点でレビューしてください。

1. label と入力欄の紐付けに漏れがないか
2. スクリーンリーダーで使ったときに困る箇所がないか
3. キーボードだけで操作できるか
4. エラー時に、何が問題かがユーザーに伝わる作りになっているか

修正後のコードは書かず、指摘だけをお願いします。私が自分で直します。
```

### ② input type の選択を検証させる

```text
以下の入力項目に対して、最適な input の type 属性と、
付けるべき検証用の属性（required / pattern / min / max など）を教えてください。

1. 郵便番号（123-4567 の形式）
2. 年齢（0〜120）
3. 予約人数（1〜8名）
4. 会社のWebサイトURL（任意入力）
5. 生年月日

それぞれについて、スマホで表示したときにどんなキーボードが出るかも教えてください。
また、HTMLの検証属性だけでは不十分な理由も説明してください。
```

---

## 🔧 やってみよう（演習）

**AIに頼らず、自力でやってください。**

### 演習1（必須）

フォームに以下を追加してください。

- [ ] 「ご来店人数」の欄（`type="number"`、1〜8の範囲、初期値2）
- [ ] 「知ったきっかけ」のチェックボックス群（複数選択可、`name="source[]"`）
      - Instagram / 友人の紹介 / 通りがかり / その他
- [ ] チェックボックス群を `<fieldset>` と `<legend>` で囲む

### 演習2（必須）

以下のフォームには**6つの問題**があります。すべて見つけて直してください。

```html
<form>
  <label>お名前</label>
  <input type="text" id="name">

  <label for="mail">メール</label>
  <input type="text" name="mail">

  <label for="pw">パスワード</label>
  <input type="text" id="pw" name="password">

  <input type="radio" id="a" name="plan1" value="a">
  <label for="a">Aプラン</label>
  <input type="radio" id="a" name="plan2" value="b">
  <label for="a">Bプラン</label>

  <textarea name="msg">
  </textarea>

  <button>送信</button>
</form>
```

<details>
<summary>答えを見る（自力でやってから開いてください）</summary>

1. **`<form>` に `method` がない** → `method="post"` を追加（省略すると GET になる）
2. **1つ目の label に `for` がなく、input に `name` がない** → `for="name"` と `name="name"` を追加
3. **2つ目の input に `id` がない**（`for="mail"` の相手がいない） → `id="mail"` を追加
4. **パスワードが `type="text"`** → `type="password"` にする（画面に丸見えになっている）
5. **ラジオの `name` がバラバラで、`id` が重複** → `name` は `plan` に統一、`id` は `plan-a` / `plan-b` のように別々にし、`label for` もそれぞれに合わせる
6. **`<textarea>` の中に改行がある** → 初期値に空白が入る。`<textarea name="msg"></textarea>` と隙間なく閉じる

（おまけ：`<button>` に `type="submit"` を明示すると意図が明確になります）

</details>

### 演習3（挑戦）

「予約フォーム」を新しいファイル `pages/reserve.html` として作ってください。

- [ ] 氏名（必須）、メール（必須）、電話（必須、`pattern` で数字とハイフンのみ）
- [ ] 予約日（必須、`type="date"`）
- [ ] 予約時間（`type="time"` を自分で調べて使う）
- [ ] 人数（1〜8、必須）
- [ ] 席の希望（カウンター / テーブル / どちらでも）をラジオで
- [ ] アレルギーの有無をチェックボックスで（複数選択）
- [ ] すべての入力欄に `label` を紐付ける

> 💡 `type="time"` の仕様は MDN で調べてください：
> https://developer.mozilla.org/ja/docs/Web/HTML/Element/input/time

---

## ✅ 章末チェック

- [ ] サーバーに届くのは `name` の値だけだと説明できる
- [ ] GET と POST の使い分けの基準を言える
- [ ] `for` と `id` を一致させる理由を説明できる
- [ ] ラジオボタンの `name` を揃える理由を説明できる
- [ ] `placeholder` をラベル代わりにしてはいけない理由を言える
- [ ] HTMLのバリデーションだけでは不十分な理由を説明できる
- [ ] `<button>` の `type` 省略で何が起きるか知っている
- [ ] カフェLPにフォームが入った
- [ ] 演習1〜2を完了した

---

**前 → [1-3 画像・リンク・リストを扱う](01-03-images-links.md)　｜　次 → [1-5 CSSの書き方とセレクタ](01-05-css-basics.md)**
