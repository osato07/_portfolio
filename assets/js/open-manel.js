(() => {
  const panel = document.getElementById('work-panel');
  const bodyEl = document.getElementById('work-panel-body');
  if (!panel || !bodyEl) return;

  let worksCache = null;

  function stripComments(jsonStr) {
    return jsonStr
      .replace(/\/\*[\s\S]*?\*\//g, "")
      .replace(/,(\s*[\]}])/g, "$1");
  }

  async function loadWorks() {
    if (worksCache) return worksCache;
    const res = await fetch('./assets/data/works.json', { cache: 'no-store' });
    if (!res.ok) throw new Error(`works.json fetch failed: ${res.status}`);
    const text = await res.text();
    worksCache = JSON.parse(stripComments(text));
    return worksCache;
  }

  function openPanel() {
    document.body.classList.add('work-panel-open');
    panel.setAttribute('aria-hidden', 'false');
    // Always show the top of the panel when opening
    panel.scrollTop = 0;
  }

  function closePanel() {
    document.body.classList.remove('work-panel-open');
    panel.setAttribute('aria-hidden', 'true');
  }

  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closePanel();
  });

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  // Minimal Markdown renderer (with fenced code blocks)
  function renderMarkdown(md) {
    const src = String(md || '').replace(/\r\n/g, '\n');
    const lines = src.split('\n');

    let out = '';
    let inList = false;

    // code fence state
    let inCode = false;
    let codeLang = 'txt';
    let codeBuf = [];

    const closeList = () => {
      if (inList) {
        out += '</ul>';
        inList = false;
      }
    };

    const flushCode = () => {
      const lang = (codeLang || 'txt').toLowerCase();
      const code = escapeHtml(codeBuf.join('\n'));
      out += `
        <pre class="work-panel__code"><span class="work-panel__codeLang">${escapeHtml(lang)}</span><code class="language-${escapeHtml(lang)}">${code}</code></pre>
      `;
      codeBuf = [];
      codeLang = 'txt';
    };

    for (const rawLine of lines) {
      const trimmed = rawLine.trim();

      // fenced code blocks: ```lang ... ```
      if (/^```/.test(trimmed)) {
        closeList();

        if (!inCode) {
          inCode = true;
          codeLang = trimmed.replace(/^```/, '').trim() || 'txt';
          codeBuf = [];
        } else {
          inCode = false;
          flushCode();
        }
        continue;
      }

      if (inCode) {
        // keep original line (do not trim)
        codeBuf.push(rawLine);
        continue;
      }

      if (!trimmed) {
        closeList();
        continue;
      }

      if (trimmed === '---') {
        closeList();
        out += '<hr />';
        continue;
      }

      if (/^###\s+/.test(trimmed)) {
        closeList();
        out += `<h3>${inline(trimmed.replace(/^###\s+/, ''))}</h3>`;
        continue;
      }
      if (/^##\s+/.test(trimmed)) {
        closeList();
        out += `<h2>${inline(trimmed.replace(/^##\s+/, ''))}</h2>`;
        continue;
      }
      if (/^#\s+/.test(trimmed)) {
        closeList();
        out += `<h1>${inline(trimmed.replace(/^#\s+/, ''))}</h1>`;
        continue;
      }

      if (/^>\s+/.test(trimmed)) {
        closeList();
        out += `<blockquote>${inline(trimmed.replace(/^>\s+/, ''))}</blockquote>`;
        continue;
      }

      if (/^- /.test(trimmed)) {
        if (!inList) {
          out += '<ul>';
          inList = true;
        }
        out += `<li>${inline(trimmed.replace(/^- /, ''))}</li>`;
        continue;
      }

      closeList();
      out += `<p>${inline(trimmed)}</p>`;
    }

    // if the markdown ends while still inside a code block, flush it safely
    if (inCode) {
      inCode = false;
      flushCode();
    }

    closeList();
    return out;

    function inline(t) {
      let s = escapeHtml(t);
      // images first
      s = s.replace(
        /!\[([^\]]*)\]\(([^)]+)\)/g,
        '<img class="work-panel__image" src="$2" alt="$1">'
      );
      // links
      s = s.replace(
        /\[([^\]]+)\]\(([^)]+)\)/g,
        '<a href="$2" target="_blank" rel="noreferrer">$1</a>'
      );
      // inline code
      s = s.replace(/`([^`]+)`/g, '<code>$1</code>');
      // bold
      s = s.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
      return s;
    }
  }

  function renderWorkDetail(w) {
    const title = w?.title || 'WORK';

    const tags = Array.isArray(w?.tags)
      ? `<div class="work-panel__tags">${w.tags.map(t => `<span class="work-panel__tag">${escapeHtml(t)}</span>`).join('')}</div>`
      : '';

    const hero = w?.image
      ? `<img class="work-panel__image" src="${escapeHtml(w.image)}" alt="">`
      : '';

    const noteHtml = renderMarkdown(w?.note || '');

    const link = w?.href
      ? `<p class="work-panel__footerLink"><a class="work-panel__link" href="${escapeHtml(w.href)}" target="_blank">Open Link</a></p>`
      : '';

    bodyEl.innerHTML = `
      <h1 class="work-panel__titleInBody">${escapeHtml(title)}</h1>
      ${w?.desc ? `<p class="sub">${escapeHtml(w.desc)}</p>` : ''}
      ${tags}
      ${hero}
      <div class="work-panel__note">${noteHtml}</div>
      ${link}
    `;

    bodyEl.scrollTop = 0;
  }

  // ★ここが肝：後から挿入された Open ボタンでも拾える
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-work-open],[data-work-id]');
    if (!btn) return;

    e.preventDefault();

    const workId = btn.getAttribute('data-work-open') || btn.getAttribute('data-work-id');
    if (!workId) return;

    try {
      const data = await loadWorks();
      const list = Array.isArray(data?.works) ? data.works : [];
      const w = list.find(x => String(x.id) === String(workId));

      renderWorkDetail(w || { title: 'WORK', note: `Not found: ${workId}` });
      openPanel();
    } catch (err) {
      console.warn('[WORK] open failed', err);
      renderWorkDetail({ title: 'WORK', note: 'Failed to load works.json' });
      openPanel();
    }
  });

  // Close when clicking outside the note/panel (but not when clicking an Open button)
  document.addEventListener('click', (e) => {
    if (!document.body.classList.contains('work-panel-open')) return;
    if (e.target.closest('#work-panel')) return;
    if (e.target.closest('[data-work-open],[data-work-id]')) return;
    closePanel();
  });
})();