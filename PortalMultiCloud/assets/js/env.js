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

export const env = {
  // Diagnóstico: ?nosmooth desliga a rolagem suave; ?debug expõe o estado em window.__mc
  smooth: !params.has('nosmooth'),
  debug: params.has('debug'),
  reducedMotion: mq('(prefers-reduced-motion: reduce)'),
  mobile: mq('(max-width: 720px)'),
  touch: mq('(hover: none), (pointer: coarse)'),
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
