<?php
session_start();
if (!isset($_SESSION['user'])) {
  header('Location: login.html');
  exit;
}
?>
<!doctype html>
<html lang="ja">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Works CMS</title>
  <style>
    :root {
      --bg: #FFFDFB;
      --card: #ffffff;
      --text: #0f172a;
      --muted: #64748b;
      --line: rgba(15, 23, 42, .10);
      --shadow: 0 18px 40px rgba(15, 23, 42, .10);
      --shadow2: 0 10px 24px rgba(15, 23, 42, .12);
      --accent: #1e40af;
      --accent2: #0ea5e9;
      --danger: #dc2626;
      --ok: #16a34a;
      --chip: #f1f5f9;
      --chipText: #334155;
      --radius: 18px;
    }

    html,
    body {
      height: 100%;
    }

    body {
      margin: 0;
      background: var(--bg);
      color: var(--text);
      font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Hiragino Sans", "Noto Sans JP", "Helvetica Neue", Arial;
      letter-spacing: .2px;
    }

    .page {
      max-width: 1180px;
      margin: 0 auto;
      padding: 28px 16px 60px;
    }

    .topbar {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 16px;
    }

    .title {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .title h1 {
      margin: 0;
      font-size: 22px;
      font-weight: 800;
      letter-spacing: .6px;
    }

    .title p {
      margin: 0;
      color: var(--muted);
      font-size: 13px;
      line-height: 1.5;
    }

    .actions {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      justify-content: flex-end;
    }

    .btn {
      border: 1px solid var(--line);
      background: var(--card);
      color: var(--text);
      border-radius: 999px;
      padding: 10px 14px;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
      transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
      user-select: none;
    }

    .btn:hover {
      transform: translateY(-1px);
      box-shadow: var(--shadow2);
      border-color: rgba(30, 64, 175, .25);
    }

    .btn:active {
      transform: translateY(0);
    }

    .btn.primary {
      background: linear-gradient(135deg, rgba(30, 64, 175, .95), rgba(14, 165, 233, .92));
      color: white;
      border-color: transparent;
    }

    .btn.danger {
      background: rgba(220, 38, 38, .08);
      border-color: rgba(220, 38, 38, .25);
      color: var(--danger);
      box-shadow: none;
    }

    .btn.ghost {
      background: transparent;
      box-shadow: none;
    }

    .card {
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      overflow: hidden;
    }

    .toolbar {
      padding: 14px 14px;
      display: flex;
      align-items: center;
      gap: 10px;
      border-bottom: 1px solid var(--line);
      flex-wrap: wrap;
    }

    .search {
      display: flex;
      align-items: center;
      gap: 10px;
      border: 1px solid var(--line);
      background: rgba(255, 255, 255, .9);
      border-radius: 999px;
      padding: 10px 12px;
      min-width: 260px;
      flex: 1 1 320px;
    }

    .search svg {
      flex: 0 0 auto;
      opacity: .7;
    }

    .search input {
      border: 0;
      outline: none;
      background: transparent;
      width: 100%;
      font-size: 14px;
      color: var(--text);
    }

    .search input::placeholder {
      color: rgba(100, 116, 139, .85);
    }

    .chips {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      align-items: center;
      flex: 2 1 420px;
    }

    .chip {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 7px 10px;
      border-radius: 999px;
      border: 1px solid var(--line);
      background: var(--chip);
      color: var(--chipText);
      font-weight: 800;
      font-size: 12px;
      cursor: pointer;
      user-select: none;
      transition: transform .12s ease, border-color .12s ease, background .12s ease;
    }

    .chip:hover {
      transform: translateY(-1px);
    }

    .chip.active {
      border-color: rgba(30, 64, 175, .35);
      background: rgba(30, 64, 175, .10);
      color: rgba(30, 64, 175, .95);
    }

    .chip .count {
      font-weight: 900;
      color: rgba(100, 116, 139, .9);
    }

    .chip.active .count {
      color: rgba(30, 64, 175, .85);
    }

    .meta {
      margin-left: auto;
      display: flex;
      gap: 10px;
      align-items: center;
      color: var(--muted);
      font-size: 12px;
      font-weight: 700;
      white-space: nowrap;
    }

    .tableWrap {
      overflow: auto;
      -webkit-overflow-scrolling: touch;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 980px;
    }

    thead th {
      text-align: left;
      font-size: 12px;
      color: rgba(100, 116, 139, .95);
      font-weight: 900;
      padding: 12px 14px;
      border-bottom: 1px solid var(--line);
      background: rgba(255, 255, 255, .98);
      position: sticky;
      top: 0;
      z-index: 1;
    }

    tbody td {
      padding: 12px 14px;
      border-bottom: 1px solid var(--line);
      vertical-align: top;
      font-size: 13px;
      line-height: 1.5;
    }

    tbody tr:hover {
      background: rgba(15, 23, 42, .02);
    }

    .cellTitle {
      font-weight: 900;
      font-size: 13px;
    }

    .cellDesc {
      color: rgba(100, 116, 139, .95);
      font-size: 12px;
      margin-top: 4px;
      max-width: 420px;
    }

    .thumb {
      width: 64px;
      height: 44px;
      border-radius: 10px;
      background: rgba(15, 23, 42, .06);
      border: 1px solid var(--line);
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      flex: 0 0 auto;
    }

    .thumb img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .thumb .noimg {
      font-size: 10px;
      color: rgba(100, 116, 139, .85);
      font-weight: 800;
    }

    .tagList {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      max-width: 240px;
    }

    .tag {
      font-size: 11px;
      font-weight: 900;
      padding: 5px 8px;
      border-radius: 999px;
      border: 1px solid var(--line);
      background: rgba(241, 245, 249, .9);
      color: rgba(51, 65, 85, .95);
      white-space: nowrap;
    }

    .rowBtns {
      display: flex;
      gap: 8px;
      align-items: center;
      white-space: nowrap;
    }

    .rowBtns .btn {
      padding: 8px 10px;
      font-size: 12px;
      box-shadow: none;
    }

    /* Modal */
    .overlay {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, .38);
      display: none;
      align-items: center;
      justify-content: center;
      padding: 18px;
      z-index: 9999;
    }

    .overlay.open {
      display: flex;
    }

    .modal {
      width: min(980px, 100%);
      max-height: min(86vh, 900px);
      overflow: auto;
      background: var(--card);
      border-radius: 22px;
      border: 1px solid rgba(255, 255, 255, .25);
      box-shadow: var(--shadow);
      position: relative;
    }

    .modalHeader {
      padding: 16px 18px;
      border-bottom: 1px solid var(--line);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      position: sticky;
      top: 0;
      background: rgba(255, 255, 255, .98);
      z-index: 2;
      backdrop-filter: blur(10px);
    }

    .modalHeader .h {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .modalHeader .h .t {
      font-weight: 900;
      font-size: 15px;
    }

    .modalHeader .h .s {
      color: var(--muted);
      font-size: 12px;
      font-weight: 700;
    }

    .modalBody {
      padding: 16px 18px 20px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }

    .field {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .field.full {
      grid-column: 1 / -1;
    }

    label {
      font-size: 12px;
      font-weight: 900;
      color: rgba(51, 65, 85, .95);
    }

    input[type="text"],
    textarea {
      border: 1px solid var(--line);
      border-radius: 14px;
      padding: 12px 12px;
      font-size: 14px;
      outline: none;
      background: rgba(255, 255, 255, .98);
    }

    textarea {
      min-height: 180px;
      resize: vertical;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
      font-size: 12.5px;
      line-height: 1.7;
    }

    .help {
      color: var(--muted);
      font-size: 12px;
      line-height: 1.5;
    }

    .modalFooter {
      padding: 14px 18px;
      border-top: 1px solid var(--line);
      display: flex;
      gap: 10px;
      align-items: center;
      justify-content: flex-end;
      position: sticky;
      bottom: 0;
      background: rgba(255, 255, 255, .98);
      z-index: 2;
      backdrop-filter: blur(10px);
    }

    .toast {
      position: fixed;
      left: 16px;
      bottom: 16px;
      background: rgba(15, 23, 42, .92);
      color: white;
      padding: 10px 12px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 800;
      box-shadow: var(--shadow2);
      opacity: 0;
      transform: translateY(8px);
      transition: opacity .18s ease, transform .18s ease;
      z-index: 10000;
      pointer-events: none;
    }

    .toast.show {
      opacity: 1;
      transform: translateY(0);
    }

    @media (max-width: 780px) {
      .modalBody {
        grid-template-columns: 1fr;
      }

      .search {
        min-width: 0;
      }
    }

    /* Image uploader */
    .uploader {
      border: 1px dashed rgba(15, 23, 42, .18);
      background: rgba(241, 245, 249, .55);
      border-radius: 16px;
      padding: 14px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }

    .uploader .left {
      display: flex;
      flex-direction: column;
      gap: 4px;
      min-width: 0;
    }

    .uploader .left .cap {
      font-size: 12px;
      font-weight: 900;
      color: rgba(51, 65, 85, .95);
    }

    .uploader .left .sub {
      font-size: 12px;
      color: var(--muted);
      line-height: 1.5;
      word-break: break-word;
    }

    .uploader .right {
      display: flex;
      align-items: center;
      gap: 10px;
      flex: 0 0 auto;
    }

    .uploader .fileName {
      font-size: 12px;
      color: rgba(100, 116, 139, .95);
      font-weight: 800;
      max-width: 240px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .previewWrap {
      margin-top: 10px;
      border: 1px solid var(--line);
      border-radius: 14px;
      overflow: hidden;
      background: rgba(255, 255, 255, .95);
      display: none;
      /* ← 選択時のみ表示 */
    }

    .previewWrap img {
      display: block;
      width: 100%;
      max-height: 260px;
      object-fit: cover;
    }
  </style>
</head>

<body>
  <div class="page">
    <div class="topbar">
      <div class="title">
        <h1>Works CMS</h1>
        <p>
          <b>assets/data/works.json</b> を読み込み、一覧表示・編集します。<br />
          ブラウザから直接ファイルは上書きできないため、<b>Export</b> でJSONをダウンロードし、手動で差し替えてください。
        </p>
      </div>
      <div class="actions">
        <button class="btn" id="btnReload">Reload</button>
        <button class="btn" id="btnImport">Import</button>
        <button class="btn primary" id="btnAdd">+ Add</button>
        <button class="btn primary" id="btnExport">Export JSON</button>
      </div>
    </div>

    <div class="card">
      <div class="toolbar">
        <div class="search">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="2" />
            <path d="M16 16l5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
          </svg>
          <input id="q" type="text" placeholder="検索（id / title / desc / tags）" />
        </div>

        <div class="chips" id="tagChips"></div>

        <div class="meta">
          <span id="countMeta">0 items</span>
        </div>
      </div>

      <div class="tableWrap">
        <table>
          <thead>
            <tr>
              <th style="width:90px;">Image</th>
              <th>Title</th>
              <th style="width:140px;">ID</th>
              <th style="width:260px;">Tags</th>
              <th style="width:220px;">Links</th>
              <th style="width:140px;">Actions</th>
            </tr>
          </thead>
          <tbody id="tbody"></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <div class="overlay" id="overlay" role="dialog" aria-modal="true" aria-label="edit work">
    <div class="modal" id="modal">
      <div class="modalHeader">
        <div class="h">
          <div class="t" id="modalTitle">Edit</div>
          <div class="s" id="modalSub">id: -</div>
        </div>
        <button class="btn ghost" id="btnClose">Close</button>
      </div>

      <div class="modalBody">
        <div class="field">
          <label for="f_id">id</label>
          <input id="f_id" type="text" placeholder="例: dome" />
          <div class="help">URLや選択に使うキー。既存と重複しないように。</div>
        </div>

        <div class="field">
          <label for="f_title">title</label>
          <input id="f_title" type="text" placeholder="例: DOME Map" />
        </div>

        <div class="field full">
          <label for="f_desc">desc</label>
          <input id="f_desc" type="text" placeholder="一覧カードに表示する短い説明" />
        </div>

        <div class="field full">
          <label>image（upload）</label>
          <input id="f_image_file" type="file" accept="image/*" style="display:none" />

          <div class="uploader">
            <div class="left">
              <div class="cap">assets/image/work/<span id="imgIdSlot">{id}</span>.jpg に保存</div>
              <div class="sub">ここで選んだ画像がある場合のみアップロードし、プレビューします（未選択なら何もしません）。</div>
            </div>
            <div class="right">
              <div class="fileName" id="imgFileName">no file</div>
              <button class="btn" type="button" id="btnPickImage">Upload</button>
              <button class="btn ghost" type="button" id="btnClearImage">Clear</button>
            </div>
          </div>

          <div class="previewWrap" id="imgPreviewWrap">
            <img id="imgPreview" alt="" />
          </div>
        </div>

        <div class="field">
          <label for="f_href">href</label>
          <input id="f_href" type="text" placeholder="https://..." />
        </div>

        <div class="field full">
          <label for="f_tags">tags（カンマ区切り）</label>
          <input id="f_tags" type="text" placeholder="Flutter, Firebase, WebRTC" />
        </div>

        <div class="field full">
          <label for="f_note">note（Markdown）</label>
          <textarea id="f_note" placeholder="# 見出し\n\n本文...\n\n```dart\n...\n```"></textarea>
          <div class="help">この CMS は note を「テキスト」として保存します。表示側の Markdown パーサでレンダリングしてください。</div>
        </div>
      </div>

      <div class="modalFooter">
        <button class="btn danger" id="btnDelete">Delete</button>
        <div style="flex:1"></div>
        <button class="btn" id="btnCopy">Copy JSON</button>
        <button class="btn primary" id="btnSave">Save</button>
      </div>
    </div>
  </div>

  <input id="fileInput" type="file" accept="application/json" style="display:none" />
  <div class="toast" id="toast">Saved</div>

  <script>
    const WORKS_URL = "assets/data/works.json";
    // token
    const CMS_TOKEN = "cms_token_change_me_2025";

    const SAVE_URL = "assets/api/save-works.php";
    const UPLOAD_URL = "assets/api/upload-work-image.php";

    /** @type {{works: any[]}} */
    let raw = { works: [] };
    /** @type {any[]} */
    let works = [];
    let activeTag = "All";
    let editingIndex = -1;

    const el = (id) => document.getElementById(id);

    const $tbody = el("tbody");
    const $q = el("q");
    const $tagChips = el("tagChips");
    const $countMeta = el("countMeta");

    const $overlay = el("overlay");
    const $modalTitle = el("modalTitle");
    const $modalSub = el("modalSub");

    const $f_id = el("f_id");
    const $f_title = el("f_title");
    const $f_desc = el("f_desc");
    const $f_note = el("f_note");
    const $f_href = el("f_href");
    const $f_tags = el("f_tags");

    const $f_image_file = el("f_image_file");
    const $btnPickImage = el("btnPickImage");
    const $btnClearImage = el("btnClearImage");
    const $imgPreviewWrap = el("imgPreviewWrap");
    const $imgPreview = el("imgPreview");
    const $imgFileName = el("imgFileName");
    const $imgIdSlot = el("imgIdSlot");

    /** @type {File|null} */
    let pendingImageFile = null;
    /** @type {string|null} */
    let pendingImageUrl = null;

    const $toast = el("toast");
    const $fileInput = el("fileInput");

    function toast(msg) {
      $toast.textContent = msg;
      $toast.classList.add("show");
      window.setTimeout(() => $toast.classList.remove("show"), 1200);
    }

    function escapeHtml(s) {
      return String(s ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
    }

    function uniq(arr) {
      return Array.from(new Set(arr));
    }

    function normalizeTags(tags) {
      if (!tags) return [];
      if (Array.isArray(tags)) return tags.map(t => String(t).trim()).filter(Boolean);
      return String(tags)
        .split(",")
        .map(s => s.trim())
        .filter(Boolean);
    }

    // --- Soft-delete (comment-out) support ---

    function stripJsonComments(jsonStr) {
      // 1. Remove /* ... */ comments
      let clean = jsonStr.replace(/\/\*[\s\S]*?\*\//g, "");
      // 2. Remove trailing commas in arrays/objects: , ] -> ] and , } -> }
      clean = clean.replace(/,(\s*[\]}])/g, "$1");
      return clean;
    }

    function extractCommentedItems(jsonStr) {
      const items = [];
      // Match /* ... */ blocks
      const regex = /\/\*([\s\S]*?)\*\//g;
      let match;
      while ((match = regex.exec(jsonStr)) !== null) {
        const content = match[1].trim();
        // Heuristic: if it looks like a JSON object starting/ending with brackets
        if (content.startsWith("{") && content.endsWith("}")) {
          try {
            // Attempt to parse the inner content
            const obj = JSON.parse(content);
            if (obj && typeof obj === 'object') {
              obj._commentOut = true;
              items.push(obj);
            }
          } catch (e) {
            // Ignore parse errors (maybe it's just a regular comment)
          }
        }
      }
      return items;
    }

    function serializeWorksWithComments(worksArray) {
      // Custom serializer to wrap items with _commentOut: true in /* ... */
      const parts = worksArray.map((w, i) => {
        // Clone and remove internal temporary flags if we want to clean up, 
        // but here we just check _commentOut.
        const { _commentOut, ...rest } = w;

        const json = JSON.stringify(rest, null, 4); // indentation 4 for better readability
        if (_commentOut) {
          return `    /* ${JSON.stringify(rest)} */`;
        }
        return `    ${json}`; // Indent standard items
      });

      return `{\n  "works": [\n${parts.join(",\n")}\n  ]\n}`;
    }

    function imagePathForId(id) {
      const safe = String(id ?? "").trim();
      return safe ? `assets/image/work/${safe}.jpg` : "";
    }

    function setImageIdSlot() {
      const id = String($f_id.value ?? "").trim() || "{id}";
      $imgIdSlot.textContent = id;
    }

    function clearPendingImage() {
      pendingImageFile = null;
      if (pendingImageUrl) {
        try { URL.revokeObjectURL(pendingImageUrl); } catch (_) { }
      }
      pendingImageUrl = null;
      $imgPreview.src = "";
      $imgPreviewWrap.style.display = "none";
      $imgFileName.textContent = "no file";
      if ($f_image_file) $f_image_file.value = "";
    }

    function setPendingImage(file) {
      clearPendingImage();
      if (!file) return;
      pendingImageFile = file;
      $imgFileName.textContent = file.name;
      pendingImageUrl = URL.createObjectURL(file);
      $imgPreview.src = pendingImageUrl;
      $imgPreviewWrap.style.display = "block";
    }

    function setExistingImagePreviewById(id) {
      const safe = String(id ?? "").trim();
      if (!safe) {
        // no id -> no preview
        $imgPreview.src = "";
        $imgPreviewWrap.style.display = "none";
        $imgFileName.textContent = "no file";
        return;
      }

      const url = `${imagePathForId(safe)}?v=${Date.now()}`;

      // If the file does not exist, hide preview on error.
      $imgPreview.onload = () => {
        // Only show if the user is not previewing a newly selected file
        if (!pendingImageFile) {
          $imgPreviewWrap.style.display = "block";
          $imgFileName.textContent = `current: ${safe}.jpg`;
        }
      };
      $imgPreview.onerror = () => {
        if (!pendingImageFile) {
          $imgPreview.src = "";
          $imgPreviewWrap.style.display = "none";
          $imgFileName.textContent = "no file";
        }
      };

      $imgPreview.src = url;
    }

    async function uploadImageForId(id, file) {
      const form = new FormData();
      form.append("id", id);
      form.append("file", file);

      const res = await fetch(UPLOAD_URL, {
        method: "POST",
        headers: { "X-CMS-Token": CMS_TOKEN },
        body: form,
      });

      let data = null;
      try { data = await res.json(); } catch (_) { }

      if (!res.ok) {
        if (res.status === 404) {
          throw new Error("アップロードAPIが見つかりません（assets/api/upload-work-image.php が 404）。PHPが動く環境で配置できているか確認してください。");
        }
        const msg = (data && (data.error || data.message))
          ? (data.error || data.message)
          : `HTTP ${res.status}`;
        throw new Error(`画像アップロードに失敗しました: ${msg}`);
      }
      if (data && data.ok === false) {
        throw new Error(data.error || "Upload failed");
      }
      return data;
    }

    function computeTagStats(items) {
      const map = new Map();
      for (const w of items) {
        const tags = normalizeTags(w.tags);
        for (const t of tags) {
          map.set(t, (map.get(t) ?? 0) + 1);
        }
      }
      return map;
    }

    function buildChips(items) {
      const stats = computeTagStats(items);
      const tags = ["All", ...Array.from(stats.keys()).sort((a, b) => a.localeCompare(b, "ja"))];

      $tagChips.innerHTML = "";
      for (const t of tags) {
        const count = t === "All" ? items.length : (stats.get(t) ?? 0);
        const chip = document.createElement("div");
        chip.className = "chip" + (t === activeTag ? " active" : "");
        chip.innerHTML = `<span>${escapeHtml(t)}</span><span class="count">(${count})</span>`;
        chip.addEventListener("click", () => {
          activeTag = t;
          buildChips(items);
          render();
        });
        $tagChips.appendChild(chip);
      }
    }

    function matches(w, q) {
      if (!q) return true;
      const s = q.toLowerCase();
      const hay = [
        w.id, w.title, w.desc,
        normalizeTags(w.tags).join(" "),
      ].join(" ").toLowerCase();
      return hay.includes(s);
    }

    function filterItems() {
      const q = ($q.value ?? "").trim();
      return works.filter(w => {
        if (w._commentOut) return false;
        if (!matches(w, q)) return false;
        if (activeTag === "All") return true;
        return normalizeTags(w.tags).includes(activeTag);
      });
    }

    function render() {
      const items = filterItems();
      $countMeta.textContent = `${items.length} / ${works.length} items`;

      $tbody.innerHTML = "";

      for (const w of items) {
        const idx = works.findIndex(x => x.id === w.id);

        const tr = document.createElement("tr");

        const imgTd = document.createElement("td");
        imgTd.innerHTML = `
          <div class="thumb">
            ${w.image ? `<img src="${escapeHtml(w.image)}" alt="" loading="lazy" decoding="async" />` : `<div class="noimg">no image</div>`}
          </div>
        `;
        tr.appendChild(imgTd);

        const titleTd = document.createElement("td");
        titleTd.innerHTML = `
          <div class="cellTitle">${escapeHtml(w.title)}</div>
          <div class="cellDesc">${escapeHtml(w.desc)}</div>
        `;
        tr.appendChild(titleTd);

        const idTd = document.createElement("td");
        idTd.innerHTML = `<code style="font-weight:900; color:#1f2937;">${escapeHtml(w.id)}</code>`;
        tr.appendChild(idTd);

        const tagsTd = document.createElement("td");
        const tags = normalizeTags(w.tags);
        tagsTd.innerHTML = `
          <div class="tagList">
            ${tags.map(t => `<span class="tag">${escapeHtml(t)}</span>`).join("")}
          </div>
        `;
        tr.appendChild(tagsTd);

        const linksTd = document.createElement("td");
        const href = w.href ? String(w.href) : "";
        linksTd.innerHTML = `
          <div style="display:flex; flex-direction:column; gap:6px;">
            <div style="color:var(--muted); font-size:12px; font-weight:800;">image: <span style="color:#0f172a">${escapeHtml(w.image || "-")}</span></div>
            <div style="color:var(--muted); font-size:12px; font-weight:800;">href: ${href ? `<a href="${escapeHtml(href)}" target="_blank" rel="noreferrer" style="color:var(--accent); font-weight:900; text-decoration: none;">open</a>` : `<span style="color:#0f172a">-</span>`
          }</div>
          </div>
        `;
        tr.appendChild(linksTd);

        const actTd = document.createElement("td");
        actTd.innerHTML = `
          <div class="rowBtns">
            <button class="btn" data-act="edit">Edit</button>
            <button class="btn danger" data-act="del">Delete</button>
          </div>
        `;
        actTd.querySelector('[data-act="edit"]').addEventListener("click", () => openModal(idx));
        actTd.querySelector('[data-act="del"]').addEventListener("click", () => deleteAt(idx));
        tr.appendChild(actTd);

        $tbody.appendChild(tr);
      }
    }

    async function load() {
      try {
        const res = await fetch(WORKS_URL, { cache: "no-store" });
        if (!res.ok) throw new Error(`fetch failed: ${res.status}`);

        // Read text and strip comments before parsing
        const text = await res.text();
        const jsonStr = stripJsonComments(text);
        raw = JSON.parse(jsonStr);

        let list = Array.isArray(raw?.works) ? raw.works : [];

        // Recover commented-out items from text
        const commented = extractCommentedItems(text);
        list = [...list, ...commented];

        works = list;
        raw.works = works; // update raw structure

        works = works.map(w => ({
          ...w,
          image: imagePathForId(w?.id),
        }));
        buildChips(works);
        render();
        toast("Loaded works.json");
      } catch (e) {
        console.error(e);
        toast("Failed to load works.json");
      }
    }

    async function saveToServer() {
      // Use custom serializer
      const body = serializeWorksWithComments(works);

      const res = await fetch(SAVE_URL, {
        method: "POST",
        headers: {
          // Send as plain text (or custom type) so PHP reads raw input
          "Content-Type": "text/plain",
          "X-CMS-Token": CMS_TOKEN,
        },
        body: body,
      });

      // Accept either JSON or plain text error responses
      let data = null;
      try {
        data = await res.json();
      } catch (_) {
        // ignore
      }

      if (!res.ok) {
        const msg = (data && (data.error || data.message))
          ? (data.error || data.message)
          : `HTTP ${res.status}`;
        throw new Error(msg);
      }

      // If PHP returns {ok:false,...}, treat as error
      if (data && data.ok === false) {
        throw new Error(data.error || "Save failed");
      }

      return data;
    }

    function openModal(index) {
      editingIndex = index;

      const isNew = index === -1;
      const w = isNew ? {
        id: "",
        title: "",
        desc: "",
        image: "",
        note: "",
        href: "",
        tags: [],
      } : (works[index] ?? {});

      $modalTitle.textContent = isNew ? "Add Work" : "Edit Work";
      $modalSub.textContent = `id: ${w.id || "-"}`;

      $f_id.value = w.id ?? "";
      $f_title.value = w.title ?? "";
      $f_desc.value = w.desc ?? "";
      $f_note.value = w.note ?? "";
      $f_href.value = w.href ?? "";
      $f_tags.value = normalizeTags(w.tags).join(", ");

      el("btnDelete").style.display = isNew ? "none" : "inline-flex";

      $overlay.classList.add("open");
      document.body.style.overflow = "hidden";

      setImageIdSlot();
      clearPendingImage();
      setExistingImagePreviewById($f_id.value);
    }

    function closeModal() {
      $overlay.classList.remove("open");
      document.body.style.overflow = "";
      editingIndex = -1;
      clearPendingImage();
    }

    function validateUniqueId(id, allowIndex) {
      const exists = works.some((w, i) => w.id === id && i !== allowIndex);
      if (exists) return false;
      return true;
    }

    async function saveModal() {
      const id = ($f_id.value ?? "").trim();
      const title = ($f_title.value ?? "").trim();

      if (!id) {
        toast("id が空です");
        return;
      }
      if (!title) {
        toast("title が空です");
        return;
      }
      if (!validateUniqueId(id, editingIndex)) {
        toast("id が重複しています");
        return;
      }
      // Upload image only when a new file is selected
      if (pendingImageFile) {
        try {
          toast("画像をアップロード中...");
          await uploadImageForId(id, pendingImageFile);
          toast("画像をアップロードしました");
          clearPendingImage();
        } catch (e) {
          console.error(e);
          toast(String(e?.message ?? "画像アップロードに失敗しました"));
          return;
        }
      }
      const updated = {
        id,
        title,
        desc: ($f_desc.value ?? "").trim(),
        image: imagePathForId(id),
        note: ($f_note.value ?? ""),
        href: ($f_href.value ?? "").trim(),
        tags: normalizeTags($f_tags.value),
      };

      if (editingIndex === -1) {
        works.unshift(updated);
      } else {
        works[editingIndex] = updated;
      }

      raw = { ...raw, works };
      buildChips(works);
      render();

      try {
        await saveToServer();
        toast("Saved to works.json");
        closeModal();
      } catch (e) {
        console.error(e);
        toast("Save failed (server)");
        // Keep modal open so the user can retry/copy/export
      }

    }

    async function deleteAt(index) {
      const w = works[index];
      if (!w) return;
      const ok = window.confirm(`Delete "${w.title}" ?\n(This will PERMANENTLY remove it)`);
      if (!ok) return;

      // Hard delete: Remove from array
      works.splice(index, 1);

      // Update UI (filterItems will hide it)
      buildChips(works);
      render();

      // Save immediately
      try {
        await saveToServer();
        toast("Deleted");
      } catch (e) {
        console.error(e);
        toast("Delete failed (Save error)");
        // Revert UI if needed - reload to be safe
        load();
      }

      if ($overlay.classList.contains("open")) closeModal();
    }

    function exportJson() {
      const data = serializeWorksWithComments(works);
      const blob = new Blob([data], { type: "application/json" });
      const url = URL.createObjectURL(blob);

      const a = document.createElement("a");
      a.href = url;
      a.download = "works.json";
      document.body.appendChild(a);
      a.click();
      a.remove();

      window.setTimeout(() => URL.revokeObjectURL(url), 400);
      toast("Exported works.json");
    }

    async function copyJson() {
      try {
        const data = serializeWorksWithComments(works);
        await navigator.clipboard.writeText(data);
        toast("Copied JSON");
      } catch (e) {
        console.warn(e);
        toast("Copy failed");
      }
    }

    function importJsonFromFile(file) {
      const reader = new FileReader();
      reader.onload = () => {
        try {
          const text = String(reader.result ?? "{}");
          const parsed = JSON.parse(stripJsonComments(text));
          const next = Array.isArray(parsed?.works) ? parsed.works : [];
          // basic normalize
          works = next.map(w => ({
            id: String(w.id ?? "").trim(),
            title: String(w.title ?? "").trim(),
            desc: String(w.desc ?? ""),
            image: imagePathForId(w.id ?? ""),
            note: String(w.note ?? ""),
            href: String(w.href ?? ""),
            tags: normalizeTags(w.tags),
          })).filter(w => w.id && w.title);

          raw = { works };
          activeTag = "All";
          buildChips(works);
          render();
          toast("Imported JSON");
        } catch (e) {
          console.error(e);
          toast("Import failed (invalid JSON)");
        }
      };
      reader.readAsText(file);
    }

    // Events
    el("btnReload").addEventListener("click", load);
    el("btnExport").addEventListener("click", exportJson);
    el("btnAdd").addEventListener("click", () => openModal(-1));
    el("btnClose").addEventListener("click", closeModal);
    el("btnSave").addEventListener("click", saveModal);
    el("btnDelete").addEventListener("click", () => deleteAt(editingIndex));
    el("btnCopy").addEventListener("click", copyJson);
    $btnPickImage.addEventListener("click", () => $f_image_file.click());
    $btnClearImage.addEventListener("click", () => {
      clearPendingImage();
      setExistingImagePreviewById($f_id.value);
    });

    $f_image_file.addEventListener("change", () => {
      const f = $f_image_file.files && $f_image_file.files[0];
      if (f) setPendingImage(f);
    });

    // id を打ち替えた時に {id} 表示も更新
    $f_id.addEventListener("input", () => {
      setImageIdSlot();
      if (!pendingImageFile) {
        setExistingImagePreviewById($f_id.value);
      }
    });

    el("btnImport").addEventListener("click", () => $fileInput.click());
    $fileInput.addEventListener("change", () => {
      const f = $fileInput.files && $fileInput.files[0];
      if (f) importJsonFromFile(f);
      $fileInput.value = "";
    });

    // Click outside modal to close
    $overlay.addEventListener("click", (e) => {
      if (e.target === $overlay) closeModal();
    });

    // Search
    $q.addEventListener("input", () => render());

    // Init
    load();
  </script>
</body>

</html>