// Renderizador por sequência de frames (vídeo cinematográfico → Canvas).
// Ativado automaticamente quando existe assets/frames/manifest.json
// (gerado por `npm run frames -- caminho/do/video.mp4`).
// Mesma interface da cena WebGL: setHero, setOutro, render, resize, getAnchors.

const clamp = (v, a = 0, b = 1) => Math.min(b, Math.max(a, v));

export async function createFrameRenderer(canvas, manifest, env) {
  const set = env.mobile && manifest.mobile ? manifest.mobile : manifest.desktop;
  const ctx = canvas.getContext('2d', { alpha: false });
  const frames = new Array(set.count);
  let target = 0, drawn = -1, size = { w: 1, h: 1 }, dpr = 1;
  // Fase do hero em que o vídeo é percorrido (alinhada à narrativa em hero.js)
  const [pStart, pEnd] = manifest.range || [0.05, 0.95];

  const src = (i) => set.pattern.replace('%04d', String(i + 1).padStart(4, '0'));
  const load = (i) => new Promise((resolve) => {
    if (frames[i]) return resolve(frames[i]);
    const img = new Image();
    img.decoding = 'async';
    img.onload = () => { frames[i] = img; resolve(img); };
    img.onerror = () => resolve(null);
    img.src = src(i);
  });

  // Carregamento progressivo: primeiro quadro, depois quadros-chave, depois o restante.
  await load(0);
  (async () => {
    const order = [];
    for (let step = 16; step >= 1; step = Math.floor(step / 2)) {
      for (let i = 0; i < set.count; i += step) if (!order.includes(i)) order.push(i);
      if (step === 1) break;
    }
    for (let i = 0; i < order.length; i += 6) await Promise.all(order.slice(i, i + 6).map(load));
  })();

  function nearest(i) {
    for (let d = 0; d < set.count; d++) {
      if (frames[i - d]) return frames[i - d];
      if (frames[i + d]) return frames[i + d];
    }
    return null;
  }

  function draw(i) {
    const img = nearest(i);
    if (!img) return;
    const { w, h } = size;
    const s = Math.max(w / img.naturalWidth, h / img.naturalHeight);
    const dw = img.naturalWidth * s, dh = img.naturalHeight * s;
    ctx.drawImage(img, (w - dw) / 2, (h - dh) / 2, dw, dh);
    drawn = i;
  }

  function resize() {
    dpr = Math.min(window.devicePixelRatio || 1, 2);
    size = { w: window.innerWidth * dpr, h: window.innerHeight * dpr };
    canvas.width = size.w;
    canvas.height = size.h;
    drawn = -1;
  }

  resize();
  return {
    kind: 'frames',
    ready: Promise.resolve(),
    setHero(p) { target = Math.round(clamp((p - pStart) / (pEnd - pStart)) * (set.count - 1)); },
    setOutro(q) { target = Math.round((1 - clamp(q)) * (set.count - 1) * 0.35); },
    render() { if (target !== drawn || !frames[target]) draw(target); },
    resize,
    getAnchors: () => null,
  };
}
