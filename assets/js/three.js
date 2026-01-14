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

  // Interaction variables
  let activeCollider = null;

  // Morph Animation Variables
  let morphMesh = null;
  // Map morph name to index in the mesh
  const morphIndices = {};
  // State for pop animations: { name: { current: 0, pop: 0 } }
  const morphState = {};

  const MORPH_SPEED = 2.5;
  const lerpSpeed = 0.2;


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

    // Optimization: Cache layer rect if possible or accept one reflow. 
    // To reduce jank, we can just read it. The main jank source is synchronous layout.
    // Let's try to be safer.
    const layerRect = commentLayer.getBoundingClientRect();
    const elRect = el.getBoundingClientRect(); // This forces layout of 'el'

    // Random Y within the stage
    const paddingTop = 10;
    const paddingBottom = 10;
    // Ensure we don't get negative values
    const safeHeight = Math.max(0, layerRect.height - paddingBottom - elRect.height);
    const maxY = Math.max(paddingTop, safeHeight);
    const y = paddingTop + Math.random() * (maxY - paddingTop);

    el.style.top = `${Math.round(y)}px`;

    // Animate left -> right across the stage
    const fromX = -elRect.width - 20;
    const toX = layerRect.width + 20;

    // Use a slightly longer duration for smoothness
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

  // --- Jaw Pop Trigger ---
  function triggerJawPop() {
    // Add pop value (clamped to sensible max later)
    jawPopValue = 1.0;
    console.log("[three-stage] Jaw pop triggered!");
  }

  // Wrap handleModelTap to also check collider
  async function handleTapOrPop(hits) {
    // Check if we hit the collider
    const hitCollider = hits.find(h => h.object.name.includes("COLLIDER_JAW"));
    if (hitCollider) {
      triggerJawPop();
      // Decide if collider click ALSO triggers hero tap. 
      // User said "tap is possible...". Let's trigger hero tap too for fun, or just pop.
      // For now, let's allow fallthrough or specific logic.
      // If the prompt implies the collider is FOR the jaw pop specifically:
      // triggerJawPop is enough. But the user said "can tap".
      // Let's assume hitting the collider is a "Jaw Tap".
      // We can ALSO do the hero tap if needed, but let's stick to pop for collider hits.
      return;
    }

    // Otherwise normal model tap
    await handleModelTap();
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

  // model load
  const loader = new GLTFLoader();
  showLoader(true);
  loader.load(
    "./assets/model/satoshi.glb",
    (gltf) => {
      const model = gltf.scene;
      model.position.set(0, 0, 0);
      // Face front (Rotate right 90 deg)
      model.rotation.y = -Math.PI / 2;
      scene.add(model);
      tappableRoot = model;
      tappableRoot = model;
      showLoader(false);

      // Load Colliders
      const colliders = [
        { file: 'COLLIDER_JAW.glb', key: 'jaw' },
        { file: 'COLLIDER_NOSE.glb', key: 'nose' },
        { file: 'COLLIDER_TOP.glb', key: 'top' },
        { file: 'COLLIDER_EYE_RIGHT.glb', key: 'eye_right' },
        { file: 'COLLIDER_EYE_LEFT.glb', key: 'eye_left' },
        { file: 'COLLIDER_EAR_RIGHT.glb', key: 'ear_right' },
        { file: 'COLLIDER_EAR_LEFT.glb', key: 'ear_left' },
      ];

      const colliderLoader = new GLTFLoader();

      colliders.forEach(item => {
        colliderLoader.load(`./assets/model/${item.file}`, (cGltf) => {
          const colScene = cGltf.scene;
          model.add(colScene);

          colScene.traverse((c) => {
            if (c.isMesh) {
              // Name it specifically so we can ID it later
              c.name = `COLLIDER__${item.key}`;
              c.material.transparent = true;
              c.material.opacity = 0;
              c.material.depthWrite = false;
            }
          });
          console.log(`[three-stage] Loaded collider: ${item.key}`);
        });
      });

      // Find Multiple Morph Targets
      model.traverse((child) => {
        if (child.isMesh && child.morphTargetInfluences && !morphMesh) {
          const dict = child.morphTargetDictionary;
          if (dict) {
            console.log("[three-stage] found mesh with morphs:", child.name, dict);

            // Map known keys to indices
            const keysToTrack = ['jaw', 'nose', 'top', 'eye_right', 'eye_left', 'ear_right', 'ear_left'];
            keysToTrack.forEach(key => {
              if (Object.prototype.hasOwnProperty.call(dict, key)) {
                morphIndices[key] = dict[key];
                // Initialize state
                morphState[key] = { current: 0, pop: 0 };
                console.log(`[three-stage] Mapped morph: ${key} -> ${dict[key]}`);
              }
            });

            if (Object.keys(morphIndices).length > 0) {
              morphMesh = child;
            }
          }
        }
      });
    },
    undefined,
    (err) => {
      console.error("[three-stage] load failed", err);
      showLoader(false);
    }
  );

  // Tap detection on canvas


  function animate() {
    requestAnimationFrame(animate);

    // Animate Morphs
    if (morphMesh) {
      // Loop through all mapped keys
      Object.keys(morphIndices).forEach(key => {
        const index = morphIndices[key];
        const state = morphState[key];
        if (!state) return;

        // Target is usually 0 unless popped
        let target = 0;

        // If held (only checking jaw for holding logic for now, or maybe generic?)
        // Original logic had hold for jaw. Let's keep hold logic generic if we want?
        // Actually user prompt implies "tap" -> "animation happens".
        // The hold logic was: (activeCollider && activeCollider.name === "COLLIDER_JAW") ? 1.0 : 0.0;
        // We can adapt that:
        if (activeCollider && activeCollider.name === `COLLIDER__${key}`) {
          target = 1.0;
        }

        // Pop Logic (Additive)
        if (state.pop > 0) {
          target = Math.max(target, state.pop);
          state.pop -= 0.08; // Decay
          if (state.pop < 0) state.pop = 0;
        }

        // Lerp
        state.current += (target - state.current) * lerpSpeed;

        // Apply
        morphMesh.morphTargetInfluences[index] = state.current;
      });
    }

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

  // Interaction variables
  // (activeCollider moved to top scope)

  // Track drag vs tap
  let isDragging = false;
  let downX = 0;
  let downY = 0;

  // Pointer Down (Press)
  renderer.domElement.addEventListener("pointerdown", (e) => {
    isDragging = false;
    downX = e.clientX;
    downY = e.clientY;

    const rect = renderer.domElement.getBoundingClientRect();
    pointer.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
    pointer.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;

    raycaster.setFromCamera(pointer, camera);
    const intersects = raycaster.intersectObjects(scene.children, true);

    if (intersects.length > 0) {
      for (let i = 0; i < intersects.length; i++) {
        const object = intersects[i].object;
        // Collider Check logic
        if (object.name && object.name.startsWith("COLLIDER__")) {
          activeCollider = object;
          controls.enabled = false;
          break;
        }
      }
    }
  });

  // Pointer Move (to detect drag)
  renderer.domElement.addEventListener("pointermove", (e) => {
    // simple distance check
    const moveDist = Math.sqrt(Math.pow(e.clientX - downX, 2) + Math.pow(e.clientY - downY, 2));
    if (moveDist > 5) {
      isDragging = true;
    }
  });

  // Pointer Up / Leave (Release)
  function onRelease(e) {
    // If it was a Collider release
    if (activeCollider) {
      activeCollider = null;
      updateControlsMode();
    }

    // Handle Click (Tap) Logic here instead of separate 'click' which might conflict
    // If not dragging and we are inside the canvas
    if (!isDragging && e.type === "pointerup") {
      // Raycast again to see what we clicked
      const rect = renderer.domElement.getBoundingClientRect();
      pointer.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
      pointer.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;
      raycaster.setFromCamera(pointer, camera);

      // Intersect everything including invisible colliders
      const intersects = raycaster.intersectObjects(scene.children, true);
      if (intersects.length > 0) {
        // Check what we hit
        let hitKey = null;

        // Prioritize direct collider hits
        for (let i = 0; i < intersects.length; i++) {
          const obj = intersects[i].object;
          if (obj.name && obj.name.startsWith("COLLIDER__")) {
            hitKey = obj.name.replace("COLLIDER__", "");
            break;
          }
        }

        // If we hit the model but NOT a collider (fallback), maybe just do nothing or random?
        // Or if we hit the main model mesh, maybe we default to 'nose' or just play sound?
        // The user said "Central nose runs jaw"... we fixed that.
        // If we hit non-collider, just playing sound is fine.
        // But if we hit a collider, we pop it.

        if (hitKey && morphState[hitKey]) {
          morphState[hitKey].pop = 1.0;
          console.log(`[three-stage] Popping: ${hitKey}`);
        }

        // Trigger Hero Action (Sound/Text) regardless of what part was hit
        handleModelTap().catch(err => console.error(err));
      }
    }
  }

  window.addEventListener("pointerup", onRelease);
  window.addEventListener("pointerleave", onRelease);

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
