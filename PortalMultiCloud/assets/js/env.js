// Capacidades do dispositivo e preferências do visitante.
const mq = (q) => window.matchMedia(q).matches;
const params = new URLSearchParams(location.search);

function hasWebGL() {
  try {
    const c = document.createElement('canvas');
    const gl = c.getContext('webgl2') || c.getContext('webgl');
    if (!gl) return false;
    gl.getExtension('WEBGL_lose_context')?.loseContext();
    return true;
  } catch {
    return false;
  }
}

const conn = navigator.connection || {};
const mobile = mq('(max-width: 720px)');
const touch = mq('(hover: none), (pointer: coarse)');

// Endereço do app.min.js em execução: a cena 3D (scene.min.js) é buscada na mesma pasta,
// qualquer que seja o servidor que entrega os arquivos.
const self = document.currentScript?.src || '';
export const sceneUrl = /app\.min\.js(?=$|[?#])/.test(self)
  ? self.replace(/app\.min\.js(?=$|[?#])/, 'scene.min.js')
  : 'assets/js/scene.min.js';

export const env = {
  // Diagnóstico: ?nosmooth desliga a rolagem suave; ?debug expõe o estado em window.__mc
  smooth: !params.has('nosmooth'),
  debug: params.has('debug'),
  reducedMotion: mq('(prefers-reduced-motion: reduce)'),
  mobile,
  touch,
  // Tablets: tela grande com GPU de celular. Recebem a cena 3D sem bloom e com menos pixels.
  tablet: touch && !mobile,
  saveData: conn.saveData === true,
  finePointer: mq('(hover: hover) and (pointer: fine)'),
  webgl: hasWebGL(),
  // Dispositivos modestos ou economia de dados recebem a versão leve (imagem + narrativa).
  lowPower:
    params.has('lite') ||
    conn.saveData === true ||
    (navigator.hardwareConcurrency || 4) <= 2 ||
    (navigator.deviceMemory !== undefined && navigator.deviceMemory < 2),
};

export const clamp = (v, a = 0, b = 1) => Math.min(b, Math.max(a, v));
export const lerp = (a, b, t) => a + (b - a) * t;
export const smooth = (a, b, v) => {
  const t = clamp((v - a) / (b - a));
  return t * t * (3 - 2 * t);
};
export const $ = (s, r = document) => r.querySelector(s);
export const $$ = (s, r = document) => [...r.querySelectorAll(s)];
