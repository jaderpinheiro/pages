// Abertura: o símbolo MultiCloud se revela, o contador acompanha o carregamento real
// e a marca "voa" até a posição do logo na navegação enquanto as persianas se abrem.
import { env, $ } from './env.js';

const { gsap } = window;

// Posição de cada parte dentro do logo completo (frações de largura/altura),
// medidas por tools/optimize-images.mjs a partir de MultiCloud.png.
const LOGO_PARTS = {
  symbol: { x: 0.0093, y: 0.0811, w: 0.1953 },
  word: { x: 0.2483, y: 0.0856, w: 0.7391 },
};

export function createLoader() {
  const el = $('#loader');
  const navLogo = $('#navLogo');
  if (!el || env.reducedMotion) {
    el?.remove();
    return { progress() {}, finish: async () => {} };
  }

  const count = $('#loaderCount');
  const bar = $('#loaderBar');
  const word = $('.loader__word', el);
  const shown = { v: 0 };
  let target = 0;
  gsap.set(navLogo, { autoAlpha: 0 });

  const intro = gsap.timeline()
    .to('.loader__ring circle', { strokeDashoffset: 0, duration: 1.9, ease: 'power2.inOut' }, 0)
    .to('.loader__symbol', { maskPosition: '0% 0', webkitMaskPosition: '0% 0', duration: 1.3, ease: 'power2.inOut' }, 0.15)
    .to(word, { clipPath: 'inset(0 0% 0 0)', duration: 1.1, ease: 'expo.inOut' }, 0.6)
    .to('.loader__tag', { autoAlpha: 1, duration: 0.8 }, 1.1);

  const render = () => {
    const v = Math.round(shown.v);
    count.textContent = String(v).padStart(3, '0');
    bar.style.transform = `scaleX(${shown.v / 100})`;
  };

  return {
    progress(p) {
      target = Math.max(target, Math.min(100, p * 100));
      gsap.to(shown, { v: target, duration: 0.9, ease: 'power2.out', onUpdate: render, overwrite: true });
    },
    async finish(onOpen) {
      target = 100;
      await Promise.all([
        intro.then(),
        new Promise((r) => gsap.to(shown, { v: 100, duration: 0.7, ease: 'power2.out', onUpdate: render, onComplete: r, overwrite: true })),
      ]);

      // FLIP duplo: símbolo e letreiro voam até a posição exata de cada parte
      // do logo completo na navegação e se encaixam formando a marca.
      const nav = navLogo.getBoundingClientRect();
      const symbol = $('.loader__symbol', el);
      const flyTo = (node, part) => {
        const from = node.getBoundingClientRect();
        return {
          x: nav.left + part.x * nav.width - from.left,
          y: nav.top + part.y * nav.height - from.top,
          scale: (part.w * nav.width) / from.width,
          transformOrigin: '0 0',
          duration: 1.15,
          ease: 'expo.inOut',
        };
      };
      const toSymbol = flyTo(symbol, LOGO_PARTS.symbol);
      const toWord = flyTo(word, LOGO_PARTS.word);

      await new Promise((resolve) => {
        gsap.timeline({ onComplete: resolve })
          .to(['.loader__foot', '.loader__tag', '.loader__bar'], { autoAlpha: 0, duration: 0.4, ease: 'power2.out' }, 0)
          .to('.loader__ring', { scale: 1.6, autoAlpha: 0, duration: 0.8, ease: 'expo.in' }, 0)
          .to('.loader__grid', { autoAlpha: 0, duration: 0.6 }, 0)
          .set(symbol, { maskImage: 'none', webkitMaskImage: 'none' }, 0)
          .to(symbol, toSymbol, 0.2)
          .to(word, toWord, 0.25)
          .to('.loader__shutter--top', { yPercent: -100, duration: 1.3, ease: 'expo.inOut' }, 0.55)
          .to('.loader__shutter--bottom', { yPercent: 100, duration: 1.3, ease: 'expo.inOut' }, 0.55)
          .call(() => onOpen?.(), null, 0.7)
          .set(navLogo, { autoAlpha: 1 }, 1.42)
          .set([word, symbol], { autoAlpha: 0 }, 1.43);
      });
      el.remove();
    },
  };
}
