/**
 * cach.js
 * Persistence for small state ("図鑑" progress, first-time flags, etc.)
 *
 * Usage:
 *   CacheBook.has('hero.voice.hello')
 *   CacheBook.mark('hero.voice.hello')
 *   CacheBook.getAll()
 *   CacheBook.reset()
 */

(() => {
  const DEBUG_CACHE = true; // set false to silence logs
  const log = (...args) => { if (DEBUG_CACHE) console.log('[CacheBook]', ...args); };

  const DEFAULT_COOKIE_NAME = 'portfolio_zukan';
  const DEFAULT_MAX_AGE_DAYS = 365;

  // ===== cookie primitives =====
  const getCookieRaw = (name) => {
    const escaped = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const m = document.cookie.match(new RegExp('(?:^|; )' + escaped + '=([^;]*)'));
    return m ? decodeURIComponent(m[1]) : null;
  };

  const setCookieRaw = (name, value, days = DEFAULT_MAX_AGE_DAYS) => {
    const maxAge = Math.max(0, Math.floor(days * 24 * 60 * 60));
    const encoded = encodeURIComponent(value);
    // SameSite=Lax is generally safe for in-site usage.
    document.cookie = `${name}=${encoded}; Path=/; Max-Age=${maxAge}; SameSite=Lax`;
  };

  const deleteCookie = (name) => {
    document.cookie = `${name}=; Path=/; Max-Age=0; SameSite=Lax`;
  };

  // Detect whether cookies are writable in the current context.
  // If not (e.g., strict settings), we fall back to localStorage.
  const canUseCookies = (cookieName = DEFAULT_COOKIE_NAME) => {
    try {
      const testKey = `${cookieName}__test`;
      const testVal = `t_${Date.now()}`;
      // write a short-lived test cookie
      document.cookie = `${testKey}=${encodeURIComponent(testVal)}; Path=/; Max-Age=60; SameSite=Lax`;
      const back = getCookieRaw(testKey);
      // cleanup
      deleteCookie(testKey);
      return back === testVal;
    } catch (_) {
      return false;
    }
  };

  // ===== JSON store =====
  const safeParse = (s) => {
    try { return JSON.parse(s); } catch (_) { return null; }
  };

  const nowIso = () => new Date().toISOString();

  class CookieJsonStore {
    constructor(cookieName = DEFAULT_COOKIE_NAME) {
      this.cookieName = cookieName;
      this._cache = null;
    }

    load() {
      if (this._cache) return this._cache;
      const raw = getCookieRaw(this.cookieName);
      const parsed = raw ? safeParse(raw) : null;
      this._cache = (parsed && typeof parsed === 'object') ? parsed : { v: 1, seen: {}, updatedAt: nowIso() };
      // normalize
      if (!this._cache.seen || typeof this._cache.seen !== 'object') this._cache.seen = {};
      if (!this._cache.v) this._cache.v = 1;
      if (!this._cache.updatedAt) this._cache.updatedAt = nowIso();
      log('load()', { backend: 'cookie', cookieName: this.cookieName, hasCookie: !!raw, size: raw ? raw.length : 0, updatedAt: this._cache.updatedAt });
      return this._cache;
    }

    save(days = DEFAULT_MAX_AGE_DAYS) {
      const data = this.load();
      data.updatedAt = nowIso();
      log('save()', { backend: 'cookie', cookieName: this.cookieName, updatedAt: data.updatedAt, keys: Object.keys(data.seen || {}).length });
      setCookieRaw(this.cookieName, JSON.stringify(data), days);
    }

    getSeenMap() {
      return this.load().seen;
    }

    has(key) {
      const seen = this.getSeenMap();
      return !!seen[key];
    }

    mark(key, meta) {
      if (!key) return;
      log('mark()', { backend: 'cookie', key, meta });
      const seen = this.getSeenMap();
      if (seen[key]) { log('mark() skipped (already seen)', { backend: 'cookie', key }); return; }
      seen[key] = {
        at: nowIso(),
        ...(meta && typeof meta === 'object' ? meta : {})
      };
      this.save();
    }

    unmark(key) {
      log('unmark()', { backend: 'cookie', key });
      const seen = this.getSeenMap();
      if (seen[key]) {
        delete seen[key];
        this.save();
      }
    }

    getAll() {
      return this.load();
    }

    reset() {
      this._cache = { v: 1, seen: {}, updatedAt: nowIso() };
      deleteCookie(this.cookieName);
      // write an empty cookie so the structure is consistent
      this.save();
    }
  }

  class LocalStorageJsonStore {
    constructor(storageKey = DEFAULT_COOKIE_NAME) {
      this.storageKey = storageKey;
      this._cache = null;
    }

    load() {
      if (this._cache) return this._cache;
      let raw = null;
      try { raw = localStorage.getItem(this.storageKey); } catch (_) { raw = null; }
      const parsed = raw ? safeParse(raw) : null;
      this._cache = (parsed && typeof parsed === 'object') ? parsed : { v: 1, seen: {}, updatedAt: nowIso() };
      if (!this._cache.seen || typeof this._cache.seen !== 'object') this._cache.seen = {};
      if (!this._cache.v) this._cache.v = 1;
      if (!this._cache.updatedAt) this._cache.updatedAt = nowIso();
      log('load()', { backend: 'localStorage', storageKey: this.storageKey, hasItem: !!raw, size: raw ? raw.length : 0, updatedAt: this._cache.updatedAt });
      return this._cache;
    }

    save() {
      const data = this.load();
      data.updatedAt = nowIso();
      const json = JSON.stringify(data);
      try { localStorage.setItem(this.storageKey, json); } catch (_) {}
      log('save()', { backend: 'localStorage', storageKey: this.storageKey, updatedAt: data.updatedAt, keys: Object.keys(data.seen || {}).length });
    }

    getSeenMap() {
      return this.load().seen;
    }

    has(key) {
      const seen = this.getSeenMap();
      return !!seen[key];
    }

    mark(key, meta) {
      if (!key) return;
      log('mark()', { backend: 'localStorage', key, meta });
      const seen = this.getSeenMap();
      if (seen[key]) { log('mark() skipped (already seen)', { backend: 'localStorage', key }); return; }
      seen[key] = { at: nowIso(), ...(meta && typeof meta === 'object' ? meta : {}) };
      this.save();
    }

    unmark(key) {
      log('unmark()', { backend: 'localStorage', key });
      const seen = this.getSeenMap();
      if (seen[key]) {
        delete seen[key];
        this.save();
      }
    }

    getAll() {
      return this.load();
    }

    reset() {
      this._cache = { v: 1, seen: {}, updatedAt: nowIso() };
      try { localStorage.removeItem(this.storageKey); } catch (_) {}
      this.save();
    }
  }

  // ===== Public API (global) =====
  // Keep names short and stable for easy use from hero.html / three.js.
  const useCookie = canUseCookies(DEFAULT_COOKIE_NAME);
  log('backend', { useCookie, protocol: location.protocol, host: location.host });
  const store = useCookie ? new CookieJsonStore(DEFAULT_COOKIE_NAME) : new LocalStorageJsonStore(DEFAULT_COOKIE_NAME);

  window.CacheBook = {
    // low-level access
    load: () => store.load(),
    save: (days) => store.save(days),
    getAll: () => store.getAll(),

    // Convenience: list keys under hero.voice.* for building the Info "図鑑" UI
    listVoices: () => {
      const all = store.getAll();
      const seen = (all && all.seen) ? all.seen : {};
      return Object.keys(seen)
        .filter((k) => k.startsWith('hero.voice.'))
        .map((k) => ({
          key: k,
          voiceId: k.slice('hero.voice.'.length),
          meta: seen[k]
        }));
    },

    reset: () => store.reset(),

    // key-based helpers
    has: (key) => store.has(key),
    mark: (key, meta) => store.mark(key, meta),
    unmark: (key) => store.unmark(key),

    // conventions
    markVoice: (voiceId, meta) => {
      // Persist any voice entry; filtering is done at the UI layer.
      // This avoids silent no-op when meta.type differs (e.g., "cach").
      if (!voiceId) return;
      store.mark(`hero.voice.${voiceId}`, meta);
      try {
        window.dispatchEvent(new CustomEvent('zukan:changed', { detail: store.getAll() }));
      } catch (_) {}
    },
    hasVoice: (voiceId) => store.has(`hero.voice.${voiceId}`),

    markFirst: (flagId, meta) => store.mark(`first.${flagId}`, meta),
    isFirstDone: (flagId) => store.has(`first.${flagId}`),

    // UI sync helper: dispatch an event after calling mark/markVoice in your code.
    // (Kept explicit to avoid surprises; you can call CacheBook.emitChange() when needed.)
    emitChange: () => {
      try {
        window.dispatchEvent(new CustomEvent('zukan:changed', { detail: store.getAll() }));
      } catch (_) {}
    },

    debugDump: () => {
      const all = store.getAll();
      log('dump', all);
      console.log('[CacheBook] protocol =', location.protocol);
      console.log('[CacheBook] document.cookie =', document.cookie);
      try {
        console.log('[CacheBook] localStorage item =', localStorage.getItem(DEFAULT_COOKIE_NAME));
      } catch (_) {}
      return all;
    },
  };
})();
