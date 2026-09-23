// Cena 3D do módulo de infraestrutura MultiCloud (Three.js).
// Gêmeo digital do módulo da foto do hero: se abre em vista explodida técnica
// e a câmera atravessa o interior. Todo o estado é função pura do progresso
// de rolagem, então rolar para trás devolve exatamente o quadro anterior.
import * as THREE from 'three';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';
import { RoundedBoxGeometry } from 'three/addons/geometries/RoundedBoxGeometry.js';

const BLUE = 0x01aeff;
const clamp = (v, a = 0, b = 1) => Math.min(b, Math.max(a, v));
const lerp = (a, b, t) => a + (b - a) * t;
const smooth = (a, b, v) => { const t = clamp((v - a) / (b - a)); return t * t * (3 - 2 * t); };
const inOut = (t) => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2);
const V = (x, y, z) => new THREE.Vector3(x, y, z);

// ---------------------------------------------------------------------------
// Texturas procedurais (sem downloads)
// ---------------------------------------------------------------------------
function canvasTex(size, draw, { srgb = true } = {}) {
  const c = document.createElement('canvas');
  c.width = c.height = size;
  draw(c.getContext('2d'), size);
  const t = new THREE.CanvasTexture(c);
  t.wrapS = t.wrapT = THREE.RepeatWrapping;
  if (srgb) t.colorSpace = THREE.SRGBColorSpace;
  return t;
}

function brushed(g, s, base) {
  g.fillStyle = base;
  g.fillRect(0, 0, s, s);
  for (let i = 0; i < s; i++) {
    g.fillStyle = `rgba(255,255,255,${Math.random() * 0.035})`;
    g.fillRect(0, i, s, 1);
    g.fillStyle = `rgba(0,0,0,${Math.random() * 0.05})`;
    g.fillRect(0, i, s, 1);
  }
}

function makeTextures(maxAniso) {
  const metal = canvasTex(256, (g, s) => brushed(g, s, '#2c3137'));
  // Perfuração real: branco = chapa, preto = furo (usado com alphaTest)
  const holes = canvasTex(128, (g, s) => {
    g.fillStyle = '#fff';
    g.fillRect(0, 0, s, s);
    g.fillStyle = '#000';
    const step = 16;
    for (let y = step / 2; y < s; y += step)
      for (let x = step / 2; x < s; x += step) {
        g.beginPath();
        g.arc(x, y, 4.6, 0, Math.PI * 2);
        g.fill();
      }
  }, { srgb: false });

  // Placa-mãe: trilhas ortogonais + versão emissiva para o brilho das trilhas
  const traces = [];
  for (let i = 0; i < 150; i++) {
    const pts = [];
    let x = Math.floor(Math.random() * 64) * 16;
    let y = Math.floor(Math.random() * 64) * 16;
    pts.push([x, y]);
    for (let k = 0; k < 2 + Math.floor(Math.random() * 3); k++) {
      if (k % 2 === 0) x = clamp(x + (Math.random() - 0.5) * 520, 0, 1024);
      else y = clamp(y + (Math.random() - 0.5) * 520, 0, 1024);
      pts.push([Math.round(x / 16) * 16, Math.round(y / 16) * 16]);
    }
    traces.push(pts);
  }
  const drawTraces = (g, color, width) => {
    g.strokeStyle = color;
    g.lineWidth = width;
    g.lineJoin = 'miter';
    for (const pts of traces) {
      g.beginPath();
      pts.forEach(([x, y], i) => (i ? g.lineTo(x, y) : g.moveTo(x, y)));
      g.stroke();
    }
  };
  const board = canvasTex(1024, (g, s) => {
    g.fillStyle = '#0a1015';
    g.fillRect(0, 0, s, s);
    drawTraces(g, 'rgba(70,120,150,.28)', 3);
    g.fillStyle = 'rgba(160,180,190,.18)';
    for (let i = 0; i < 260; i++) g.fillRect(Math.random() * s, Math.random() * s, 6, 6);
    g.strokeStyle = 'rgba(200,210,220,.12)';
    g.lineWidth = 2;
    for (let i = 0; i < 14; i++) g.strokeRect(Math.random() * 900, Math.random() * 900, 40 + Math.random() * 90, 40 + Math.random() * 90);
  });
  const boardGlow = canvasTex(1024, (g, s) => {
    g.fillStyle = '#000';
    g.fillRect(0, 0, s, s);
    drawTraces(g, '#fff', 2);
  }, { srgb: false });

  const grid = canvasTex(512, (g, s) => {
    g.clearRect(0, 0, s, s);
    g.strokeStyle = 'rgba(1,174,255,.5)';
    g.lineWidth = 6;
    g.strokeRect(0, 0, s, s);
    g.strokeStyle = 'rgba(1,174,255,.2)';
    g.lineWidth = 3;
    for (let i = 1; i < 4; i++) {
      g.beginPath(); g.moveTo((s / 4) * i, 0); g.lineTo((s / 4) * i, s); g.stroke();
      g.beginPath(); g.moveTo(0, (s / 4) * i); g.lineTo(s, (s / 4) * i); g.stroke();
    }
  });
  const radial = canvasTex(256, (g, s) => {
    const r = g.createRadialGradient(s / 2, s / 2, 0, s / 2, s / 2, s / 2);
    r.addColorStop(0, 'rgba(255,255,255,1)');
    r.addColorStop(0.55, 'rgba(255,255,255,.35)');
    r.addColorStop(1, 'rgba(255,255,255,0)');
    g.fillStyle = r;
    g.fillRect(0, 0, s, s);
  }, { srgb: false });

  for (const t of [metal, board, boardGlow, holes, grid]) t.anisotropy = maxAniso;
  return { metal, holes, board, boardGlow, grid, radial };
}

// ---------------------------------------------------------------------------
export function createInfraScene(canvas, env) {
  const isMobile = env.mobile;
  const renderer = new THREE.WebGLRenderer({ canvas, antialias: !isMobile, powerPreference: 'high-performance', alpha: false });
  renderer.setClearColor(0x000000, 1);
  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, isMobile ? 1.5 : 1.75));
  renderer.outputColorSpace = THREE.SRGBColorSpace;
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.toneMappingExposure = 1.05;

  const scene = new THREE.Scene();
  scene.fog = new THREE.FogExp2(0x000000, 0.028);
  const pmrem = new THREE.PMREMGenerator(renderer);
  scene.environment = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;
  scene.environmentIntensity = 0.85;

  const camera = new THREE.PerspectiveCamera(26, 16 / 9, 0.03, 120);
  const tex = makeTextures(renderer.capabilities.getMaxAnisotropy());

  // ---------------- Materiais ----------------
  const mat = {
    metal: new THREE.MeshStandardMaterial({ color: 0xc9d0d8, map: tex.metal, metalness: 0.78, roughness: 0.36 }),
    dark: new THREE.MeshStandardMaterial({ color: 0x9aa2ab, map: tex.metal, metalness: 0.74, roughness: 0.42 }),
    inner: new THREE.MeshStandardMaterial({ color: 0x2a3037, metalness: 0.5, roughness: 0.7 }),
    fin: new THREE.MeshStandardMaterial({ color: 0x8e98a3, metalness: 0.95, roughness: 0.28 }),
    glass: new THREE.MeshStandardMaterial({ color: 0x070b0f, metalness: 0.95, roughness: 0.06, transparent: true, opacity: 0.88 }),
    led: new THREE.MeshBasicMaterial({ color: new THREE.Color(BLUE).multiplyScalar(3.2), toneMapped: false }),
    ledSoft: new THREE.MeshBasicMaterial({ color: new THREE.Color(BLUE).multiplyScalar(1.6), toneMapped: false }),
    dimm: new THREE.MeshStandardMaterial({ color: 0x14202a, metalness: 0.4, roughness: 0.55 }),
    board: new THREE.MeshStandardMaterial({ map: tex.board, emissiveMap: tex.boardGlow, emissive: new THREE.Color(BLUE), emissiveIntensity: 0.12, metalness: 0.35, roughness: 0.65 }),
  };
  const perfCache = new Map();
  function perforated(w, h, base = mat.dark) {
    const key = `${w.toFixed(2)}x${h.toFixed(2)}`;
    if (perfCache.has(key)) return perfCache.get(key);
    const holes = tex.holes.clone();
    holes.repeat.set(w * 7.5, h * 7.5);
    holes.needsUpdate = true;
    const m = base.clone();
    m.alphaMap = holes;
    m.alphaTest = 0.5;
    m.side = THREE.DoubleSide;
    perfCache.set(key, m);
    return m;
  }

  // ---------------- Geometria ----------------
  const rbox = (w, h, d, r = 0.018) => new RoundedBoxGeometry(w, h, d, 2, Math.min(r, w / 2.1, h / 2.1, d / 2.1));
  const mesh = (geo, m, x = 0, y = 0, z = 0) => { const o = new THREE.Mesh(geo, m); o.position.set(x, y, z); return o; };

  const module = new THREE.Group();
  scene.add(module);
  const parts = [];
  const byName = {};
  function part(name, obj, off, s0, s1, guide = true) {
    const p = { name, obj, base: obj.position.clone(), off: V(...off), s0, s1, guide };
    parts.push(p);
    byName[name] = p;
    module.add(obj);
    return p;
  }

  const W = 4.4, H = 2.3, D = 3.2, T = 0.07;

  // Carcaça
  part('cover', mesh(rbox(W, T, D, 0.03), mat.metal, 0, H / 2 - T / 2, 0), [0, 1.5, 0], 0.0, 0.5);
  part('bottom', mesh(rbox(W, T, D, 0.03), mat.dark, 0, -H / 2 + T / 2, 0), [0, -0.9, 0], 0.05, 0.55);
  part('left', mesh(new THREE.BoxGeometry(T, H - 2 * T, D), perforated(D, H, mat.metal), -W / 2 + T / 2, 0, 0), [-1.25, 0, 0], 0.02, 0.52);
  part('right', mesh(new THREE.BoxGeometry(T, H - 2 * T, D), perforated(D, H, mat.metal), W / 2 - T / 2, 0, 0), [1.3, 0, 0], 0.06, 0.56);
  part('rear', mesh(new THREE.BoxGeometry(W - 2 * T, H - 2 * T, T), perforated(W, H, mat.dark), 0, 0, -D / 2 + T / 2), [0, 0, -1.95], 0.08, 0.6);

  // Coluna frontal com LED (como na foto)
  const zf = D / 2;
  const bezel = new THREE.Group();
  bezel.position.set(-2.03, 0, zf - 0.04);
  bezel.add(mesh(rbox(0.2, H - 2 * T, 0.1, 0.02), mat.dark));
  bezel.add(mesh(new THREE.BoxGeometry(0.014, 1.86, 0.012), mat.led, 0.05, 0, 0.056));
  part('led', bezel, [0, 0, 1.3], 0.1, 0.58);

  // Baias (8 gavetas perfuradas, alinhadas)
  const heights = [0.36, 0.36, 0.2, 0.2, 0.2, 0.2, 0.2, 0.2];
  const gap = 0.03;
  const sledW = 2.92, sledX = -0.44, sledDepth = 0.86;
  let top = 1.065;
  const sleds = [];
  heights.forEach((h, i) => {
    const y = top - h / 2;
    top -= h + gap;
    const g = new THREE.Group();
    g.position.set(sledX, y, zf - 0.03);
    g.add(mesh(rbox(sledW, h, 0.045, 0.012), perforated(sledW, h, mat.metal)));
    g.add(mesh(new THREE.BoxGeometry(sledW - 0.06, h - 0.03, sledDepth), mat.inner, 0, 0, -0.0225 - sledDepth / 2));
    g.add(mesh(new THREE.BoxGeometry(0.05, 0.012, 0.01), i % 3 === 0 ? mat.led : mat.ledSoft, sledW / 2 - 0.1, 0, 0.03));
    // LEDs de status na coluna, alinhados a cada baia
    bezel.add(mesh(new THREE.BoxGeometry(0.04, 0.012, 0.01), mat.ledSoft, -0.04, y, 0.056));
    const p = part(`sled${i}`, g, [0, (i - 3.5) * 0.11, 0.95 + i * 0.15], 0.12 + i * 0.025, 0.6 + i * 0.02);
    p.h = h;
    sleds.push(p);
  });

  // Painel de vidro fumê + controladoras por trás
  part('glass', mesh(rbox(1.04, H - 2 * T - 0.02, 0.04, 0.012), mat.glass, 1.59, 0, zf - 0.02), [0.95, 0, 1.9], 0.15, 0.62);
  const ctrl = new THREE.Group();
  ctrl.position.set(1.59, 0.55, zf - 0.45);
  ctrl.add(mesh(rbox(0.94, 0.4, 0.7, 0.02), mat.dark, 0, 0.22, 0));
  ctrl.add(mesh(rbox(0.94, 0.4, 0.7, 0.02), mat.dark, 0, -0.24, 0));
  ctrl.add(mesh(new THREE.BoxGeometry(0.5, 0.01, 0.01), mat.led, 0, 0.22, 0.36));
  part('ctrl', ctrl, [0.95, 0.25, 0.95], 0.2, 0.66);

  // Placa-mãe + pulsos de dados
  const boardG = new THREE.Group();
  boardG.position.set(0, -1.05, -0.35);
  boardG.add(mesh(new THREE.BoxGeometry(4.1, 0.035, 2.35), mat.board));
  part('board', boardG, [0, -0.25, 0], 0.2, 0.65);

  // Processadores: dissipadores com aletas
  const finGeo = new THREE.BoxGeometry(0.012, 0.44, 0.64);
  const cpuX = [-1.0, 1.0];
  cpuX.forEach((cx, k) => {
    const hs = new THREE.Group();
    hs.position.set(cx, -0.99, -0.45);
    hs.add(mesh(rbox(0.66, 0.06, 0.66, 0.01), mat.fin, 0, 0.03, 0));
    const fins = new THREE.InstancedMesh(finGeo, mat.fin, 22);
    const m4 = new THREE.Matrix4();
    for (let i = 0; i < 22; i++) { m4.makeTranslation(-0.3 + (i / 21) * 0.6, 0.28, 0); fins.setMatrixAt(i, m4); }
    hs.add(fins);
    part(`heatsink${k}`, hs, [0, 0.85, 0], 0.26, 0.72);
  });

  // Memórias (4 de cada lado de cada processador) com filete luminoso
  const dimmGroup = new THREE.Group();
  const dimms = new THREE.InstancedMesh(new THREE.BoxGeometry(0.022, 0.3, 1.0), mat.dimm, 16);
  const dimmTops = new THREE.InstancedMesh(new THREE.BoxGeometry(0.024, 0.008, 0.86), mat.ledSoft, 16);
  let di = 0;
  const m4 = new THREE.Matrix4();
  for (const cx of cpuX)
    for (const side of [-1, 1])
      for (let j = 0; j < 4; j++) {
        const x = cx + side * (0.42 + j * 0.07);
        m4.makeTranslation(x, -0.8825, -0.45); dimms.setMatrixAt(di, m4);
        m4.makeTranslation(x, -0.73, -0.45); dimmTops.setMatrixAt(di, m4);
        di++;
      }
  dimmGroup.add(dimms, dimmTops);
  part('dimms', dimmGroup, [0, 0.5, 0], 0.24, 0.7, false);
  byName.dimms.anchorLocal = V(-1.42, -0.73, -0.45);

  // Parede de ventoinhas
  const fanWall = new THREE.Group();
  fanWall.position.set(0, -0.74, 0.5);
  const frameShape = new THREE.Shape();
  frameShape.moveTo(-0.27, -0.27); frameShape.lineTo(0.27, -0.27); frameShape.lineTo(0.27, 0.27); frameShape.lineTo(-0.27, 0.27); frameShape.lineTo(-0.27, -0.27);
  const hole = new THREE.Path(); hole.absarc(0, 0, 0.235, 0, Math.PI * 2, true);
  frameShape.holes.push(hole);
  const frameGeo = new THREE.ExtrudeGeometry(frameShape, { depth: 0.2, bevelEnabled: true, bevelSize: 0.008, bevelThickness: 0.008, bevelSegments: 1, curveSegments: 28 });
  frameGeo.translate(0, 0, -0.1);
  const bladeGeo = new THREE.BoxGeometry(0.19, 0.075, 0.008);
  bladeGeo.translate(0.12, 0, 0);
  const hubGeo = new THREE.CylinderGeometry(0.07, 0.07, 0.12, 24);
  hubGeo.rotateX(Math.PI / 2);
  const rotors = [];
  for (let i = 0; i < 7; i++) {
    const x = -1.68 + i * 0.56;
    fanWall.add(mesh(frameGeo, mat.dark, x, 0, 0));
    const rotor = new THREE.Group();
    rotor.position.set(x, 0, 0);
    const blades = new THREE.InstancedMesh(bladeGeo, mat.inner, 7);
    const q = new THREE.Quaternion(), e = new THREE.Euler(), s = V(1, 1, 1), p0 = V(0, 0, 0);
    for (let b = 0; b < 7; b++) { e.set(0.35, 0, (b / 7) * Math.PI * 2); q.setFromEuler(e); m4.compose(p0, q, s); blades.setMatrixAt(b, m4); }
    rotor.add(blades, mesh(hubGeo, mat.dark));
    rotor.add(mesh(new THREE.TorusGeometry(0.03, 0.006, 6, 24), mat.ledSoft, 0, 0, 0.062));
    fanWall.add(rotor);
    rotors.push(rotor);
  }
  part('fans', fanWall, [0, 1.35, 0.15], 0.28, 0.76);

  // Fontes redundantes e placa de rede
  const psu = new THREE.Group();
  psu.position.set(1.55, -0.6, -0.95);
  psu.add(mesh(rbox(0.9, 0.36, 1.1, 0.02), mat.dark, 0, -0.2, 0));
  psu.add(mesh(rbox(0.9, 0.36, 1.1, 0.02), mat.dark, 0, 0.2, 0));
  psu.add(mesh(new THREE.BoxGeometry(0.02, 0.02, 0.02), mat.led, 0.3, -0.2, -0.56));
  psu.add(mesh(new THREE.BoxGeometry(0.02, 0.02, 0.02), mat.led, 0.3, 0.2, -0.56));
  part('psu', psu, [0.3, 1.95, -0.7], 0.22, 0.68);

  const nic = new THREE.Group();
  nic.position.set(-1.72, -0.66, -1.0);
  nic.add(mesh(new THREE.BoxGeometry(0.03, 0.62, 1.0), mat.board));
  nic.add(mesh(new THREE.BoxGeometry(0.04, 0.5, 0.012), mat.ledSoft, 0, 0, -0.5));
  part('nic', nic, [-0.65, 0.45, -0.45], 0.22, 0.7);

  // Pulsos de dados sobre a placa (coordenadas locais da placa)
  const pulsePaths = [
    [[-1.0, -0.1], [-1.0, 0.6], [-0.2, 0.6], [-0.2, 1.1]],
    [[1.0, -0.1], [1.0, 0.55], [0.25, 0.55], [0.25, 1.1]],
    [[-1.6, -0.1], [-1.0, -0.1]],
    [[1.6, -0.1], [1.0, -0.1]],
    [[-1.0, -0.1], [0.0, -0.1], [0.0, -1.0]],
    [[1.0, -0.1], [0.0, -0.1]],
    [[-1.7, -0.6], [-1.0, -0.6], [-1.0, -0.1]],
    [[0.0, -1.0], [1.5, -1.0], [1.5, -0.4]],
  ].map((pts) => {
    const v = pts.map(([x, z]) => V(x, 0.03, z));
    const lens = [];
    let total = 0;
    for (let i = 1; i < v.length; i++) { const l = v[i].distanceTo(v[i - 1]); lens.push(l); total += l; }
    return { v, lens, total };
  });
  const PULSES = isMobile ? 18 : 30;
  const pulses = new THREE.InstancedMesh(new THREE.BoxGeometry(0.09, 0.012, 0.012), mat.led, PULSES);
  const pulseState = Array.from({ length: PULSES }, (_, i) => ({ path: i % pulsePaths.length, t: Math.random(), speed: 0.12 + Math.random() * 0.18 }));
  boardG.add(pulses);

  // Referências técnicas: contorno do volume montado + guias tracejadas
  const lineMat = new THREE.LineBasicMaterial({ color: BLUE, transparent: true, opacity: 0, depthWrite: false });
  const outline = new THREE.LineSegments(new THREE.EdgesGeometry(new THREE.BoxGeometry(W, H, D)), lineMat);
  module.add(outline);
  const guided = parts.filter((p) => p.guide);
  const guideGeo = new THREE.BufferGeometry();
  guideGeo.setAttribute('position', new THREE.BufferAttribute(new Float32Array(guided.length * 6), 3));
  const guideMat = new THREE.LineDashedMaterial({ color: BLUE, dashSize: 0.06, gapSize: 0.05, transparent: true, opacity: 0, depthWrite: false });
  const guides = new THREE.LineSegments(guideGeo, guideMat);
  guides.frustumCulled = false;
  module.add(guides);

  // Piso técnico, sombra de contato e luz ao fundo
  const floorY = -2.9;
  tex.grid.repeat.set(20, 20);
  const floor = new THREE.Mesh(new THREE.PlaneGeometry(80, 80), new THREE.MeshBasicMaterial({ map: tex.grid, alphaMap: tex.radial, transparent: true, opacity: 0.16, depthWrite: false }));
  floor.rotation.x = -Math.PI / 2;
  floor.position.y = floorY;
  scene.add(floor);
  const shadow = new THREE.Mesh(new THREE.PlaneGeometry(8, 6), new THREE.MeshBasicMaterial({ color: 0x000000, alphaMap: tex.radial, transparent: true, opacity: 0.85, depthWrite: false }));
  shadow.rotation.x = -Math.PI / 2;
  shadow.position.y = floorY + 0.01;
  scene.add(shadow);
  // Luz atrás da chapa traseira: aparece apenas através das perfurações
  const portal = new THREE.Mesh(new THREE.PlaneGeometry(4.4, 2.3), new THREE.MeshBasicMaterial({ color: new THREE.Color(BLUE).multiplyScalar(0.9), alphaMap: tex.radial, transparent: true, opacity: 0, depthWrite: false, toneMapped: false }));
  portal.position.set(0, 0, -4.1);
  scene.add(portal);

  // Luzes
  scene.add(new THREE.HemisphereLight(0xb9c8d4, 0x050608, 0.55));
  const key = new THREE.DirectionalLight(0xffffff, 2.8);
  key.position.set(-6, 7, 7);
  scene.add(key);
  const fill = new THREE.DirectionalLight(0xdfe8f0, 0.9);
  fill.position.set(4, 1.5, 9);
  scene.add(fill);
  // Recorte discreto: mais forte que isso acende as bordas das gavetas como neon
  const rim = new THREE.DirectionalLight(0x6fcaff, 0.25);
  rim.position.set(6, 5, -8);
  scene.add(rim);
  const inner = new THREE.PointLight(BLUE, 0, 7, 1.6);
  inner.position.set(0, -0.45, -0.5);
  scene.add(inner);
  const back = new THREE.PointLight(BLUE, 0, 9, 1.4);
  back.position.set(0, -0.3, -4.2);
  scene.add(back);

  // ---------------- Pós-processamento (bloom só no desktop) ----------------
  let composer = null, bloom = null;
  const composerReady = isMobile ? Promise.resolve() : Promise.all([
    import('three/addons/postprocessing/EffectComposer.js'),
    import('three/addons/postprocessing/RenderPass.js'),
    import('three/addons/postprocessing/UnrealBloomPass.js'),
    import('three/addons/postprocessing/OutputPass.js'),
  ]).then(([{ EffectComposer }, { RenderPass }, { UnrealBloomPass }, { OutputPass }]) => {
    composer = new EffectComposer(renderer);
    composer.addPass(new RenderPass(scene, camera));
    bloom = new UnrealBloomPass(new THREE.Vector2(256, 256), 0.55, 0.5, 0.92);
    composer.addPass(bloom);
    composer.addPass(new OutputPass());
    resize();
  }).catch(() => { composer = null; });

  // ---------------- Câmera ----------------
  const pose = (pos, target, fov) => ({ pos: V(...pos), target: V(...target), fov });
  const P = {
    intro: pose([-7.2, 0.55, 7.7], [0.3, -0.05, 0], 25),
    scan: pose([-6.5, 1.05, 7.9], [0.25, 0.0, 0], 26),
    wide: pose([8.6, 5.6, 11.4], [0.15, 0.3, 0.35], 31),
    hold: pose([7.6, 4.8, 10.4], [0.1, 0.25, 0.5], 31),
  };

  // Mergulho: a câmera entra sob a tampa erguida, sobrevoa ventoinhas, dissipadores
  // e a placa, e desce até a chapa traseira iluminada por trás.
  const divePos = new THREE.CatmullRomCurve3([
    P.hold.pos.clone(),
    V(4.6, 3.5, 8.6),
    V(1.3, 2.0, 5.2),
    V(0.0, 1.6, 2.7),
    V(0.0, 1.1, 0.9),
    V(0.0, 0.62, -0.45),
    V(0.0, 0.2, -1.45),
  ], false, 'centripetal', 0.4);
  const diveTarget = new THREE.CatmullRomCurve3([
    P.hold.target.clone(),
    V(0.5, 0.6, 1.5),
    V(0.0, 0.35, 0.0),
    V(0.0, 0.0, -1.3),
    V(0.0, -0.2, -2.5),
    V(0.0, -0.05, -3.6),
    V(0.0, 0.0, -5.0),
  ], false, 'centripetal', 0.4);

  const cam = { pos: V(), target: V(), fov: 26 };
  const sph = new THREE.Spherical(), sphA = new THREE.Spherical(), sphB = new THREE.Spherical();
  const tmp = V(), tmp2 = V();
  function orbit(a, b, t) {
    cam.target.lerpVectors(a.target, b.target, t);
    sphA.setFromVector3(tmp.copy(a.pos).sub(a.target));
    sphB.setFromVector3(tmp2.copy(b.pos).sub(b.target));
    let dTheta = sphB.theta - sphA.theta;
    if (dTheta > Math.PI) dTheta -= Math.PI * 2;
    if (dTheta < -Math.PI) dTheta += Math.PI * 2;
    sph.set(lerp(sphA.radius, sphB.radius, t), lerp(sphA.phi, sphB.phi, t), sphA.theta + dTheta * t);
    cam.pos.setFromSpherical(sph).add(cam.target);
    cam.fov = lerp(a.fov, b.fov, t);
  }

  // ---------------- Estado ----------------
  const state = { explode: 0, interior: 0, fanSpeed: 1, glow: 0.12, portal: 0, float: 1, exterior: 1, shift: 0, outro: false };
  let size = { w: 1, h: 1 };

  function applyExplode(e) {
    for (const p of parts) {
      const t = inOut(clamp((e - p.s0) / (p.s1 - p.s0)));
      p.obj.position.copy(p.base).addScaledVector(p.off, t);
    }
    const arr = guideGeo.attributes.position.array;
    guided.forEach((p, i) => {
      arr.set([p.base.x, p.base.y, p.base.z, p.obj.position.x, p.obj.position.y, p.obj.position.z], i * 6);
    });
    guideGeo.attributes.position.needsUpdate = true;
    guides.computeLineDistances();
    guideMat.opacity = smooth(0.08, 0.5, e) * 0.55;
    lineMat.opacity = smooth(0.2, 0.7, e) * 0.28;
  }

  /** Progresso do hero (0–1). Fases alinhadas com hero.js */
  function setHero(p) {
    state.outro = false;
    state.explode = smooth(0.18, 0.5, p);
    state.float = 1 - smooth(0.15, 0.3, p);
    if (p < 0.2) {
      const t = smooth(0, 0.2, p);
      cam.pos.lerpVectors(P.intro.pos, P.scan.pos, t);
      cam.target.lerpVectors(P.intro.target, P.scan.target, t);
      cam.fov = lerp(P.intro.fov, P.scan.fov, t);
    } else if (p < 0.5) {
      orbit(P.scan, P.wide, inOut((p - 0.2) / 0.3));
    } else if (p < 0.58) {
      const t = smooth(0.5, 0.58, p);
      cam.pos.lerpVectors(P.wide.pos, P.hold.pos, t);
      cam.target.lerpVectors(P.wide.target, P.hold.target, t);
      cam.fov = P.wide.fov;
    } else {
      const t = inOut(clamp((p - 0.58) / 0.38));
      divePos.getPointAt(t, cam.pos);
      diveTarget.getPointAt(t, cam.target);
      cam.fov = lerp(31, 58, smooth(0.0, 0.55, t));
    }
    state.exterior = 1 - smooth(0.62, 0.76, p);
    // Desktop: o módulo desliza para a direita durante a vista explodida (texto à esquerda)
    // e volta ao centro quando a câmera mergulha.
    state.shift = size.w > 1024 ? lerp(0.045, 0.13, smooth(0.18, 0.42, p)) * (1 - smooth(0.55, 0.66, p)) : 0;
    state.interior = smooth(0.45, 0.8, p);
    state.glow = 0.12 + smooth(0.55, 0.85, p) * 1.1;
    state.fanSpeed = 1 + smooth(0.55, 0.9, p) * 7;
    state.portal = smooth(0.78, 0.94, p);
    applyExplode(state.explode);
  }

  /** Remontagem ao final da página (contato). q: 0–1 */
  function setOutro(q) {
    state.outro = true;
    state.explode = 0.42 * (1 - smooth(0.05, 0.6, q));
    state.float = 1;
    state.exterior = 1;
    state.interior = 0.15;
    state.glow = 0.2;
    state.fanSpeed = 1.5;
    state.portal = 0;
    const theta = lerp(-1.25, 0.55, inOut(clamp(q)));
    const r = isMobile ? 15.5 : 12.8;
    cam.target.set(0, -0.1, 0);
    cam.pos.set(Math.sin(theta) * r, lerp(3.6, 1.6, q), Math.cos(theta) * r);
    cam.fov = 26;
    applyExplode(state.explode);
  }

  function resize() {
    const w = window.innerWidth, h = window.innerHeight;
    size = { w, h };
    renderer.setSize(w, h, false);
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
    if (composer) {
      composer.setPixelRatio(renderer.getPixelRatio());
      composer.setSize(w, h);
    }
  }

  const q4 = new THREE.Quaternion(), s1 = V(1, 1, 1), pos = V(), dir = V(), rot = new THREE.Euler();
  let last = performance.now();
  function render(now = performance.now()) {
    const dt = Math.min(0.05, (now - last) / 1000);
    last = now;
    const time = now / 1000;

    // Câmera (com leve respiração) — narrativa manda, respiração só adiciona vida
    camera.position.copy(cam.pos);
    camera.position.y += Math.sin(time * 0.6) * 0.03 * state.float;
    camera.position.x += Math.sin(time * 0.37) * 0.04 * state.float;
    camera.fov = cam.fov;
    // Mantém o módulo inteiro no quadro em telas estreitas (retrato);
    // o ajuste some quando a câmera já está dentro da estrutura.
    const aspect = size.w / size.h;
    if (aspect < 1.2) {
      const k = lerp(1, 1.95, clamp((1.2 - aspect) / 0.75));
      camera.fov = Math.min(80, cam.fov * lerp(1, k, state.exterior));
    }
    camera.lookAt(cam.target);
    // Desloca o enquadramento sem mover a câmera (alinha com a foto / abre espaço para o texto)
    // No contato em telas estreitas o módulo sobe para a metade de cima (título embaixo)
    const shiftX = state.outro ? (size.w > 900 ? 0.25 : 0) : state.shift;
    const shiftY = state.outro && size.w <= 900 ? 0.2 : 0;
    if (shiftX || shiftY) camera.setViewOffset(size.w, size.h, -size.w * shiftX, size.h * shiftY, size.w, size.h);
    else camera.clearViewOffset();
    camera.updateProjectionMatrix();

    module.position.y = Math.sin(time * 0.8) * 0.035 * state.float;
    shadow.material.opacity = 0.85 * (1 - state.explode * 0.5);

    for (const r of rotors) r.rotation.z -= dt * state.fanSpeed * 2.4;
    mat.board.emissiveIntensity = state.glow;
    inner.intensity = state.interior * 9;
    back.intensity = state.portal * 14;
    portal.material.opacity = state.portal * 0.9;

    // Pulsos correm pelas trilhas
    pulseState.forEach((s, i) => {
      const path = pulsePaths[s.path];
      s.t = (s.t + (dt * s.speed * (0.6 + state.fanSpeed * 0.35)) / path.total * 2.2) % 1;
      let d = s.t * path.total, k = 0;
      while (k < path.lens.length - 1 && d > path.lens[k]) { d -= path.lens[k]; k++; }
      const a = path.v[k], b = path.v[k + 1];
      pos.lerpVectors(a, b, clamp(d / path.lens[k]));
      dir.subVectors(b, a);
      rot.set(0, Math.atan2(-dir.z, dir.x), 0);
      q4.setFromEuler(rot);
      m4.compose(pos, q4, s1);
      pulses.setMatrixAt(i, m4);
    });
    pulses.instanceMatrix.needsUpdate = true;

    if (composer) composer.render();
    else renderer.render(scene, camera);
  }

  // Pontos de ancoragem das legendas (coordenadas locais de cada peça)
  const anchorDefs = {
    cover: ['cover', V(0.2, 0.05, 0.6)],
    led: ['led', V(0.05, 0.62, 0.06)],
    bays: ['sled7', V(1.2, 0, 0.03)],
    board: ['heatsink1', V(0, 0.52, 0)],
    psu: ['psu', V(0.3, 0.4, 0.3)],
    fans: ['fans', V(-1.68, 0.28, 0.1)],
  };
  const pv = V();
  function getAnchors() {
    const out = {};
    for (const [k, [name, local]] of Object.entries(anchorDefs)) {
      const o = byName[name].obj;
      o.updateWorldMatrix(true, false);
      pv.copy(local).applyMatrix4(o.matrixWorld).project(camera);
      out[k] = { x: (pv.x * 0.5 + 0.5) * size.w, y: (-pv.y * 0.5 + 0.5) * size.h, on: pv.z < 1 && Math.abs(pv.x) < 1.2 && Math.abs(pv.y) < 1.2 };
    }
    return out;
  }

  resize();
  setHero(0);

  return {
    kind: 'webgl',
    ready: composerReady,
    setHero,
    setOutro,
    render,
    resize,
    getAnchors,
    renderer,
    debug: env.debug ? { scene, mat, key, rim, fill, get bloom() { return bloom; } } : null,
  };
}
