const P = require("pptxgenjs");
const p = new P();
p.layout = "LAYOUT_WIDE";               // 13.33 x 7.5
const W = 13.33, H = 7.5, M = 0.7;
const F = "Meiryo";
const C = {
  dark: "12332F", teal: "0F766E", tealDk: "0B5A54", mint: "2FBBA6",
  paper: "FFFFFF", soft: "EDF4F1", ink: "233029", ink2: "55615A", ink3: "7C857E",
  ai: "6A4FA3", aiSoft: "EEE9F7", white: "FFFFFF", warmInk: "9A6300"
};

function bg(s, color) { s.background = { color }; }
function title(s, t, color) {
  s.addText(t, { isTextBox: true, x: M, y: 0.55, w: W - M * 2, h: 0.95, fontFace: F,
    fontSize: 32, bold: true, color: color || C.ink, align: "left", valign: "middle", margin: 0 });
}
function card(s, x, y, w, h, header, body) {
  s.addText(
    [{ text: header, options: { bold: true, fontSize: 16, color: C.teal, breakLine: true } },
     { text: body, options: { fontSize: 12.5, color: C.ink2 } }],
    { isTextBox: true, x, y, w, h, fontFace: F, align: "left", valign: "top",
      shape: p.ShapeType.roundRect, rectRadius: 0.08, fill: { color: C.soft }, line: { type: "none" },
      margin: 10 });
}
function numCircle(s, x, y, n) {
  s.addText(String(n), { isTextBox: true, x, y, w: 0.62, h: 0.62, fontFace: F, fontSize: 20, bold: true,
    color: C.white, align: "center", valign: "middle", shape: p.ShapeType.oval,
    fill: { color: C.teal }, line: { type: "none" }, margin: 0 });
}

/* 1 ── Title (dark) */
let s = p.addSlide(); bg(s, C.dark);
s.addText("パソコン未経験から、一人で作りきるまで",
  { isTextBox: true, x: M, y: 1.55, w: W - M * 2, h: 0.5, fontFace: F, fontSize: 15, color: C.mint, charSpacing: 2, margin: 0 });
s.addText(
  [{ text: "AIに書かせるな。", options: { color: C.white, breakLine: true } },
   { text: "AIを疑え。", options: { color: C.mint } }],
  { isTextBox: true, x: M, y: 2.1, w: W - M * 2, h: 2.3, fontFace: F, fontSize: 54, bold: true, lineSpacingMultiple: 1.05, margin: 0 });
s.addText("AIと一緒につくる Webアプリ開発入門",
  { isTextBox: true, x: M, y: 4.6, w: W - M * 2, h: 0.5, fontFace: F, fontSize: 20, color: "CFE0D8", margin: 0 });
s.addText("全7部・全83レッスン ／ 完成コード付き ／ HTML・CSS・JavaScript・PHP・MySQL・Laravel",
  { isTextBox: true, x: M, y: 6.4, w: W - M * 2, h: 0.5, fontFace: F, fontSize: 13, color: C.mint, margin: 0 });
s.addNotes("（つかみ）AIに、コードを書かせる時代になりました。でも――AIは平気で間違えます。この講座のテーマはひとつ。『AIに書かせるな。AIを疑え』。AIを鵜呑みにせず、使いこなして、自分で作れる人になる。パソコン未経験の方が、Laravelでアプリを作りきるまでを、一本道で学びます。");

/* 2 ── Why people quit (light, 2x2) */
s = p.addSlide(); bg(s, C.paper);
title(s, "なぜ、プログラミング学習は続かないのか");
s.addText("原因は、才能でも意志でもありません。次の4つ。そのすべてを、教材の設計で潰します。",
  { isTextBox: true, x: M, y: 1.5, w: W - M * 2, h: 0.5, fontFace: F, fontSize: 15, color: C.ink2, margin: 0 });
const gx = M, gy = 2.25, gw = (W - M * 2 - 0.4) / 2, gh = 2.05, gap = 0.4;
card(s, gx, gy, gw, gh, "① 環境構築で詰む", "「書いてある通りにやったのに動かない」。この教材は、うまくいかない時の分岐まで全部書きます。");
card(s, gx + gw + gap, gy, gw, gh, "② エラーが読めず止まる", "英語のエラーは3か所だけ拾えば読める。全レッスンに対策、巻末に症状別「エラー図鑑」。");
card(s, gx, gy + gh + gap, gw, gh, "③ 写経しても身につかない", "読む→打つ→AIに説明させる→自分で変える。この4ステップを毎回踏みます。");
card(s, gx + gw + gap, gy + gh + gap, gw, gh, "④ AIに丸投げして詰む", "AI活用を独立した学習項目に。聞き方・検証・任せてはいけない範囲まで教えます。");
s.addNotes("（悩み）プログラミング学習の挫折率は高いと言われます。でも原因は驚くほど決まっていて、この4つです。環境構築で詰む。エラーが読めず止まる。写経しても身につかない。そしてAIに丸投げして詰む。この講座は、この4つを潰すためだけに作りました。");

/* 3 ── AI pitfall (light, callout + points) */
s = p.addSlide(); bg(s, C.paper);
title(s, "便利なAIには、落とし穴がある");
s.addText(
  [{ text: "「動いた」", options: { color: C.ai, bold: true } },
   { text: " ＝ ", options: { color: C.ink2 } },
   { text: "「正しい」", options: { color: C.ai, bold: true } },
   { text: " ではない", options: { color: C.ink } }],
  { isTextBox: true, x: M, y: 2.0, w: 6.2, h: 2.6, fontFace: F, fontSize: 26, bold: true, align: "center", valign: "middle",
    shape: p.ShapeType.roundRect, rectRadius: 0.1, fill: { color: C.aiSoft }, line: { type: "none" }, margin: 12 });
s.addText(
  [{ text: "存在しない関数を提案する", options: { breakLine: true, bold: true, color: C.ink } },
   { text: "（例：strreverse — 実在しない）\n\n", options: { color: C.ink2, fontSize: 12, breakLine: true } },
   { text: "情報が古い・バージョンがずれる", options: { breakLine: true, bold: true, color: C.ink } },
   { text: "（削除済みの書き方を返す）\n\n", options: { color: C.ink2, fontSize: 12, breakLine: true } },
   { text: "脆弱性を含んだまま「動く」", options: { breakLine: true, bold: true, color: C.ink } },
   { text: "（SQLインジェクション/XSS）", options: { color: C.ink2, fontSize: 12 } }],
  { isTextBox: true, x: 7.2, y: 1.9, w: W - 7.2 - M, h: 3.4, fontFace: F, fontSize: 15, align: "left", valign: "middle", margin: 0 });
s.addNotes("（AIの落とし穴）AIはとても便利です。でも、平気で間違えます。存在しない関数を提案したり、古い情報を返したり。いちばん怖いのは、脆弱性を含んだまま『動いてしまう』こと。動くから、初学者には問題が見えません。だからこの講座は、『動いた＝正しい』という思い込みを、最初に捨てます。");

/* 4 ── 3 promises (light, numbered rows) */
s = p.addSlide(); bg(s, C.paper);
title(s, "だから、この教材は3つを約束します");
const rows = [
  ["手順は「分岐」まで書く", "「〇〇して」で終わらせない。うまくいかなかった時にどうするか、まで書く。"],
  ["AIの使い方を「技術」として教える", "何を聞くか、答えをどう検証するか、どこから自分で理解すべきか、を独立して扱う。"],
  ["必ず「動くもの」が手元に残る", "各部の最後に総合演習。修了時、GitHubに5つの作品が並びます。"]
];
let ry = 1.9;
rows.forEach((r, i) => {
  numCircle(s, M, ry, i + 1);
  s.addText(
    [{ text: r[0], options: { bold: true, fontSize: 18, color: C.ink, breakLine: true } },
     { text: r[1], options: { fontSize: 13.5, color: C.ink2 } }],
    { isTextBox: true, x: M + 0.85, y: ry - 0.1, w: W - M - 0.85 - M, h: 1.3, fontFace: F, valign: "top", margin: 0 });
  ry += 1.55;
});
s.addNotes("（解決）そこで、この教材は3つを約束します。1つ、手順は分岐まで書く。2つ、AIの使い方を技術として教える。3つ、必ず動くものが残る。修了する頃には、あなたのGitHubに5つの作品が並びます。");

/* 5 ── 5 deliverables (light, 5 cards row) */
s = p.addSlide(); bg(s, C.paper);
title(s, "手を動かして、5つの作品が残る");
const items = [
  ["1", "カフェのLP", "HTML / CSS"],
  ["2", "ToDoアプリ", "JavaScript"],
  ["3", "掲示板", "PHP + MySQL"],
  ["4", "フリマアプリ", "Laravel・認証つき"],
  ["5", "オリジナル作品", "あなたの企画"]
];
const cw = (W - M * 2 - 0.4 * 4) / 5, cy = 2.4, ch = 2.6;
items.forEach((it, i) => {
  const x = M + i * (cw + 0.4);
  s.addText(
    [{ text: it[0], options: { fontSize: 30, bold: true, color: C.teal, breakLine: true } },
     { text: it[1], options: { fontSize: 15, bold: true, color: C.ink, breakLine: true } },
     { text: it[2], options: { fontSize: 11.5, color: C.ink2 } }],
    { isTextBox: true, x, y: cy, w: cw, h: ch, fontFace: F, align: "center", valign: "middle",
      shape: p.ShapeType.roundRect, rectRadius: 0.09, fill: { color: C.soft }, line: { type: "none" }, margin: 8 });
});
s.addNotes("（成果物）学ぶために作るのではなく、作るために学びます。カフェのランディングページ、ToDoアプリ、掲示板、そして認証つきのフリマアプリ。最後は、あなた自身のオリジナル作品。5本すべてが、ポートフォリオになります。");

/* 6 ── AI-era skills (dark teal feature) */
s = p.addSlide(); bg(s, C.dark);
title(s, "AIに任せられない力まで、学ぶ", C.white);
s.addText("技術は変わり続けます。でも、この2つはどんな時代にも効き続けます。",
  { isTextBox: true, x: M, y: 1.5, w: W - M * 2, h: 0.5, fontFace: F, fontSize: 15, color: C.mint, margin: 0 });
const fw = (W - M * 2 - 0.5) / 2;
s.addText(
  [{ text: "クリティカルシンキング\n", options: { bold: true, fontSize: 22, color: C.white, breakLine: true } },
   { text: "AIや情報を鵜呑みにせず、主張・根拠・前提を分けて検証する力。", options: { fontSize: 14, color: "CFE0D8" } }],
  { isTextBox: true, x: M, y: 2.5, w: fw, h: 2.6, fontFace: F, valign: "middle",
    shape: p.ShapeType.roundRect, rectRadius: 0.1, fill: { color: "13413A" }, line: { color: C.teal, width: 1 }, margin: 16 });
s.addText(
  [{ text: "仕様で考える力\n", options: { bold: true, fontSize: 22, color: C.white, breakLine: true } },
   { text: "「何を・なぜ作るか」を決める力。AIに任せられない、いちばん価値の高い仕事。", options: { fontSize: 14, color: "CFE0D8" } }],
  { isTextBox: true, x: M + fw + 0.5, y: 2.5, w: fw, h: 2.6, fontFace: F, valign: "middle",
    shape: p.ShapeType.roundRect, rectRadius: 0.1, fill: { color: "13413A" }, line: { color: C.teal, width: 1 }, margin: 16 });
s.addNotes("（差別化）そしてこの講座は、AIに任せられない人間の力まで扱います。クリティカルシンキング――鵜呑みにせず検証する力。そして仕様で考える力――何を、なぜ作るかを決める力。技術が変わっても通用する、一生モノのスキルです。");

/* 7 ── Audience + stats (light) */
s = p.addSlide(); bg(s, C.paper);
title(s, "こんな人のための教材です");
s.addText(
  [{ text: "プログラミング未経験〜独学で挫折した人", options: { breakLine: true, bullet: true } },
   { text: "副業・転職・社内DXで「作れる力」が欲しい人", options: { breakLine: true, bullet: true } },
   { text: "AIを「なんとなく」使っていて、開発で活かしたい人", options: { bullet: true } }],
  { isTextBox: true, x: M, y: 1.9, w: 7.0, h: 3.4, fontFace: F, fontSize: 16, color: C.ink, paraSpaceAfter: 14, valign: "top", margin: 0 });
const stats = [["83", "レッスン"], ["約180", "時間"], ["5", "作品が残る"]];
let sy = 1.9;
stats.forEach((st) => {
  s.addText(
    [{ text: st[0] + "  ", options: { fontSize: 40, bold: true, color: C.teal } },
     { text: st[1], options: { fontSize: 16, color: C.ink2 } }],
    { isTextBox: true, x: 8.0, y: sy, w: W - 8.0 - M, h: 1.0, fontFace: F, align: "left", valign: "middle", margin: 0 });
  sy += 1.15;
});
s.addNotes("（対象）この教材は、完全未経験の方や、独学で一度挫折した方のために作りました。副業や転職、社内DXで『自分で作れる力』が欲しい方にも。全83レッスン、約180時間。終える頃には、5つの作品が手元に残ります。");

/* 8 ── CTA (dark) */
s = p.addSlide(); bg(s, C.dark);
s.addText(
  [{ text: "AIを相棒に、\n", options: { color: C.white, breakLine: true } },
   { text: "「自分で作れる人」へ。", options: { color: C.mint } }],
  { isTextBox: true, x: M, y: 2.2, w: W - M * 2, h: 2.4, fontFace: F, fontSize: 44, bold: true, align: "center", valign: "middle", lineSpacingMultiple: 1.1, margin: 0 });
s.addText("まずは、無料プレビューのレッスンからご覧ください。",
  { isTextBox: true, x: M, y: 4.7, w: W - M * 2, h: 0.6, fontFace: F, fontSize: 18, color: "CFE0D8", align: "center", margin: 0 });
s.addText("AIと一緒につくる Webアプリ開発入門",
  { isTextBox: true, x: M, y: 6.5, w: W - M * 2, h: 0.5, fontFace: F, fontSize: 13, color: C.mint, align: "center", margin: 0 });
s.addNotes("（CTA）技術は、AIという最強の相棒とともに、あなたのものになります。でも決めるのは、いつもあなた自身。さあ、AIを相棒に、自分で作れる人になりましょう。まずは、無料プレビューのレッスンからご覧ください。お待ちしています。");

p.writeFile({ fileName: "プロモ動画.pptx" }).then(f => console.log("wrote", f));
