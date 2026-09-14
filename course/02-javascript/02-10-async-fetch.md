# 2-10 非同期処理と fetch でAPIを叩く

> 🎯 **このレッスンのゴール**
> - 同期と非同期の違いを説明できる
> - `async` / `await` で通信を書ける
> - エラー・ローディング・空状態を正しく扱える
> - 実際のAPIからデータを取得して表示する

所要 180分 / 難度 🔴
完成コード: [`code/02-10/`](../code/02-10/)

---

## 📖 なぜ非同期が必要か

```javascript
console.log("①");
サーバーからデータを取得（3秒かかる）;
console.log("②");
```

もしこれが**同期**（順番に待つ）だったら、3秒間ブラウザが完全に固まります。クリックもスクロールもできません。

そこで JavaScript は、**時間のかかる処理を「あとで」に回します**。

```javascript
console.log("①");
setTimeout(() => console.log("②"), 1000);
console.log("③");

// 出力：① ③ ②
```

**「③」が先に出ます。** これが非同期です。

> 💡 **たとえ話**：レストランで注文したあと、料理ができるまで突っ立って待つ（同期）のではなく、
> 席に戻って会話を続け、できたら呼ばれる（非同期）。

---

## ✍️ 手を動かす① ─ Promise

非同期処理の結果は、**Promise（約束）** というオブジェクトで表されます。

```javascript
const promise = fetch("https://example.com/data.json");
console.log(promise);   // Promise { <pending> } ← まだ結果がない
```

Promise には3つの状態があります。

| 状態 | 意味 |
| --- | --- |
| `pending` | 処理中（結果待ち） |
| `fulfilled` | 成功。値を持っている |
| `rejected` | 失敗。エラーを持っている |

### `.then()` で結果を受け取る（古い書き方）

```javascript
fetch("https://api.example.com/items")
  .then((response) => response.json())
  .then((data) => {
    console.log(data);
  })
  .catch((error) => {
    console.error(error);
  });
```

**読めるようにはなってください**（既存コードで見かけます）が、書くのは次の `async/await` を使います。

---

## ✍️ 手を動かす② ─ async / await（こちらを使う）

```javascript
const getItems = async () => {
  const response = await fetch("https://api.example.com/items");
  const data = await response.json();
  console.log(data);
};

getItems();
```

| キーワード | 意味 |
| --- | --- |
| `async` | 「この関数は非同期です」という宣言。**`await` を使うには必須** |
| `await` | 「Promise の結果が出るまで待つ」 |

**上から下に読める**のが最大の利点です。

### 3つのルール

#### ① `await` は `async` 関数の中でしか使えない

```javascript
// ❌ SyntaxError
const data = await fetch(url);

// ✅ async 関数で包む
const load = async () => {
  const data = await fetch(url);
};
```

> 💡 モジュール（`<script type="module">`）ではトップレベル `await` が使えますが、この教材では関数で包む書き方に統一します。

#### ② `async` 関数は必ず Promise を返す

```javascript
const f = async () => 42;

console.log(f());          // Promise { 42 } ← 値そのものではない
console.log(await f());    // 42（async 関数の中でなら）

f().then((v) => console.log(v));   // 42
```

#### ③ 呼び出し側も `await` しないと待たない

```javascript
const load = async () => {
  const data = await fetchData();
  return data;
};

const result = load();          // ❌ Promise が入る
const result = await load();    // ✅ データが入る（async 関数の中で）
```

> ⚠️ **「データが `Promise { <pending> }` になる」のは、`await` の付け忘れです。** 最頻出のミスです。

---

## ✍️ 手を動かす③ ─ fetch の正しい書き方

### 基本形（これだけでは不十分）

```javascript
const res = await fetch(url);
const data = await res.json();
```

### ⚠️ fetch の最大の罠

**`fetch` は、404 や 500 でもエラーになりません。**

```javascript
const res = await fetch("https://example.com/not-found");
console.log(res.ok);       // false
console.log(res.status);   // 404
// でも catch には入らない！
```

`fetch` が `reject` するのは、**ネットワーク自体が失敗したとき**（オフライン、DNS失敗、CORS違反）だけです。

**必ず `res.ok` を確認してください。**

> 🆘 **ここで詰まったら**（データが取れない／`undefined` になる）
> - **まず確認**：① `console.log(res.status)` で 200 が返っているか（404/500 なら URL 違い・APIの仕様）② `console.log(data)` で中身の構造を見て、`data.results[0].name` のような**取り出し方のキー名**が合っているか ③ 開発者ツールの「Network」タブで該当リクエストの Status とレスポンス本文を確認
> - **直らなければ、AIにこう聞く**（`console.log(data)` の出力を貼る）：
>   「fetch で取れたこのJSONから、○○の値を取り出したいです。正しいアクセス方法を教えてください。あわせて、なぜ私の書き方だと undefined になるのかも説明してください」

### 完成形のテンプレート（📋 コピペして使ってください）

```javascript
/**
 * JSON を取得する。失敗時は例外を投げる。
 */
const fetchJson = async (url, options = {}) => {
  const res = await fetch(url, {
    headers: { "Accept": "application/json" },
    ...options,
  });

  if (!res.ok) {
    throw new Error(`HTTPエラー: ${res.status} ${res.statusText}`);
  }

  return res.json();
};
```

### 使う側

```javascript
const load = async () => {
  try {
    const data = await fetchJson("https://api.example.com/items");
    render(data);
  } catch (error) {
    console.error(error);
    showError("データの取得に失敗しました。時間をおいてお試しください。");
  }
};
```

### POST する

```javascript
const postJson = async (url, body) => {
  const res = await fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(body),      // ← オブジェクトを文字列にする
  });

  if (!res.ok) throw new Error(`HTTPエラー: ${res.status}`);
  return res.json();
};

// 使う
await postJson("/api/reserve", { name: "山田", guests: 2 });
```

> ⚠️ **`body` には文字列を渡します。** オブジェクトをそのまま渡すと `[object Object]` になります。
> `JSON.stringify()` を忘れないでください。

### タイムアウトを付ける

`fetch` には標準のタイムアウトがありません。**永遠に待ち続けることがあります。**

```javascript
const fetchJsonWithTimeout = async (url, options = {}, timeoutMs = 10000) => {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);

  try {
    const res = await fetch(url, { ...options, signal: controller.signal });
    if (!res.ok) throw new Error(`HTTPエラー: ${res.status}`);
    return await res.json();
  } catch (error) {
    if (error.name === "AbortError") {
      throw new Error("通信がタイムアウトしました");
    }
    throw error;
  } finally {
    clearTimeout(timer);
  }
};
```

---

## ✍️ 手を動かす④ ─ 3つの状態を必ず扱う

**通信を伴うUIには、必ず4つの状態があります。**

```
① 読み込み中（loading）  → スピナーやスケルトンを出す
② 成功・データあり       → データを表示
③ 成功・データ0件        → 「該当なし」を表示（← 忘れがち）
④ 失敗                   → エラーメッセージと再試行ボタン
```

> ⚠️ **初学者は②しか作りません。**
> 通信中に真っ白、失敗しても真っ白、0件でも真っ白——これが「壊れている」と思われる原因です。
> **③と④を作れるかどうかが、初学者とそれ以外の分かれ目です。**

### 実装パターン

```javascript
const state = {
  status: "idle",   // "idle" | "loading" | "success" | "error"
  items: [],
  error: null,
};

const render = () => {
  const el = document.querySelector("#app");

  if (state.status === "loading") {
    el.innerHTML = '<p class="loading">読み込み中…</p>';
    return;
  }

  if (state.status === "error") {
    el.innerHTML = `
      <div class="error-box">
        <p>${escapeHtml(state.error)}</p>
        <button type="button" id="retry">再試行</button>
      </div>
    `;
    return;
  }

  if (state.items.length === 0) {
    el.innerHTML = '<p class="empty">データがありません</p>';
    return;
  }

  el.innerHTML = state.items.map(itemHtml).join("");
};

const load = async () => {
  state.status = "loading";
  render();

  try {
    state.items = await fetchJson(API_URL);
    state.status = "success";
    state.error = null;
  } catch (error) {
    state.status = "error";
    state.error = error.message;
  }

  render();
};
```

> 💡 **「状態を変える → 描き直す」** という2-6の設計が、ここで効いてきます。
> 状態に `status` を1つ足すだけで、4つの状態すべてに対応できました。

---

## ✍️ 手を動かす⑤ ─ 実践：天気予報ウィジェット

**実在の無料API**を使います。APIキー不要で、登録も不要です。

### API について

**Open-Meteo**（https://open-meteo.com/ ）

```
https://api.open-meteo.com/v1/forecast?latitude=35.68&longitude=139.76&daily=weather_code,temperature_2m_max,temperature_2m_min&timezone=Asia%2FTokyo&forecast_days=5
```

まず、**このURLをブラウザのアドレスバーに貼って開いてみてください。** JSONが返ってきます。

> 💡 **API を使うときは、まずブラウザで叩いてレスポンスの形を確認する**のが鉄則です。
> どんなキーがあるか、配列かオブジェクトか、を見てからコードを書きます。

### HTML（📋 コピペ可）

`htdocs/js-lesson/weather.html`

```html
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>天気予報</title>
  <link rel="stylesheet" href="css/weather.css">
</head>
<body>
  <main>
    <h1>5日間の天気</h1>

    <div class="controls">
      <label for="city">都市</label>
      <select id="city">
        <option value="35.68,139.76">東京</option>
        <option value="34.69,135.50">大阪</option>
        <option value="43.06,141.35">札幌</option>
        <option value="26.21,127.68">那覇</option>
      </select>
      <button type="button" id="reload">更新</button>
    </div>

    <div id="app" aria-live="polite"></div>
  </main>

  <script src="js/weather.js" defer></script>
</body>
</html>
```

### CSS（📋 コピペ可）

`css/weather.css`

```css
*, *::before, *::after { box-sizing: border-box; }

body {
  font-family: system-ui, "Hiragino Sans", sans-serif;
  line-height: 1.8;
  margin: 0; padding: 24px;
  background: #f7f8fa; color: #1c1f24;
}
main { max-width: 720px; margin-inline: auto; }

.controls { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-block: 20px; }
select, button {
  font: inherit; font-size: 15px; padding: 8px 14px;
  border: 1px solid #ccd2da; border-radius: 4px; background: #fff;
}
button { cursor: pointer; }
button:hover { background: #eef1f5; }

.cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%,120px), 1fr)); gap: 12px; }

.card {
  background: #fff; border: 1px solid #e3e7ec; border-radius: 6px;
  padding: 16px 12px; text-align: center;
}
.card .date { font-size: 13px; color: #6b727c; }
.card .icon { font-size: 32px; line-height: 1.4; }
.card .desc { font-size: 12px; color: #6b727c; min-height: 2.4em; }
.card .temp { font-variant-numeric: tabular-nums; }
.card .max { color: #c2483a; font-weight: 700; }
.card .min { color: #3a6fc2; }

.loading, .empty { text-align: center; padding: 40px; color: #6b727c; }

.error-box {
  border: 1px solid #e5b4ad; background: #fdf3f1;
  padding: 20px; border-radius: 6px; text-align: center;
}
.error-box p { color: #a33a2a; margin: 0 0 12px; }

/* 読み込み中のアニメーション */
.spinner {
  display: inline-block; width: 24px; height: 24px;
  border: 3px solid #dfe3e8; border-top-color: #6b727c;
  border-radius: 50%; animation: spin .8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
@media (prefers-reduced-motion: reduce) { .spinner { animation: none; } }
```

### JavaScript（📋 完成形は `code/02-10/js/weather.js`）

`js/weather.js`

```javascript
// ==========================================================
// 定数
// ==========================================================

const API_BASE = "https://api.open-meteo.com/v1/forecast";

// WMO 天気コード → 絵文字と日本語
const WEATHER = {
  0:  ["☀️", "快晴"],
  1:  ["🌤️", "晴れ"],
  2:  ["⛅", "一部くもり"],
  3:  ["☁️", "くもり"],
  45: ["🌫️", "霧"],
  48: ["🌫️", "霧氷"],
  51: ["🌦️", "霧雨（弱）"],
  53: ["🌦️", "霧雨"],
  55: ["🌦️", "霧雨（強）"],
  61: ["🌧️", "雨（弱）"],
  63: ["🌧️", "雨"],
  65: ["🌧️", "雨（強）"],
  71: ["🌨️", "雪（弱）"],
  73: ["🌨️", "雪"],
  75: ["🌨️", "雪（強）"],
  80: ["🌦️", "にわか雨"],
  81: ["🌦️", "にわか雨"],
  82: ["⛈️", "激しいにわか雨"],
  95: ["⛈️", "雷雨"],
};

// ==========================================================
// ユーティリティ
// ==========================================================

const escapeHtml = (str) =>
  String(str)
    .replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;").replaceAll("'", "&#39;");

/** JSON を取得する。HTTPエラーとタイムアウトを例外にする */
const fetchJson = async (url, timeoutMs = 10000) => {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);

  try {
    const res = await fetch(url, { signal: controller.signal });
    if (!res.ok) throw new Error(`サーバーエラー（${res.status}）が発生しました`);
    return await res.json();
  } catch (error) {
    if (error.name === "AbortError") {
      throw new Error("通信がタイムアウトしました。電波状況をご確認ください。");
    }
    if (error instanceof TypeError) {
      throw new Error("ネットワークに接続できません。");
    }
    throw error;
  } finally {
    clearTimeout(timer);
  }
};

const formatDate = (iso) => {
  const d = new Date(iso);
  const days = ["日", "月", "火", "水", "木", "金", "土"];
  return `${d.getMonth() + 1}/${d.getDate()}(${days[d.getDay()]})`;
};

// ==========================================================
// 状態
// ==========================================================

const state = {
  status: "idle",     // idle | loading | success | error
  days: [],
  error: null,
};

// ==========================================================
// 描画
// ==========================================================

const app = document.querySelector("#app");

const dayCardHtml = (day) => {
  const [icon, desc] = WEATHER[day.code] ?? ["❓", "不明"];
  return `
    <div class="card">
      <p class="date">${escapeHtml(formatDate(day.date))}</p>
      <p class="icon" aria-hidden="true">${icon}</p>
      <p class="desc">${escapeHtml(desc)}</p>
      <p class="temp">
        <span class="max">${day.max}°</span> /
        <span class="min">${day.min}°</span>
      </p>
    </div>
  `;
};

const render = () => {
  if (state.status === "loading") {
    app.innerHTML = '<p class="loading"><span class="spinner"></span><br>読み込み中…</p>';
    return;
  }

  if (state.status === "error") {
    app.innerHTML = `
      <div class="error-box">
        <p>${escapeHtml(state.error)}</p>
        <button type="button" id="retry">再試行</button>
      </div>
    `;
    return;
  }

  if (state.days.length === 0) {
    app.innerHTML = '<p class="empty">表示できるデータがありません</p>';
    return;
  }

  app.innerHTML = `<div class="cards">${state.days.map(dayCardHtml).join("")}</div>`;
};

// ==========================================================
// データ取得
// ==========================================================

/** APIのレスポンスを、扱いやすい形に変換する */
const toDays = (json) => {
  const d = json.daily;
  if (!d?.time) return [];

  return d.time.map((date, i) => ({
    date,
    code: d.weather_code[i],
    max: Math.round(d.temperature_2m_max[i]),
    min: Math.round(d.temperature_2m_min[i]),
  }));
};

const load = async (lat, lon) => {
  state.status = "loading";
  render();

  const params = new URLSearchParams({
    latitude: lat,
    longitude: lon,
    daily: "weather_code,temperature_2m_max,temperature_2m_min",
    timezone: "Asia/Tokyo",
    forecast_days: "5",
  });

  try {
    const json = await fetchJson(`${API_BASE}?${params}`);
    state.days = toDays(json);
    state.status = "success";
    state.error = null;
  } catch (error) {
    state.status = "error";
    state.error = error.message;
    console.error(error);
  }

  render();
};

// ==========================================================
// イベント
// ==========================================================

const citySelect = document.querySelector("#city");

const loadSelectedCity = () => {
  const [lat, lon] = citySelect.value.split(",");
  load(lat, lon);
};

citySelect.addEventListener("change", loadSelectedCity);
document.querySelector("#reload").addEventListener("click", loadSelectedCity);

// 再試行ボタン（描き直されるのでイベント委譲）
app.addEventListener("click", (e) => {
  if (e.target.closest("#retry")) loadSelectedCity();
});

// 初回読み込み
loadSelectedCity();
```

### 動作確認

- [ ] ページを開くと、東京の5日間の天気が出る
- [ ] 都市を変えると、切り替わる
- [ ] 読み込み中にスピナーが出る（開発者ツールの Network で Slow 3G にすると見える）
- [ ] **オフラインにすると**、エラーメッセージと再試行ボタンが出る
      （開発者ツール → Network → Throttling を「Offline」に）
- [ ] URLをわざと間違えると、HTTPエラーのメッセージが出る

> 💡 **「わざと失敗させて確認する」**のが重要です。
> エラー処理は、失敗させないとテストできません。開発者ツールの Throttling を活用してください。

### `URLSearchParams` について

```javascript
const params = new URLSearchParams({ latitude: 35.68, longitude: 139.76 });
console.log(params.toString());   // latitude=35.68&longitude=139.76
```

**クエリ文字列を安全に組み立てられます。** 手で `?a=1&b=2` と繋ぐと、特殊文字のエスケープを忘れて壊れます。

---

## ✍️ 手を動かす⑥ ─ 複数の通信を並行させる

```javascript
// ❌ 順番に待つ（3秒 + 3秒 = 6秒）
const a = await fetchJson(url1);
const b = await fetchJson(url2);

// ✅ 同時に投げる（3秒で両方完了）
const [a, b] = await Promise.all([
  fetchJson(url1),
  fetchJson(url2),
]);
```

| メソッド | 挙動 |
| --- | --- |
| `Promise.all([...])` | **1つでも失敗したら全体が失敗** |
| `Promise.allSettled([...])` | 全部の結果（成功・失敗）を返す |
| `Promise.race([...])` | **最初に終わった1つ**の結果 |

```javascript
// 一部が失敗してもよい場合
const results = await Promise.allSettled([fetchJson(u1), fetchJson(u2)]);
results.forEach((r) => {
  if (r.status === "fulfilled") console.log(r.value);
  else console.error(r.reason);
});
```

---

## ⚠️ つまずきポイントまとめ

| 症状 | 原因 | 対処 |
| --- | --- | --- |
| データが `Promise { <pending> }` | `await` の付け忘れ | `await` を付ける |
| `await is only valid in async functions` | `async` を付けていない | 関数に `async` |
| 404 なのに catch に入らない | fetch は HTTPエラーで reject しない | `res.ok` を確認する |
| `CORS policy` のエラー | サーバー側が許可していない | **フロント側では解決できない**。APIの仕様を確認 |
| POST でデータが届かない | `JSON.stringify()` を忘れた | body を文字列にする |
| 通信が終わらない | タイムアウトがない | `AbortController` を使う |
| 通信中に画面が真っ白 | ローディング状態を作っていない | 4状態すべてを実装 |
| `console.log` で見ると空 | 取得前に出力している | 通信の後で出力する |

### CORS エラーについて

```
Access to fetch at 'https://...' from origin 'http://localhost' has been
blocked by CORS policy
```

**これはあなたのコードのバグではありません。**

ブラウザは、セキュリティのため**別ドメインへの通信を制限**しています。サーバー側が「このドメインからのアクセスを許可する」というヘッダーを返さないと、通信できません。

| 状況 | 対処 |
| --- | --- |
| 公開APIを使っている | そのAPIがCORSに対応しているか確認。していなければ使えない |
| 自分のサーバーを叩いている | **サーバー側**でCORSヘッダーを設定（第3部・第5部） |
| Live Server で `file://` を開いている | `http://localhost` で開く |

> ⚠️ **「CORS を無効にする拡張機能」は、開発でも使わないでください。** 本番で必ず破綻します。

---

## 🤖 AIに聞いてみよう

### ① API のレスポンスから型を読み解く

```text
以下は、あるAPIから返ってきたJSONです。

（レスポンスを貼る。長ければ一部でOK）

1. このデータの構造を、階層がわかる形で説明してください
2. 画面に「日付・天気・最高気温・最低気温」を表示したい場合、
   どのキーを使えばよいですか
3. このデータを扱いやすい形に変換する関数の「設計方針」を教えてください
   （コードは書かないでください）
4. 値が欠けている可能性があるキーはどれですか？その対策は？
```

### ② エラーハンドリングをレビューさせる

```text
以下は、私が書いた fetch のエラーハンドリングです。

（コードを貼る）

次の観点でレビューしてください。

1. 考慮できていないエラーケース
2. ユーザーに表示するメッセージが適切か（技術的すぎないか、対処法が伝わるか）
3. ローディング・空・エラーの状態がすべて扱えているか
4. リトライやタイムアウトの実装に問題はないか

修正後のコードは書かず、指摘だけをお願いします。
```

---

## 🔧 やってみよう（演習）

**AIに頼らず、自力でやってください。**

### 演習1（必須）

天気ウィジェットに以下を追加してください。

- [ ] 表示日数を選べるようにする（3日 / 7日 / 14日）
- [ ] 降水確率を表示する（APIの `daily` に `precipitation_probability_max` を追加）
- [ ] 最終更新時刻を表示する（「12:34 更新」）
- [ ] 都市を「現在地」から取得するボタンを作る
      （ヒント：`navigator.geolocation.getCurrentPosition`）

> 💡 APIのパラメータは、Open-Meteo の公式ドキュメントで確認してください。
> https://open-meteo.com/en/docs

### 演習2（必須）

以下のコードの問題点を、それぞれ指摘して直してください。

```javascript
// ①
const data = fetch("/api/items").then(res => res.json());
console.log(data);

// ②
const load = () => {
  const res = await fetch(url);
};

// ③
try {
  const res = await fetch("/api/items");
  const data = await res.json();
  render(data);
} catch (e) {
  console.log("エラー");
}

// ④
const save = async (item) => {
  await fetch("/api/items", {
    method: "POST",
    body: item,
  });
};

// ⑤
const loadAll = async () => {
  const users = await fetchJson("/api/users");
  const posts = await fetchJson("/api/posts");
  const tags  = await fetchJson("/api/tags");
  return { users, posts, tags };
};
```

<details>
<summary>答えを見る</summary>

① **`await` がない**ので `data` に Promise が入る。
```javascript
const data = await fetch("/api/items").then(res => res.json());
// または
const res = await fetch("/api/items");
const data = await res.json();
```

② **`async` がない**ので `await` が使えない（SyntaxError）。
```javascript
const load = async () => {
  const res = await fetch(url);
};
```

③ **`res.ok` を確認していない**ので、404 でも `res.json()` に進む（多くの場合そこで別のエラーになる）。
また、**エラーメッセージがユーザーに伝わらない**。
```javascript
try {
  const res = await fetch("/api/items");
  if (!res.ok) throw new Error(`サーバーエラー（${res.status}）`);
  const data = await res.json();
  render(data);
} catch (e) {
  console.error(e);
  showError("データの取得に失敗しました。時間をおいてお試しください。");
}
```

④ **`JSON.stringify()` と `Content-Type` がない**。
```javascript
await fetch("/api/items", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify(item),
});
```

⑤ **3つの通信を順番に待っている**（依存関係がないので並行にできる）。
```javascript
const [users, posts, tags] = await Promise.all([
  fetchJson("/api/users"),
  fetchJson("/api/posts"),
  fetchJson("/api/tags"),
]);
return { users, posts, tags };
```

</details>

### 演習3（挑戦）

**別のAPIで、同じ構造のウィジェットを作ってください。**

APIキー不要で使える公開APIの例：

| API | URL | 内容 |
| --- | --- | --- |
| 郵便番号検索 | `https://zipcloud.ibsnet.co.jp/api/search?zipcode=1000001` | 郵便番号→住所 |
| 祝日一覧 | `https://holidays-jp.github.io/api/v1/date.json` | 日本の祝日 |
| 為替レート | `https://api.frankfurter.app/latest?from=USD&to=JPY` | 通貨換算 |

- [ ] まずブラウザでURLを開き、レスポンスの構造を確認する
- [ ] `fetchJson` テンプレートを流用する
- [ ] 4つの状態（loading / success / empty / error）をすべて実装する
- [ ] オフラインにして、エラー表示を確認する

---

## ✅ 章末チェック

- [ ] 同期と非同期の違いを説明できる
- [ ] `async` / `await` の3つのルールを言える
- [ ] `fetch` が404で reject しない理由と対処を説明できる
- [ ] `res.ok` を必ず確認する
- [ ] POST で `JSON.stringify()` が必要な理由を言える
- [ ] 4つの状態（loading / success / empty / error）をすべて実装した
- [ ] `Promise.all` で並行実行できる
- [ ] CORS エラーがフロント側で解決できない理由を説明できる
- [ ] オフラインにしてエラー表示を確認した

---

**前 → [2-9 エラーの読み方とデバッグ手順](02-09-debugging.md)　｜　次 → [2-11 localStorage でデータを保存する](02-11-storage.md)**
