(() => {
  // Не запускаємо двічі
  if (window.__snowCanvasRunning) {
    return;
  }
  window.__snowCanvasRunning = true;

  // Повага до reduced motion
  const prefersReduced = window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches;
  if (prefersReduced) {
    return;
  }

  const SNOW_CHAR = '❄︎'; // <- важливо: "text presentation" (FE0E), щоб працював color через fillStyle

  const blueShades = [
    'rgba(173, 216, 230, 0.8)',
    'rgba(175, 238, 238, 0.8)',
    'rgba(135, 206, 235, 0.8)',
    'rgba(0, 206, 209, 0.8)',
    'rgba(30, 144, 255, 0.8)',
  ];

  const isMobile = () => window.innerWidth < 768;

  // Налаштування (можеш крутити)
  const cfg = {
    zIndex: 9999,
    maxDpr: 2,               // не даємо малювати 3x/4x на Retina
    fpsMobile: 30,
    fpsDesktop: 60,
    density: 8500,           // чим БІЛЬШЕ число — тим МЕНШЕ сніжинок
    maxFlakesMobile: 45,
    maxFlakesDesktop: 140,
    windBase: 6,             // горизонтальний дрейф
    windGust: 10,            // додаткова "поривчастість"
  };

  // Canvas layer
  const canvas = document.createElement('canvas');
  canvas.id = 'snow-canvas-layer';
  canvas.style.position = 'fixed';
  canvas.style.left = '0';
  canvas.style.top = '0';
  canvas.style.width = '100vw';
  canvas.style.height = '100vh';
  canvas.style.pointerEvents = 'none';
  canvas.style.userSelect = 'none';
  canvas.style.zIndex = String(cfg.zIndex);
  canvas.style.display = 'block';
  canvas.style.contain = 'layout paint';
  document.body.appendChild(canvas);

  const ctx = canvas.getContext('2d', { alpha: true, desynchronized: true });

  let W = 0, H = 0, dpr = 1;

  function resizeCanvas() {
    W = window.innerWidth;
    H = window.innerHeight;
    dpr = Math.min(window.devicePixelRatio || 1, cfg.maxDpr);

    canvas.width = Math.floor(W * dpr);
    canvas.height = Math.floor(H * dpr);

    // Малюємо в CSS-пікселях
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  }

  // Кеш "картинок" сніжинок (offscreen) щоб не робити fillText складно кожен кадр
  const glyphCache = new Map();

  function getGlyph(sizePx, color) {
    const key = `${sizePx}|${color}`;
    const cached = glyphCache.get(key);
    if (cached) {
      return cached;
    }

    const pad = Math.ceil(sizePx * 0.6);
    const c = document.createElement('canvas');
    c.width = sizePx + pad;
    c.height = sizePx + pad;
    const cctx = c.getContext('2d');

    // Важливо: шрифт з символами
    cctx.font = `${sizePx}px "Segoe UI Symbol","Noto Sans Symbols","Arial",sans-serif`;
    cctx.textAlign = 'center';
    cctx.textBaseline = 'middle';
    cctx.fillStyle = color;

    cctx.clearRect(0, 0, c.width, c.height);
    cctx.fillText(SNOW_CHAR, c.width / 2, c.height / 2);

    glyphCache.set(key, c);
    return c;
  }

  // Сніжинки
  let flakes = [];

  function calcFlakeCount() {
    const area = W * H;
    const byDensity = Math.round(area / cfg.density);
    const max = isMobile() ? cfg.maxFlakesMobile : cfg.maxFlakesDesktop;
    return Math.max(10, Math.min(max, byDensity));
  }

  function rand(min, max) { return Math.random() * (max - min) + min; }

  function pick(arr) { return arr[(Math.random() * arr.length) | 0]; }

  function makeFlake(spawnTop = true) {
    const mobile = isMobile();
    const size = mobile ? rand(4, 10) : rand(6, 18);
    const speedY = (mobile ? rand(18, 45) : rand(22, 70)) * (size / 10); // px/sec
    const baseWind = (mobile ? rand(-8, 8) : rand(-10, 10));

    return {
      x: rand(0, W),
      y: spawnTop ? rand(-H, 0) : rand(0, H),
      size,
      speedY,
      wind: baseWind,
      phase: rand(0, Math.PI * 2),
      wobble: rand(0.4, 1.2),
      opacity: rand(0.55, 0.95),
      color: pick(blueShades),
      rot: rand(0, Math.PI * 2),
      rotSpeed: rand(-0.8, 0.8),
    };
  }

  function rebuildFlakes() {
    const target = calcFlakeCount();
    if (flakes.length === target) {
      return;
    }

    if (flakes.length < target) {
      while (flakes.length < target) {
        flakes.push(makeFlake(true));
      }
    } else {
      flakes.length = target;
    }
  }

  // FPS throttle
  let lastT = performance.now();
  let acc = 0;
  let paused = false;

  function targetFrameMs() {
    return 1000 / (isMobile() ? cfg.fpsMobile : cfg.fpsDesktop);
  }

  function tick(now) {
    if (!window.__snowCanvasRunning) {
      return;
    }
    requestAnimationFrame(tick);
    if (paused) {
      return;
    }

    const dtMs = now - lastT;
    lastT = now;

    // throttle FPS
    acc += dtMs;
    const frameMs = targetFrameMs();
    if (acc < frameMs) {
      return;
    }
    const dt = Math.min(acc / 1000, 0.05); // clamp (сек)
    acc = 0;

    ctx.clearRect(0, 0, W, H);

    // легкий "порив" вітру від часу
    const wind = cfg.windBase + Math.sin(now * 0.00035) * cfg.windGust;

    for (let i = 0; i < flakes.length; i++) {
      const f = flakes[i];

      // рух
      f.phase += dt * f.wobble;
      f.y += f.speedY * dt;

      const sway = Math.sin(f.phase) * 8;         // "хитання"
      f.x += (f.wind + wind * 0.15) * dt + sway * dt;

      f.rot += f.rotSpeed * dt;

      // wrap по X
      if (f.x < -40) {
        f.x = W + 40;
      }
      if (f.x > W + 40) {
        f.x = -40;
      }

      // якщо впала — ресет
      if (f.y > H + 60) {
        flakes[i] = makeFlake(true);
        continue;
      }

      // fade in/out
      const yNorm = f.y / H;
      const fadeIn = Math.min(1, Math.max(0, (yNorm + 0.05) / 0.15));      // на старті
      const fadeOut = 1 - Math.min(1, Math.max(0, (yNorm - 0.9) / 0.15));  // в кінці
      const alpha = f.opacity * fadeIn * fadeOut;

      // малювання (через кеш)
      const glyph = getGlyph(Math.round(f.size), f.color);

      ctx.save();
      ctx.globalAlpha = alpha;
      ctx.translate(f.x, f.y);
      ctx.rotate(f.rot);
      ctx.drawImage(glyph, -glyph.width / 2, -glyph.height / 2);
      ctx.restore();
    }
  }

  // Пауза у фоні
  document.addEventListener('visibilitychange', () => {
    paused = document.hidden;
  });

  // Resize + rebuild
  let rT = null;
  window.addEventListener('resize', () => {
    clearTimeout(rT);
    rT = setTimeout(() => {
      resizeCanvas();
      rebuildFlakes();
    }, 150);
  });

  // Старт
  function start() {
    resizeCanvas();
    rebuildFlakes();
    lastT = performance.now();
    acc = 0;
    paused = document.hidden;
    requestAnimationFrame(tick);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start, { once: true });
  } else {
    start();
  }

  // Опційно: можливість зупинити вручну в консолі
  window.stopSnow = () => {
    window.__snowCanvasRunning = false;
    canvas.remove();
  };
})();
