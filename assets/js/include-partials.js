(async () => {
  const elements = Array.from(document.querySelectorAll('[data-include]'));

  for (const el of elements) {
    const url = el.getAttribute('data-include');
    if (!url) continue;

    try {
      const res = await fetch(url);
      if (!res.ok) {
        console.error(`[include-partials] Failed to fetch ${url}: ${res.status}`);
        continue;
      }

      const html = await res.text();
      el.innerHTML = html;

      // Scripts inside injected HTML do NOT execute via innerHTML.
      // Re-create them so the browser runs them.
      const scripts = Array.from(el.querySelectorAll('script'));
      for (const oldScript of scripts) {
        const newScript = document.createElement('script');

        // Copy attributes (type, src, defer, etc.)
        for (const attr of Array.from(oldScript.attributes)) {
          newScript.setAttribute(attr.name, attr.value);
        }

        // Copy inline content (only when src is not set)
        if (!oldScript.src) {
          newScript.textContent = oldScript.textContent;
        }

        oldScript.parentNode?.replaceChild(newScript, oldScript);
      }
    } catch (e) {
      console.error(`[include-partials] Error loading ${url}`, e);
    }
  }

  // Optional: notify that partials are ready.
  window.dispatchEvent(new Event('partials:loaded'));
})();