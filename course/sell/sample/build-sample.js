/*
 * 無料サンプルHTMLを生成するスクリプト
 * 使い方:  node build-sample.js [レッスンID]
 *   例)   node build-sample.js 0-7      （既定は 0-7）
 *         node build-sample.js 1-1
 * 出力:  ./サンプルレッスン.html
 * 前提:  ../../web/lessons.json（教材本文データ）が存在すること
 */
const fs = require("fs");
const path = require("path");
const { mdToHtml, esc } = require("./render.js");

const DIR = __dirname;
const LESSONS = path.join(DIR, "..", "..", "web", "lessons.json");
const SAMPLE_ID = process.argv[2] || "0-7";

const docs = JSON.parse(fs.readFileSync(LESSONS, "utf8"));
if (!docs[SAMPLE_ID]) { console.error("レッスンが見つかりません:", SAMPLE_ID); process.exit(1); }
const lessonHtml = mdToHtml(docs[SAMPLE_ID]);
const sampleTitle = (docs[SAMPLE_ID].match(/^#\s+(.*)$/m) || [,"サンプルレッスン"])[1].replace(/^\d+-\d+\s*/, "").replace(/`/g, "");

function firstTitle(md){ const m = md.match(/^#\s+(.*)$/m); return m ? m[1].trim() : ""; }
const partNames = {
  "0":"第0部 オリエンテーション（AI入門・思考法）","1":"第1部 HTML / CSS","2":"第2部 JavaScript",
  "3":"第3部 PHP","4":"第4部 データベースとSQL","5":"第5部 Laravel","6":"第6部 ポートフォリオ制作と公開"
};
const ids = Object.keys(docs).filter(k=>/^\d+-\d+$/.test(k))
  .sort((a,b)=>{const[ap,an]=a.split("-").map(Number),[bp,bn]=b.split("-").map(Number);return ap-bp||an-bn;});
let curr = "";
for (const p of Object.keys(partNames)) {
  const ks = ids.filter(k=>k.split("-")[0]===p);
  curr += `<div class="cpart"><h3>${esc(partNames[p])}</h3><ul>`;
  ks.forEach(k=>{
    const t = firstTitle(docs[k]).replace(/^\d+-\d+\s*/,"").replace(/`/g,"");
    curr += `<li><span class="cid">${k}</span>${esc(t)}${k===SAMPLE_ID?' <span class="here">◀ このサンプル</span>':""}</li>`;
  });
  curr += `</ul></div>`;
}

const css = `
:root{--paper:#F3F8F5;--surface:#FFFFFF;--sunk:#E9F1EC;--ink:#233029;--ink2:#55615A;--ink3:#6E7A73;
--rule:#D3E0D8;--rule-soft:#E4EDE7;--teal:#0F766E;--tealDk:#0B5A54;--tealSoft:#DBEDE8;
--ai:#6A4FA3;--aiSoft:#EBE7F6;--warn:#8A6300;--warnSoft:#F6EED8;--dark:#12332F;--mint:#2FBBA6;}
*,*::before,*::after{box-sizing:border-box}
body{margin:0;background:var(--paper);color:var(--ink);
 font-family:"Noto Sans JP","Hiragino Sans","Yu Gothic",system-ui,sans-serif;font-size:16px;line-height:1.8;-webkit-font-smoothing:antialiased}
.masthead{background:var(--dark);color:#fff;padding:12px 20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.masthead .wm{font-weight:900;font-size:15px}.masthead .wm span{color:var(--mint)}
.badge{margin-left:auto;background:var(--mint);color:#06231f;font-weight:700;font-size:12px;letter-spacing:.08em;padding:3px 12px;border-radius:999px}
.wrap{max-width:820px;margin:0 auto;padding:0 20px 80px}
.intro{padding:34px 0 26px;border-bottom:1px solid var(--rule);margin-bottom:34px}
.eyebrow{color:var(--teal);font-weight:700;font-size:13px;letter-spacing:.1em;margin-bottom:10px}
.intro h1{font-size:clamp(24px,4.5vw,34px);font-weight:900;margin:0 0 12px;line-height:1.35}
.intro p{color:var(--ink2);font-size:14.5px;margin:0}
.note{display:inline-block;margin-top:14px;background:var(--tealSoft);color:var(--tealDk);font-size:13px;padding:8px 14px;border-radius:8px}
.md>*:first-child{margin-top:0}
.md h1{font-size:26px;font-weight:900;line-height:1.35;margin:0 0 8px}
.md h2{font-size:20px;margin:34px 0 12px;padding-bottom:8px;border-bottom:1px solid var(--rule)}
.md h3{font-size:16px;margin:26px 0 8px}.md h4{font-size:14px;margin:20px 0 6px;color:var(--ink2)}
.md p{margin:0 0 15px}.md ul,.md ol{margin:0 0 16px;padding-left:1.5em}.md li{margin-bottom:6px}
.md a{color:var(--teal)}.md strong{font-weight:700}.md hr{border:0;border-top:1px solid var(--rule);margin:28px 0}
.md code{font-family:"Noto Sans Mono",monospace;font-size:.9em;background:var(--sunk);padding:1px 5px;border-radius:4px;word-break:break-word}
.md pre{background:var(--sunk);border:1px solid var(--rule-soft);border-radius:8px;padding:14px 16px;overflow-x:auto;margin:0 0 16px;white-space:pre-wrap;word-break:break-word;line-height:1.7}
.md pre code{font-size:12.5px;background:none;padding:0}
.md blockquote{margin:0 0 16px;padding:13px 18px;border-left:4px solid var(--rule);background:var(--sunk);border-radius:0 8px 8px 0}
.md blockquote>*:last-child{margin-bottom:0}
.md blockquote.goal{border-left-color:var(--ink)}
.md blockquote.warn,.md blockquote.rescue{border-left-color:var(--warn);background:var(--warnSoft)}
.md blockquote.ai{border-left-color:var(--ai);background:var(--aiSoft)}
.md blockquote.tip{border-left-color:var(--teal);background:var(--tealSoft)}
.md .tblwrap{overflow-x:auto;margin:0 0 18px;border:1px solid var(--rule);border-radius:8px}
.md table{border-collapse:collapse;width:100%;font-size:13.5px;background:var(--surface);margin:0}
.md th,.md td{border-bottom:1px solid var(--rule-soft);padding:9px 12px;text-align:left;vertical-align:top}
.md th{background:var(--sunk);font-size:11.5px;letter-spacing:.06em;color:var(--ink3);white-space:nowrap}
.md tr:last-child td{border-bottom:0}
.md details{margin:0 0 16px}.md summary{font-weight:700;color:var(--tealDk);cursor:pointer}
.overview{margin-top:48px;padding-top:30px;border-top:2px solid var(--ink)}
.overview>h2{font-size:20px;margin:0 0 6px}.overview>p{color:var(--ink2);font-size:14px;margin:0 0 20px}
.cpart{margin-bottom:18px}.cpart h3{font-size:14px;color:var(--teal);margin:0 0 6px}
.cpart ul{list-style:none;margin:0;padding:0}.cpart li{font-size:13px;padding:3px 0;border-bottom:1px solid var(--rule-soft)}
.cpart .cid{font-family:"Noto Sans Mono",monospace;font-size:11px;color:var(--ink3);margin-right:10px}.cpart .here{color:var(--teal);font-weight:700;font-size:11.5px}
.cta{margin-top:44px;background:var(--dark);color:#fff;border-radius:14px;padding:34px 30px;text-align:center}
.cta h2{color:#fff;font-size:24px;margin:0 0 10px;border:0}.cta p{color:#CFE0D8;font-size:14.5px;margin:0 auto 6px;max-width:34em}.cta .big{color:var(--mint);font-weight:700}
.foot{margin-top:40px;color:var(--ink3);font-size:12px;line-height:1.9;border-top:1px solid var(--rule);padding-top:20px}
@media print{body{background:#fff}.masthead,.cta{-webkit-print-color-adjust:exact;print-color-adjust:exact}}
`;

const html = `<!doctype html><html lang="ja"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>サンプルレッスン｜AIと一緒につくるWebアプリ開発入門</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700;900&family=Noto+Sans+Mono:wght@400;500&display=swap">
<style>${css}</style></head><body>
<header class="masthead"><span class="wm">AIと一緒につくる<span>／</span>Webアプリ開発入門</span><span class="badge">無料サンプル</span></header>
<div class="wrap">
  <div class="intro">
    <div class="eyebrow">SAMPLE LESSON ／ 無料お試し</div>
    <h1>サンプルレッスン：${esc(sampleTitle)}</h1>
    <p>全7部・全83レッスンのうちの1本（レッスン ${SAMPLE_ID}）を、<strong>実際の教材そのままの形式</strong>で公開しています。文章の密度・つまずき対策・AIの使い方まで、この一本で教材の雰囲気がわかります。</p>
    <span class="note">このページは無料サンプルです。続きの82レッスンと完成コードは本編に収録しています。</span>
  </div>
  <article class="md">
${lessonHtml}
  </article>
  <section class="overview">
    <h2>この教材の全体像（全7部・全83レッスン）</h2>
    <p>サンプルはこのうちの1本です。全体像はこちら。</p>
    ${curr}
  </section>
  <section class="cta">
    <h2>続きは、本編で。</h2>
    <p>パソコンの基本操作から <span class="big">Laravelでアプリを作りきる</span> まで。手を動かす総合演習が5本、AIとの付き合い方と「考える力」まで学べます。</p>
    <p>※ 効果には個人差があります。「必ず稼げる／誰でも簡単に」といった保証はしていません。近道の代わりに、続けられる設計で着実な前進を支えます。</p>
  </section>
  <div class="foot">本サンプルは無料でご覧いただけます。教材本体は購入者個人の学習用です。無断での再配布・転載を禁じます。<br>掲載サンプルコードは学習・ポートフォリオ・商用問わず自由に利用いただけます。</div>
</div>
</body></html>`;

fs.writeFileSync(path.join(DIR, "サンプルレッスン.html"), html);
console.log("wrote サンプルレッスン.html (lesson " + SAMPLE_ID + ", " + Math.round(Buffer.byteLength(html)/1024) + " KB)");
