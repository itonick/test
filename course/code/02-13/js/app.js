/* ==========================================================
   ToDo アプリ — 第2部 完成版
   使用技術: DOM操作 / イベント委譲 / 配列メソッド / localStorage
   設計方針: 「状態を変える → 画面を描き直す」の一方通行
   ========================================================== */


/* ==========================================================
   1. ユーティリティ
   ========================================================== */

/** HTMLエスケープ（XSS対策）。ユーザー入力をHTMLに埋める前に必ず通す */
const escapeHtml = (str) =>
  String(str)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");

/** localStorage の安全なラッパー。失敗しても例外を投げない */
const storage = {
  set(key, value) {
    try {
      localStorage.setItem(key, JSON.stringify(value));
      return true;
    } catch (error) {
      console.error("保存に失敗しました:", error);
      return false;
    }
  },
  get(key, fallback = null) {
    try {
      const raw = localStorage.getItem(key);
      if (raw === null) return fallback;
      return JSON.parse(raw) ?? fallback;
    } catch (error) {
      console.error("読み込みに失敗しました:", error);
      return fallback;
    }
  },
  isAvailable() {
    try {
      const k = "__test__";
      localStorage.setItem(k, "1");
      localStorage.removeItem(k);
      return true;
    } catch {
      return false;
    }
  },
};

/** 一意なIDを作る（古いブラウザ向けのフォールバック付き） */
const createId = () =>
  crypto.randomUUID?.() ?? `${Date.now()}-${Math.random().toString(36).slice(2)}`;

/** タイムスタンプを "4/13 10:05" の形式にする */
const formatDate = (ts) => {
  const d = new Date(ts);
  const pad = (n) => String(n).padStart(2, "0");
  return `${d.getMonth() + 1}/${d.getDate()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
};


/* ==========================================================
   2. 状態（この3つが唯一の真実）
   ========================================================== */

const STORAGE_KEY = "todos-v1";
const THEME_KEY = "todo-theme";

/** @type {{id:string, text:string, done:boolean, createdAt:number}[]} */
let todos = storage.get(STORAGE_KEY, []);

/** "all" | "active" | "done" */
let filter = "all";

/** 編集中のタスクID（null なら編集していない） */
let editingId = null;


/* ==========================================================
   3. DOM要素
   ========================================================== */

const el = {
  form:       document.querySelector("#addForm"),
  input:      document.querySelector("#newTodo"),
  formError:  document.querySelector("#formError"),
  list:       document.querySelector("#todoList"),
  empty:      document.querySelector("#emptyState"),
  counter:    document.querySelector("#counter"),
  filters:    document.querySelector(".filters"),
  clearDone:  document.querySelector("#clearDone"),
  themeToggle:document.querySelector("#themeToggle"),
  warning:    document.querySelector("#storageWarning"),
};


/* ==========================================================
   4. データ操作
   すべて「新しい配列を作って代入」する（元を破壊しない）
   ========================================================== */

const save = () => storage.set(STORAGE_KEY, todos);

const addTodo = (text) => {
  todos = [
    ...todos,
    { id: createId(), text, done: false, createdAt: Date.now() },
  ];
  save();
};

const toggleTodo = (id) => {
  todos = todos.map((t) => (t.id === id ? { ...t, done: !t.done } : t));
  save();
};

const updateTodo = (id, text) => {
  todos = todos.map((t) => (t.id === id ? { ...t, text } : t));
  save();
};

const deleteTodo = (id) => {
  todos = todos.filter((t) => t.id !== id);
  save();
};

const clearCompleted = () => {
  todos = todos.filter((t) => !t.done);
  save();
};

/** 絞り込み後のタスクを返す */
const getVisibleTodos = () => {
  if (filter === "active") return todos.filter((t) => !t.done);
  if (filter === "done")   return todos.filter((t) => t.done);
  return todos;
};


/* ==========================================================
   5. 描画
   ========================================================== */

const EMPTY_MESSAGE = {
  all:    "まだタスクがありません。上の入力欄から追加してください。",
  active: "未完了のタスクはありません。おつかれさまでした。",
  done:   "完了したタスクはまだありません。",
};

/** 1件分のHTMLを作る */
const todoHtml = (todo) => {
  const isEditing = todo.id === editingId;
  const safeText = escapeHtml(todo.text);

  const body = isEditing
    ? `<input type="text" class="todo-edit" value="${safeText}"
              maxlength="120" aria-label="タスクを編集">`
    : `<span class="todo-text" tabindex="0" role="button"
             aria-label="ダブルクリックで編集: ${safeText}">${safeText}</span>`;

  return `
    <li class="todo ${todo.done ? "is-done" : ""}" data-id="${todo.id}">
      <input type="checkbox" class="todo-check" ${todo.done ? "checked" : ""}
             aria-label="${safeText} を完了にする">
      ${body}
      <span class="todo-date">${formatDate(todo.createdAt)}</span>
      <button type="button" class="todo-delete"
              aria-label="${safeText} を削除">×</button>
    </li>
  `;
};

/** 画面全体を描き直す */
const render = () => {
  const visible = getVisibleTodos();

  // リスト本体
  el.list.innerHTML = visible.map(todoHtml).join("");

  // 空の状態
  const isEmpty = visible.length === 0;
  el.empty.hidden = !isEmpty;
  el.empty.textContent = EMPTY_MESSAGE[filter];

  // 絞り込みボタンの状態
  el.filters.querySelectorAll(".chip").forEach((chip) => {
    const active = chip.dataset.filter === filter;
    chip.classList.toggle("is-active", active);
    chip.setAttribute("aria-pressed", String(active));
  });

  // 集計
  const total = todos.length;
  const doneCount = todos.filter((t) => t.done).length;
  el.counter.textContent =
    total === 0 ? "" : `全 ${total} 件 ／ 未完了 ${total - doneCount} 件 ／ 完了 ${doneCount} 件`;

  // 「完了を削除」の活性
  el.clearDone.disabled = doneCount === 0;

  // 編集中なら input にフォーカスを移す
  if (editingId) {
    const input = el.list.querySelector(".todo-edit");
    if (input) {
      input.focus();
      input.setSelectionRange(input.value.length, input.value.length);
    }
  }
};


/* ==========================================================
   6. 入力の検証
   ========================================================== */

const validateText = (text) => {
  const v = text.trim();
  if (v === "") return "タスクの内容を入力してください";
  if (v.length > 120) return `120文字以内で入力してください（現在${v.length}文字）`;
  if (todos.some((t) => t.text === v && !t.done)) {
    return "同じ内容の未完了タスクが既にあります";
  }
  return null;
};

const showFormError = (message) => {
  el.formError.textContent = message ?? "";
};


/* ==========================================================
   7. イベント
   ========================================================== */

/* --- 追加 --- */
el.form.addEventListener("submit", (e) => {
  e.preventDefault();

  const text = el.input.value;
  const error = validateText(text);

  if (error) {
    showFormError(error);
    el.input.focus();
    return;
  }

  addTodo(text.trim());
  el.input.value = "";
  showFormError(null);
  render();
  el.input.focus();
});

/* 入力し直したらエラーを消す */
el.input.addEventListener("input", () => showFormError(null));

/* --- リスト内の操作（イベント委譲） --- */

// チェックボックス
el.list.addEventListener("change", (e) => {
  const check = e.target.closest(".todo-check");
  if (!check) return;
  const id = check.closest(".todo").dataset.id;
  toggleTodo(id);
  render();
});

// 削除 / 編集開始（クリック）
el.list.addEventListener("click", (e) => {
  const li = e.target.closest(".todo");
  if (!li) return;
  const id = li.dataset.id;

  if (e.target.closest(".todo-delete")) {
    deleteTodo(id);
    if (editingId === id) editingId = null;
    render();
  }
});

// ダブルクリックで編集開始
el.list.addEventListener("dblclick", (e) => {
  const text = e.target.closest(".todo-text");
  if (!text) return;
  editingId = text.closest(".todo").dataset.id;
  render();
});

// キーボードで編集開始（Enter / Space）
el.list.addEventListener("keydown", (e) => {
  const text = e.target.closest(".todo-text");
  if (text && (e.key === "Enter" || e.key === " ")) {
    e.preventDefault();
    editingId = text.closest(".todo").dataset.id;
    render();
    return;
  }

  const input = e.target.closest(".todo-edit");
  if (!input) return;

  if (e.key === "Enter") {
    e.preventDefault();
    commitEdit(input);
  } else if (e.key === "Escape") {
    e.preventDefault();
    editingId = null;
    render();
  }
});

// フォーカスが外れたら確定（focusout はバブリングする）
el.list.addEventListener("focusout", (e) => {
  const input = e.target.closest(".todo-edit");
  if (!input) return;
  commitEdit(input);
});

/** 編集を確定する。空ならキャンセル扱い */
const commitEdit = (input) => {
  if (!editingId) return;
  const id = editingId;
  const text = input.value.trim();

  editingId = null;

  if (text !== "") {
    updateTodo(id, text);
  }
  render();
};

/* --- 絞り込み --- */
el.filters.addEventListener("click", (e) => {
  const chip = e.target.closest(".chip");
  if (!chip) return;
  filter = chip.dataset.filter;
  editingId = null;
  render();
});

/* --- 完了を一括削除 --- */
el.clearDone.addEventListener("click", () => {
  const doneCount = todos.filter((t) => t.done).length;
  if (doneCount === 0) return;
  if (!confirm(`完了した ${doneCount} 件を削除します。よろしいですか？`)) return;

  clearCompleted();
  editingId = null;
  render();
});

/* --- 他のタブでの変更を同期 --- */
window.addEventListener("storage", (e) => {
  if (e.key !== STORAGE_KEY) return;
  todos = storage.get(STORAGE_KEY, []);
  editingId = null;
  render();
});


/* ==========================================================
   8. テーマ切り替え
   ========================================================== */

const applyTheme = (theme) => {
  document.documentElement.setAttribute("data-theme", theme);
  const isDark = theme === "dark";
  el.themeToggle.setAttribute("aria-pressed", String(isDark));
  el.themeToggle.querySelector("span[aria-hidden]").textContent = isDark ? "☀" : "☾";
  el.themeToggle.querySelector(".visually-hidden").textContent =
    isDark ? "ライトモードに切り替え" : "ダークモードに切り替え";
};

const getInitialTheme = () => {
  const saved = storage.get(THEME_KEY);
  if (saved === "dark" || saved === "light") return saved;
  return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
};

el.themeToggle.addEventListener("click", () => {
  const next =
    document.documentElement.getAttribute("data-theme") === "dark" ? "light" : "dark";
  applyTheme(next);
  storage.set(THEME_KEY, next);
});


/* ==========================================================
   9. 初期化
   ========================================================== */

applyTheme(getInitialTheme());

// 保存できない環境では警告を出す（クラッシュはさせない）
el.warning.hidden = storage.isAvailable();

render();
el.input.focus();
