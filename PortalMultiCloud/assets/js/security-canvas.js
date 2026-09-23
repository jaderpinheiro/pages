// Camadas de proteção (Segurança): anéis surgem de fora para dentro ao redor do
// núcleo e ameaças que se aproximam são defletidas pela camada ativa mais externa.
const clamp = (v, a = 0, b = 1) => Math.min(b, Math.max(a, v));
const smooth = (a, b, v) => { const t = clamp((v - a) / (b - a)); return t * t * (3 - 2 * t); };

export function createShield(canvas, env) {
  const g = canvas.getContext('2d');
  const RINGS = 5;
  const N = env.mobile ? 22 : 44;
  let W = 1, H = 1, cx = 0, cy = 0, R0 = 40, gap = 30, maxR = 200;
  let progress = 0, active = false, raf = 0, last = 0;
  const flashes = [];
  const particles = [];

  function resize() {
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    W = canvas.clientWidth || 1;
    H = canvas.clientHeight || 1;
    canvas.width = W * dpr;
    canvas.height = H * dpr;
    g.setTransform(dpr, 0, 0, dpr, 0, 0);
    const narrow = W < 1025;
    cx = narrow ? W * 0.5 : W * 0.7;
    cy = narrow ? H * 0.54 : H * 0.48;
    maxR = Math.min(narrow ? W * 0.42 : W * 0.26, H * (narrow ? 0.21 : 0.38));
    R0 = maxR * 0.3;
    gap = (maxR - R0) / (RINGS - 1);
  }

  const ringR = (k) => R0 + (RINGS - 1 - k) * gap; // k=0 é o anel externo (L1)
  const ringVis = (k) => smooth(0.1 + k * 0.12, 0.18 + k * 0.12, progress);

  function spawn(p = {}) {
    const a = Math.random() * Math.PI * 2;
    const d = Math.max(W, H) * (0.55 + Math.random() * 0.3);
    const speed = 45 + Math.random() * 60;
    const tx = cx + (Math.random() - 0.5) * R0, ty = cy + (Math.random() - 0.5) * R0;
    const x = cx + Math.cos(a) * d, y = cy + Math.sin(a) * d;
    const len = Math.hypot(tx - x, ty - y);
    Object.assign(p, { x, y, vx: ((tx - x) / len) * speed, vy: ((ty - y) / len) * speed, life: 1, hit: false, trail: [] });
    return p;
  }
  for (let i = 0; i < N; i++) {
    const p = spawn();
    const k = Math.random();
    p.x += p.vx * k * 6;
    p.y += p.vy * k * 6;
    particles.push(p);
  }

  function outerActive() {
    for (let k = 0; k < RINGS; k++) if (ringVis(k) > 0.6) return k;
    return -1;
  }

  function draw(time, dt) {
    g.clearRect(0, 0, W, H);

    // Núcleo
    const glow = g.createRadialGradient(cx, cy, 0, cx, cy, R0 * 1.6);
    glow.addColorStop(0, 'rgba(1,174,255,0.28)');
    glow.addColorStop(1, 'rgba(1,174,255,0)');
    g.fillStyle = glow;
    g.beginPath(); g.arc(cx, cy, R0 * 1.6, 0, Math.PI * 2); g.fill();
    g.strokeStyle = 'rgba(1,174,255,0.9)';
    g.lineWidth = 1;
    g.beginPath(); g.arc(cx, cy, R0 * 0.55, 0, Math.PI * 2); g.stroke();
    g.fillStyle = 'rgba(0,45,75,0.9)';
    g.beginPath(); g.arc(cx, cy, R0 * 0.52, 0, Math.PI * 2); g.fill();
    g.fillStyle = 'rgba(243,241,236,0.9)';
    g.font = `600 ${Math.max(9, R0 * 0.16)}px "Space Mono", monospace`;
    g.textAlign = 'center';
    g.textBaseline = 'middle';
    g.fillText('SUA', cx, cy - R0 * 0.1);
    g.fillText('INFRA', cx, cy + R0 * 0.12);

    // Anéis segmentados
    for (let k = 0; k < RINGS; k++) {
      const v = ringVis(k);
      const r = ringR(k);
      g.strokeStyle = 'rgba(243,241,236,0.07)';
      g.lineWidth = 1;
      g.beginPath(); g.arc(cx, cy, r, 0, Math.PI * 2); g.stroke();
      if (v <= 0.01) continue;
      const segs = 10 + k * 4;
      const rot = time * (k % 2 ? -0.08 : 0.06) * (1 + k * 0.25);
      const fill = 0.62 * v;
      g.strokeStyle = `rgba(1,174,255,${(0.25 + 0.45 * v).toFixed(3)})`;
      g.lineWidth = k === 0 ? 2 : 1.4;
      for (let s = 0; s < segs; s++) {
        const a0 = rot + (s / segs) * Math.PI * 2;
        g.beginPath(); g.arc(cx, cy, r, a0, a0 + (Math.PI * 2 / segs) * fill); g.stroke();
      }
      g.fillStyle = `rgba(1,174,255,${(0.9 * v).toFixed(3)})`;
      g.font = '10px "Space Mono", monospace';
      g.textAlign = 'left';
      const la = -Math.PI / 4;
      g.fillText(`L${k + 1}`, cx + Math.cos(la) * r + 8, cy + Math.sin(la) * r - 6);
    }

    // Impactos nos anéis
    for (let i = flashes.length - 1; i >= 0; i--) {
      const f = flashes[i];
      f.life -= dt * 1.6;
      if (f.life <= 0) { flashes.splice(i, 1); continue; }
      g.strokeStyle = `rgba(1,174,255,${f.life.toFixed(3)})`;
      g.lineWidth = 3;
      g.beginPath(); g.arc(cx, cy, f.r, f.a - 0.18, f.a + 0.18); g.stroke();
    }

    // Ameaças
    const shieldK = outerActive();
    const shieldR = shieldK >= 0 ? ringR(shieldK) : R0 * 0.55;
    for (const p of particles) {
      p.x += p.vx * dt;
      p.y += p.vy * dt;
      p.trail.push(p.x, p.y);
      if (p.trail.length > 16) p.trail.splice(0, 2);
      const dx = p.x - cx, dy = p.y - cy;
      const dist = Math.hypot(dx, dy);
      if (!p.hit && dist <= shieldR) {
        p.hit = true;
        if (shieldK >= 0) {
          const nx = dx / dist, ny = dy / dist;
          const dot = p.vx * nx + p.vy * ny;
          p.vx -= 2 * dot * nx; p.vy -= 2 * dot * ny;
          p.x = cx + nx * (shieldR + 1); p.y = cy + ny * (shieldR + 1);
          flashes.push({ a: Math.atan2(dy, dx), r: shieldR, life: 1 });
        }
      }
      if (p.hit) p.life -= dt * (shieldK >= 0 ? 0.9 : 2.5);
      if (p.life <= 0 || dist > Math.max(W, H) * 1.2) { spawn(p); continue; }
      g.strokeStyle = p.hit ? `rgba(1,174,255,${(0.8 * p.life).toFixed(3)})` : 'rgba(243,241,236,0.55)';
      g.lineWidth = 1.2;
      g.beginPath();
      for (let i = 0; i < p.trail.length; i += 2) (i ? g.lineTo : g.moveTo).call(g, p.trail[i], p.trail[i + 1]);
      g.stroke();
    }
  }

  function loop(now) {
    if (!active) return;
    const dt = Math.min(0.05, (now - last) / 1000 || 0.016);
    last = now;
    draw(now / 1000, dt);
    raf = requestAnimationFrame(loop);
  }

  resize();
  return {
    setProgress(p) { progress = p; },
    setActive(a) {
      if (a === active) return;
      active = a;
      if (a) { resize(); last = performance.now(); raf = requestAnimationFrame(loop); }
      else cancelAnimationFrame(raf);
    },
    resize,
    drawOnce() { resize(); particles.length = 0; draw(0, 0); },
  };
}
