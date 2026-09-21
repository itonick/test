const P = require("pptxgenjs");
const p = new P();
p.layout = "LAYOUT_WIDE";
const W = 13.33, H = 7.5, M = 0.7;
const F = "Meiryo";
const C = {
  dark: "12332F", teal: "0F766E", tealDk: "0B5A54", mint: "2FBBA6",
  paper: "FFFFFF", soft: "EDF4F1", ink: "233029", ink2: "55615A", ink3: "7C857E",
  ai: "6A4FA3", aiSoft: "EEE9F7", warnSoft: "F6EED8", warnInk: "8A6300", white: "FFFFFF"
};
function bg(s, c) { s.background = { color: c }; }
function title(s, t, color) {
  s.addText(t, { isTextBox: true, x: M, y: 0.5, w: W - M * 2, h: 0.95, fontFace: F, fontSize: 30, bold: true,
    color: color || C.ink, valign: "middle", margin: 0 });
}
function lead(s, t, y) {
  s.addText(t, { isTextBox: true, x: M, y: y || 1.45, w: W - M * 2, h: 0.55, fontFace: F, fontSize: 15, color: C.ink2, margin: 0 });
}
function numCircle(s, x, y, n, color) {
  s.addText(String(n), { isTextBox: true, x, y, w: 0.6, h: 0.6, fontFace: F, fontSize: 19, bold: true, color: C.white,
    align: "center", valign: "middle", shape: p.ShapeType.oval, fill: { color: color || C.teal }, line: { type: "none" }, margin: 0 });
}

/* 1 ── Title */
let s = p.addSlide(); bg(s, C.dark);
s.addText("第0部 オリエンテーション", { isTextBox: true, x: M, y: 2.2, w: W - M * 2, h: 0.5, fontFace: F, fontSize: 16, color: C.mint, charSpacing: 2, margin: 0 });
s.addText("レッスン 0-1", { isTextBox: true, x: M, y: 2.75, w: W - M * 2, h: 0.7, fontFace: F, fontSize: 20, color: "CFE0D8", margin: 0 });
s.addText("ようこそ / この教材の使い方", { isTextBox: true, x: M, y: 3.35, w: W - M * 2, h: 1.4, fontFace: F, fontSize: 44, bold: true, color: C.white, margin: 0 });
s.addNotes("このコースへようこそ。最初のレッスンでは、コードは書きません。でもここが一番大事です。何を学ぶかの前に、『詰まったときにどう動くか』を先に決めます。これを知っているかどうかで、続くか挫折するかが変わります。");

/* 2 ── You are not at fault */
s = p.addSlide(); bg(s, C.paper);
title(s, "あなたは、何も悪くない");
lead(s, "もし過去に挫折した経験があっても、それはほぼ確実に「教材の設計の問題」です。");
s.addText("初学者がつまずく瞬間は、驚くほど決まっています：",
  { isTextBox: true, x: M, y: 2.15, w: W - M * 2, h: 0.4, fontFace: F, fontSize: 14, color: C.ink, bold: true, margin: 0 });
s.addText(
  [{ text: "書いてある通りにやったのに、エラーが出る", options: { breakLine: true, bullet: true } },
   { text: "英語のエラーが出て、どこを直せばいいか分からない", options: { breakLine: true, bullet: true } },
   { text: "動いたけれど、「なぜ動いたか」が分からないまま進む", options: { breakLine: true, bullet: true } },
   { text: "質問できる相手がいない（何を聞けばいいかも分からない）", options: { bullet: true } }],
  { isTextBox: true, x: M, y: 2.65, w: W - M * 2, h: 2.6, fontFace: F, fontSize: 16, color: C.ink, paraSpaceAfter: 12, margin: 0 });
s.addText("この教材は、この4つを潰すことだけを考えて作りました。",
  { isTextBox: true, x: M, y: 5.4, w: W - M * 2, h: 0.5, fontFace: F, fontSize: 15, bold: true, color: C.teal, margin: 0 });
s.addNotes("もしあなたが過去にプログラミングで挫折したことがあっても、それはあなたのせいじゃありません。初学者がつまずく場所は、驚くほど決まっています。書いた通りなのにエラーが出る、英語のエラーが読めない、なぜ動いたか分からない、質問相手がいない。この教材は、この4つを潰すために作りました。");

/* 3 ── 3 promises */
s = p.addSlide(); bg(s, C.paper);
title(s, "この教材の3つの約束");
const promises = [
  ["手順は、必ず「分岐」まで書く", "「〇〇して」で終わらせず、うまくいかない時にどうするか、まで書きます。"],
  ["AIの使い方を、技術として教える", "聞き方・検証の仕方・任せてはいけない範囲まで、独立した項目として扱います。"],
  ["必ず「動くもの」が手元に残る", "各部の最後に総合演習。修了時、GitHubに5つの作品が並びます。"]
];
let ry = 1.75;
promises.forEach((r, i) => {
  numCircle(s, M, ry, i + 1);
  s.addText(
    [{ text: r[0], options: { bold: true, fontSize: 18, color: C.ink, breakLine: true } },
     { text: r[1], options: { fontSize: 13.5, color: C.ink2 } }],
    { isTextBox: true, x: M + 0.85, y: ry - 0.1, w: W - M - 0.85 - M, h: 1.4, fontFace: F, valign: "top", margin: 0 });
  ry += 1.55;
});
s.addNotes("この教材は3つを約束します。1つ、手順は分岐まで書く。2つ、AIの使い方を技術として教える。3つ、必ず動くものが残る。特に2つ目。いまや現役エンジニアのほとんどがAIを使っていますが、多くの教材はAIをおまけ扱いします。この教材は違います。");

/* 4 ── 4-step learning */
s = p.addSlide(); bg(s, C.paper);
title(s, "写経して終わりにしない ── 学習の4ステップ");
lead(s, "特に③を飛ばさないでください。ここで理解の穴が見つかります。");
const steps = [
  ["① 読む", "解説を読んで、やろうとしていることを掴む"],
  ["② 打つ", "コードを自分で打つ（コピペしない）"],
  ["③ 説明させる", "AIに「1行ずつ説明して」と投げる"],
  ["④ 変える", "演習で、自分の意図でコードを変える"]
];
const cw = (W - M * 2 - 0.35 * 3) / 4, cy = 2.35, ch = 2.5;
steps.forEach((st, i) => {
  const x = M + i * (cw + 0.35);
  s.addText(
    [{ text: st[0], options: { bold: true, fontSize: 18, color: C.teal, breakLine: true } },
     { text: "\n" + st[1], options: { fontSize: 13, color: C.ink2 } }],
    { isTextBox: true, x, y: cy, w: cw, h: ch, fontFace: F, align: "left", valign: "top",
      shape: p.ShapeType.roundRect, rectRadius: 0.09, fill: { color: C.soft }, line: { type: "none" }, margin: 12 });
});
s.addNotes("各レッスンは4ステップで進めます。読む、打つ、AIに説明させる、そして自分で変える。3番目を飛ばさないでください。AIに『このコードを1行ずつ説明して』と投げると、自分の理解の穴が一気に見えます。");

/* 5 ── why type by hand (callout) */
s = p.addSlide(); bg(s, C.paper);
title(s, "なぜ、コピペせずに手で打つのか");
s.addText(
  [{ text: "コピペは5秒。手打ちは2分。\n", options: { bold: true, fontSize: 24, color: C.ink, breakLine: true } },
   { text: "その差の1分55秒が、タイポの直し方とエラーの読み方を教えてくれます。", options: { fontSize: 16, color: C.ink2 } }],
  { isTextBox: true, x: M, y: 2.3, w: W - M * 2, h: 2.4, fontFace: F, align: "center", valign: "middle",
    shape: p.ShapeType.roundRect, rectRadius: 0.1, fill: { color: C.soft }, line: { type: "none" }, margin: 20 });
s.addText("この教材では、コードは手で打ってください。",
  { isTextBox: true, x: M, y: 5.1, w: W - M * 2, h: 0.6, fontFace: F, fontSize: 16, bold: true, color: C.teal, align: "center", margin: 0 });
s.addNotes("『手で打つ』と聞くと面倒に感じますよね。でも、コピペは5秒で終わりますが、手打ちは2分かかる。その差の約2分が、タイポの直し方とエラーの読み方を、あなたの手に覚えさせてくれます。だからこの教材では、コードは手で打ってください。");

/* 6 ── 15-minute rule */
s = p.addSlide(); bg(s, C.paper);
title(s, "詰まったときの「15分ルール」");
lead(s, "15分だけ自力で粘る。超えたら、この順で助けを借ります。");
const flow = [
  "そのレッスンの「つまずきポイント」を読み直す",
  "巻末の「エラー図鑑」でエラーを検索する",
  "AIに聞く（聞き方は 0-6 で学びます）",
  "エラーメッセージをそのまま検索する",
  "いったん飛ばして、翌日もう一度見る"
];
let fy = 2.15;
flow.forEach((t, i) => {
  numCircle(s, M, fy, i + 1, i === 4 ? C.ai : C.teal);
  s.addText(t, { isTextBox: true, x: M + 0.8, y: fy, w: W - M - 0.8 - M, h: 0.6, fontFace: F, fontSize: 15, color: C.ink, valign: "middle", margin: 0 });
  fy += 0.82;
});
s.addText("⑤は「逃げ」ではありません。一晩寝かせて解決するのは、プロでも日常的にあります。",
  { isTextBox: true, x: M, y: 6.45, w: W - M * 2, h: 0.5, fontFace: F, fontSize: 13, italic: true, color: C.ink2, margin: 0 });
s.addNotes("詰まったら、15分だけ自力で粘ってください。超えたらこの順で助けを借ります。つまずきポイント、エラー図鑑、AIに聞く、そのまま検索、最後は翌日に回す。5番目は逃げではありません。一晩寝かせると解決することは、プロでも日常的にあります。");

/* 7 ── 7割で進む */
s = p.addSlide(); bg(s, C.paper);
title(s, "完璧に理解してから、進もうとしない");
s.addText(
  [{ text: "理解度は7割でOK。\n", options: { bold: true, fontSize: 24, color: C.ink, breakLine: true } },
   { text: "知識は後の章で何度も再登場します。1回目で7割、2回目で9割、3回目で腹落ち。", options: { fontSize: 16, color: C.ink2 } }],
  { isTextBox: true, x: M, y: 2.3, w: W - M * 2, h: 2.3, fontFace: F, align: "center", valign: "middle",
    shape: p.ShapeType.roundRect, rectRadius: 0.1, fill: { color: C.aiSoft }, line: { type: "none" }, margin: 20 });
s.addText("「完全に理解してから次へ」は、最も多い挫折パターンです。",
  { isTextBox: true, x: M, y: 5.0, w: W - M * 2, h: 0.6, fontFace: F, fontSize: 16, bold: true, color: C.ai, align: "center", margin: 0 });
s.addNotes("最後の約束です。完璧に理解してから進もうとしないでください。理解度7割で先へ。プログラミングの知識は後の章で何度も再登場します。1回目で7割、2回目で9割、3回目で腹落ち、が普通のペース。『完全に理解してから次へ』は、最も多い挫折パターンです。");

/* 8 ── symbols */
s = p.addSlide(); bg(s, C.paper);
title(s, "この教材で使う記号");
const sym = [
  ["◎", "このレッスンのゴール"], ["📸", "完成イメージ"], ["✍️", "手を動かすパート"],
  ["⚠️", "つまずきポイント（必読）"], ["🤖", "AIに聞いてみよう"], ["🔧", "演習"],
  ["✅", "章末チェック"], ["🆘", "ここで詰まったら"], ["💡", "補足・豆知識"]
];
const scw = (W - M * 2 - 0.3 * 2) / 3, sch = 1.15;
sym.forEach((it, i) => {
  const col = i % 3, row = Math.floor(i / 3);
  const x = M + col * (scw + 0.3), y = 1.85 + row * (sch + 0.25);
  s.addText(
    [{ text: it[0] + "  ", options: { fontSize: 20, color: C.teal } },
     { text: it[1], options: { fontSize: 14, color: C.ink } }],
    { isTextBox: true, x, y, w: scw, h: sch, fontFace: F, align: "left", valign: "middle",
      shape: p.ShapeType.roundRect, rectRadius: 0.1, fill: { color: C.soft }, line: { type: "none" }, margin: 10 });
});
s.addNotes("教材の中では、こうした記号で節を示します。ゴール、完成イメージ、手を動かす、つまずきポイント、AIに聞いてみよう、演習、章末チェック、そして詰まったとき用の記号。特に、つまずきポイントは必読です。");

/* 9 ── stuck prompt */
s = p.addSlide(); bg(s, C.paper);
title(s, "詰まったら、AIにこう聞く");
lead(s, "各レッスンの手が止まりやすい箇所に「🆘 ここで詰まったら」を置いています。");
s.addText(
  "プログラミング初心者です。○○をしようとして、△△というエラー（または状態）になりました。原因の候補と、私が自分で確認する手順を、やさしく教えてください。いきなり答えのコードは出さないでください。",
  { isTextBox: true, x: M, y: 2.25, w: W - M * 2, h: 2.2, fontFace: F, fontSize: 16, color: C.ink, valign: "middle",
    shape: p.ShapeType.roundRect, rectRadius: 0.08, fill: { color: C.soft }, line: { type: "none" }, margin: 18 });
s.addText("ポイントは最後の一文。「答えのコードは出さないで」と付けると、AIが“代筆屋”ではなく“先生”になります。",
  { isTextBox: true, x: M, y: 4.7, w: W - M * 2, h: 0.9, fontFace: F, fontSize: 14, color: C.ai, bold: true, margin: 0 });
s.addNotes("詰まったときのAIへの聞き方も、最初のテンプレを渡しておきます。これをコピペして使ってください。ポイントは最後の一文。『いきなり答えのコードは出さないで』と付けること。こう指示すると、AIが代筆屋ではなく、先生になります。");

/* 10 ── checklist + next */
s = p.addSlide(); bg(s, C.dark);
title(s, "章末チェック", C.white);
s.addText(
  [{ text: "コードはコピペせず、手で打つと決めた", options: { breakLine: true, bullet: true } },
   { text: "詰まったときの「15分ルール」の流れを説明できる", options: { breakLine: true, bullet: true } },
   { text: "理解度7割で次へ進んでよい、と納得した", options: { bullet: true } }],
  { isTextBox: true, x: M, y: 1.9, w: W - M * 2, h: 2.4, fontFace: F, fontSize: 17, color: "E8EEEA", paraSpaceAfter: 14, margin: 0 });
s.addText(
  [{ text: "次のレッスン → ", options: { color: C.mint } },
   { text: "0-2 挫折しない学び方の設計", options: { color: C.white, bold: true } }],
  { isTextBox: true, x: M, y: 5.4, w: W - M * 2, h: 0.6, fontFace: F, fontSize: 18, margin: 0 });
s.addNotes("今日決めたのは3つ。コードは手で打つ、15分ルール、7割で進む。この3つを胸に、次のレッスンへ進みましょう。次回は、挫折しない学び方の“設計”です。おつかれさまでした。");

p.writeFile({ fileName: "レッスン0-1_ようこそ.pptx" }).then(f => console.log("wrote", f));
