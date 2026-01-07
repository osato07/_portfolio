import * as THREE from "three";
import { OrbitControls } from "three/examples/jsm/controls/OrbitControls.js";
import { GLTFLoader } from "three/examples/jsm/loaders/GLTFLoader.js";

function waitForElement(selector, timeoutMs = 8000) {
  return new Promise((resolve) => {
    const existing = document.querySelector(selector);
    if (existing) return resolve(existing);

    const obs = new MutationObserver(() => {
      const el = document.querySelector(selector);
      if (el) {
        obs.disconnect();
        resolve(el);
      }
    });

    obs.observe(document.documentElement, { childList: true, subtree: true });

    window.setTimeout(() => {
      obs.disconnect();
      resolve(null);
    }, timeoutMs);
  });
}

(async () => {
  const stageEl = await waitForElement("#stage");
  if (!stageEl) {
    console.warn("[three-stage] #stage not found (timeout)");
    return;
  }
  const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  renderer.setSize(stageEl.clientWidth, stageEl.clientHeight);
  stageEl.appendChild(renderer.domElement);

  // Ensure stage is a positioning context
  stageEl.style.position = stageEl.style.position || "relative";

  // Comment layer should be BEHIND the WebGL canvas
  const commentLayer = document.createElement("div");
  commentLayer.id = "comment-layer";
  commentLayer.style.position = "absolute";
  commentLayer.style.inset = "0";
  commentLayer.style.overflow = "hidden";
  commentLayer.style.pointerEvents = "none";
  commentLayer.style.zIndex = "0";
  stageEl.appendChild(commentLayer);

  // WebGL canvas goes above the comments
  renderer.domElement.style.position = "relative";
  renderer.domElement.style.zIndex = "1";

  const scene = new THREE.Scene();

  // ===== Tap-to-comment (Raycaster) =====
  const raycaster = new THREE.Raycaster();
  const pointer = new THREE.Vector2();
  let tappableRoot = null; // set after model load

  // Cache hero.json so we only fetch once
  let heroCache = null;
  async function loadHeroData() {
    if (heroCache) return heroCache;
    const res = await fetch("./assets/data/hero.json", { cache: "no-store" });
    if (!res.ok) throw new Error(`hero.json fetch failed: ${res.status}`);
    const data = await res.json();
    // support either {items:[...]} or direct array
    heroCache = Array.isArray(data) ? data : (Array.isArray(data?.items) ? data.items : []);
    return heroCache;
  }

  function pickRandom(arr) {
    if (!arr || arr.length === 0) return null;
    const i = Math.floor(Math.random() * arr.length);
    return arr[i];
  }

  // Create a stable-ish id even if hero.json items don't have an explicit id.
  function djb2Hash(str) {
    let h = 5381;
    for (let i = 0; i < str.length; i++) {
      h = ((h << 5) + h) ^ str.charCodeAt(i);
    }
    // Convert to unsigned 32-bit
    return (h >>> 0).toString(16);
  }

  function makeVoiceId(picked) {
    // Prefer explicit ids if provided by hero.json
    const explicit = picked?.id ?? picked?.voiceId ?? picked?.key ?? picked?.name;
    if (explicit) return String(explicit);

    // Otherwise hash on content
    const text = picked?.text ?? picked?.message ?? '';
    const audio = picked?.audio ?? picked?.sound ?? '';
    return `auto_${djb2Hash(`${text}|${audio}`)}`;
  }

  function persistToCacheBook(voiceId, meta) {
    const book = window.CacheBook;
    if (!book || typeof book.markVoice !== 'function') {
      console.warn('[hero-cache] CacheBook not ready; skip persist', { voiceId, meta });
      return;
    }
    try {
      book.markVoice(voiceId, meta);
      if (typeof book.debugDump === 'function') book.debugDump();
      console.log('[hero-cache] saved', { voiceId, meta });
    } catch (e) {
      console.warn('[hero-cache] persist failed', e);
    }
  }

  function playOneShot(src) {
    if (!src) return;
    try {
      const a = new Audio(src);
      a.preload = "auto";
      a.volume = 1;
      // user gesture (tap) allows playback
      void a.play();
    } catch (e) {
      console.warn("[hero-audio] play failed", e);
    }
  }

  function spawnComment(text) {
    if (!text) return;

    const el = document.createElement("div");
    el.textContent = String(text);

    // NicoNico-like style (subtle, behind model)
    el.style.position = "absolute";
    el.style.left = "0";
    el.style.top = "0";
    el.style.whiteSpace = "nowrap";

    // Random font size for NicoNico-like variety
    const minSize = 12;
    const maxSize = 24;
    const size = Math.round(minSize + Math.random() * (maxSize - minSize));
    el.style.fontSize = `${size}px`;

    el.style.fontWeight = "700";
    el.style.color = "rgba(40, 40, 40, 0.32)";
    el.style.textShadow = "0 1px 0 rgba(255,255,255,0.18)";
    el.style.userSelect = "none";

    commentLayer.appendChild(el);

    const layerRect = commentLayer.getBoundingClientRect();
    const elRect = el.getBoundingClientRect();

    // Random Y within the stage (avoid top loader area a bit)
    const paddingTop = 10;
    const paddingBottom = 10;
    const maxY = Math.max(paddingTop, layerRect.height - paddingBottom - elRect.height);
    const y = paddingTop + Math.random() * Math.max(0, maxY - paddingTop);

    el.style.top = `${Math.round(y)}px`;

    // Animate left -> right across the stage
    const fromX = -elRect.width - 10;
    const toX = layerRect.width + 10;

    const duration = 6000 + Math.random() * 2500;

    const anim = el.animate(
      [
        { transform: `translateX(${fromX}px)` },
        { transform: `translateX(${toX}px)` },
      ],
      { duration, easing: "linear", fill: "forwards" },
    );

    anim.onfinish = () => {
      el.remove();
    };
  }

  async function handleModelTap() {
    try {
      const items = await loadHeroData();
      const picked = pickRandom(items);
      if (!picked) return;

      // Allow both {text,audio} and {message,sound}
      const text = picked.text ?? picked.message ?? "";
      const audio = picked.audio ?? picked.sound ?? "";

      // Persist only items explicitly marked as type === 'info'
      const itemType = picked?.type;
      if (itemType === 'info') {
        const voiceId = makeVoiceId(picked);
        persistToCacheBook(voiceId, {
          type: 'info',
          text,
          sound: audio,
          source: 'three',
        });
      }

      if (audio) playOneShot(audio);
      if (text) spawnComment(text);
    } catch (e) {
      console.warn("[hero] tap action failed", e);
    }
  }
  const camera = new THREE.PerspectiveCamera(
    45,
    stageEl.clientWidth / stageEl.clientHeight,
    0.1,
    1000
  );
  camera.position.set(0, 1.2, 3);

  const controls = new OrbitControls(camera, renderer.domElement);
  controls.enableDamping = true;
  controls.enablePan = false;
  // Zoom is enabled only while holding Shift.
  // We start disabled and toggle it via keyboard + wheel gating below.
  controls.enableZoom = false;
  controls.enableRotate = true;

  // ===== Shift + Wheel => zoom the model, otherwise let the page scroll =====
  let shiftDown = false;
  window.addEventListener("keydown", (e) => {
    if (e.key === "Shift") {
      shiftDown = true;
      controls.enableZoom = true;
    }
  });

  window.addEventListener("keyup", (e) => {
    if (e.key === "Shift") {
      shiftDown = false;
      controls.enableZoom = false;
    }
  });

  // Capture phase so we can block OrbitControls from eating wheel events
  // when Shift is NOT pressed.
  renderer.domElement.addEventListener(
    "wheel",
    (e) => {
      if (shiftDown || e.shiftKey) {
        // Zoom intent: stop page scroll
        e.preventDefault();
        return; // allow OrbitControls to handle the wheel
      }

      // Normal scroll intent: do not preventDefault, but stop OrbitControls
      // from reacting to the wheel.
      e.stopImmediatePropagation();
    },
    { passive: false, capture: true },
  );

  // light
  scene.add(new THREE.AmbientLight(0xffffff, 0.9));
  const dir = new THREE.DirectionalLight(0xffffff, 1.0);
  dir.position.set(2, 3, 2);
  scene.add(dir);

  // loader UI
  const loaderEl = document.getElementById("model-loader");
  const showLoader = (v) => {
    if (!loaderEl) return;
    loaderEl.style.display = v ? "flex" : "none";
    loaderEl.setAttribute("aria-hidden", v ? "false" : "true");
  };

  // model load（パスはあなたのモデルに合わせて変更）
  const loader = new GLTFLoader();
  showLoader(true);
  loader.load(
    "./assets/model/ash_face.glb",
    (gltf) => {
      const model = gltf.scene;
      model.position.set(0, 0, 0);
      scene.add(model);
      tappableRoot = model;
      showLoader(false);
    },
    undefined,
    (err) => {
      console.error("[three-stage] load failed", err);
      showLoader(false);
    }
  );

  // Tap detection on canvas
  renderer.domElement.addEventListener("pointerdown", (e) => {
    if (!tappableRoot) return;

    const rect = renderer.domElement.getBoundingClientRect();
    const x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
    const y = -(((e.clientY - rect.top) / rect.height) * 2 - 1);

    pointer.set(x, y);
    raycaster.setFromCamera(pointer, camera);

    const hits = raycaster.intersectObject(tappableRoot, true);
    if (hits && hits.length > 0) {
      void handleModelTap();
    }
  });

  function animate() {
    requestAnimationFrame(animate);
    controls.update();
    renderer.render(scene, camera);
  }
  animate();

  function updateControlsMode() {
    // 768px breakpoint for mobile/tablet
    const isMobile = window.matchMedia("(max-width: 768px)").matches;
    if (isMobile) {
      // Mobile: Disable camera controls so the user can scroll the page
      controls.enabled = false;
      // Allow browser to handle touch actions (scrolling)
      renderer.domElement.style.touchAction = "auto";
    } else {
      // Desktop: Enable camera controls (rotate, etc)
      controls.enabled = true;
      // OrbitControls usually wants touch-action: none, 
      // though explicitly setting it handles the switch back from mobile
      renderer.domElement.style.touchAction = "none";
    }
  }

  // Initial check
  updateControlsMode();

  window.addEventListener("resize", () => {
    const w = stageEl.clientWidth;
    const h = stageEl.clientHeight;
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
    renderer.setSize(w, h);
    
    // Update controls state on resize
    updateControlsMode();
  });
})();
