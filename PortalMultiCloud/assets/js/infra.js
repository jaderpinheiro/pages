// Palco da infraestrutura: controla o hero (foto → gêmeo 3D → vista explodida →
// mergulho) e a remontagem do módulo na abertura do contato. Um único canvas fixo
// e um único contexto WebGL atendem os dois momentos.
import { env, smooth, clamp, $, $$ } from './env.js';
import { FRAMES } from './frames-config.js';

const { gsap, ScrollTrigger } = window;

// Janelas (progresso do hero) em que cada legenda técnica aparece.
// No celular elas se revezam (2–3 por vez) para não se sobreporem.
const LABEL_WINDOWS = env.mobile
  ? [[0.27, 0.35], [0.31, 0.39], [0.35, 0.43], [0.39, 0.47], [0.43, 0.51], [0.47, 0.56]]
  : [[0.27, 0.55], [0.3, 0.55], [0.33, 0.56], [0.36, 0.56], [0.39, 0.57], [0.42, 0.57]];

export function initStage() {
  const canvas = $('#infraCanvas');
  const hero = $('#home');
  const stage = $('.hero__stage', hero);
  const photoImg = $('#heroPhoto img');
  const scan = $('#heroScan');
  const hudPct = $('#heroHudPct');
  const labels = $$('#heroLabels .callout');
  const contactIntro = $('#contactIntro');

  let renderer = null;
  let heroP = 0, heroActive = true, outroActive = false, outroQ = 0;
  let labelSizes = [];

  const ready = (async () => {
    if (env.reducedMotion) {
      document.documentElement.classList.add('no-stage');
      return null;
    }
    try {
      if (FRAMES) renderer = await (await import('./hero-frames.js')).createFrameRenderer(canvas, FRAMES, env);
      else if (env.webgl && !env.lowPower) renderer = (await import('./infra-scene.js')).createInfraScene(canvas, env);
    } catch (err) {
      console.warn('[MultiCloud] Cena 3D indisponível — usando a versão com imagem.', err);
      renderer = null;
    }
    if (renderer) {
      await renderer.ready;
      renderer.setHero(0);
      renderer.render();
    }
    document.documentElement.classList.add(renderer ? 'has-stage' : 'no-stage');
    return renderer;
  })();

  function applyCanvasOpacity() {
    let o = 0;
    if (heroActive) o = Math.max(o, 1 - smooth(0.86, 0.95, heroP));
    if (outroActive) o = Math.max(o, smooth(0, 0.3, outroQ) * (1 - smooth(0.82, 1, outroQ)));
    canvas.style.opacity = renderer ? o.toFixed(3) : 0;
  }

  function measureLabels() {
    labelSizes = labels.map((l) => l.offsetWidth);
  }

  function updateLabels() {
    const anchors = renderer?.getAnchors?.();
    labels.forEach((el, i) => {
      const [a, b] = LABEL_WINDOWS[i];
      const alpha = anchors ? smooth(a, a + 0.03, heroP) * (1 - smooth(b, b + 0.03, heroP)) : 0;
      const anchor = anchors?.[el.dataset.anchor];
      if (!anchor || !anchor.on || alpha <= 0.001) {
        if (el.style.opacity !== '0') el.style.opacity = '0';
        return;
      }
      // Lado preferido (data-side) com inversão só se a legenda sair da tela
      const w = labelSizes[i] || el.offsetWidth;
      let left = el.dataset.side === 'left';
      if (!left && anchor.x + w > window.innerWidth - 16) left = true;
      else if (left && anchor.x - w < 16) left = false;
      el.classList.toggle('callout--left', left);
      let x = left ? anchor.x - w + 4.5 : anchor.x - 4.5;
      if (env.mobile) x = Math.min(Math.max(8, x), window.innerWidth - w - 8);
      el.style.transform = `translate3d(${x.toFixed(1)}px, ${(anchor.y - el.offsetHeight / 2).toFixed(1)}px, 0)`;
      el.style.opacity = alpha.toFixed(3);
    });
  }

  function buildHeroTimeline() {
    const tl = gsap.timeline({ defaults: { ease: 'none' }, paused: true });
    tl.to('#heroIntro', { autoAlpha: 0, y: -70, duration: 0.07 }, 0.012)
      .to('#heroStats', { autoAlpha: 0, y: 30, duration: 0.05 }, 0)
      .to('#heroCue', { autoAlpha: 0, duration: 0.02 }, 0)
      .to('#heroHud', { autoAlpha: 1, duration: 0.02 }, 0.04)
      .to('#heroHud', { autoAlpha: 0, duration: 0.02 }, 0.9);

    if (renderer) {
      tl.fromTo(photoImg, { scale: 1 }, { scale: 1.07, duration: 0.19 }, 0)
        .fromTo(stage, { '--scan': '0%' }, { '--scan': '100%', duration: 0.13, ease: 'power1.inOut' }, 0.06)
        .fromTo(scan, { autoAlpha: 0 }, { autoAlpha: 1, duration: 0.008 }, 0.06)
        .to(scan, { autoAlpha: 0, duration: 0.01 }, 0.182);
    } else {
      // Versão leve: a própria foto conduz a narrativa com aproximação lenta.
      tl.fromTo(photoImg, { scale: 1, filter: 'brightness(1)' }, { scale: 1.35, filter: 'brightness(0.35)', duration: 0.9 }, 0.02);
    }

    const chapter = (sel, tIn, tOut) => {
      tl.fromTo(sel, { autoAlpha: 0, y: 50 }, { autoAlpha: 1, y: 0, duration: 0.035, ease: 'power2.out' }, tIn)
        .to(sel, { autoAlpha: 0, y: -50, duration: 0.035, ease: 'power2.in' }, tOut);
    };
    chapter('#heroChapterA', 0.23, 0.46);
    chapter('#heroChapterB', 0.63, 0.8);
    tl.fromTo('#heroQuestion', { autoAlpha: 0, y: 60 }, { autoAlpha: 1, y: 0, duration: 0.05, ease: 'power2.out' }, 0.89);
    tl.to({}, { duration: 0.001 }, 1); // ancora a duração total em 1
    return tl;
  }

  function init() {
    measureLabels();
    if (env.reducedMotion) return;

    const tl = buildHeroTimeline();
    ScrollTrigger.create({
      trigger: hero,
      start: 'top top',
      end: 'bottom bottom',
      animation: tl,
      scrub: env.touch ? true : 0.5,
      onToggle: (self) => { heroActive = self.isActive || self.progress < 1; applyCanvasOpacity(); },
    });
    tl.eventCallback('onUpdate', () => {
      heroP = tl.progress();
      if (renderer && !outroActive) renderer.setHero(heroP);
      hudPct.textContent = String(Math.round(heroP * 100)).padStart(3, '0');
      applyCanvasOpacity();
    });

    if (renderer && contactIntro) {
      ScrollTrigger.create({
        trigger: contactIntro,
        start: 'top bottom',
        end: 'bottom top',
        onUpdate: (self) => { outroQ = self.progress; renderer.setOutro(outroQ); applyCanvasOpacity(); },
        onToggle: (self) => {
          outroActive = self.isActive;
          if (outroActive) renderer.setOutro(self.progress);
          else renderer.setHero(heroP);
          applyCanvasOpacity();
        },
      });
    }

    gsap.ticker.add(() => {
      if (!renderer) return;
      const heroVisible = heroActive && heroP > 0.035 && heroP < 0.96;
      if (!heroVisible && !outroActive) return;
      renderer.render();
      if (heroVisible && !outroActive) updateLabels();
    });

    window.addEventListener('resize', () => {
      renderer?.resize();
      measureLabels();
    });
    applyCanvasOpacity();
  }

  /** Entrada do hero após a abertura */
  function intro({ immediate = false } = {}) {
    const title = $('#heroTitle');
    const split = window.SplitText ? new window.SplitText(title, { type: 'lines', mask: 'lines', linesClass: 'ln' }) : null;
    const stats = $$('#heroStats strong');
    const countUp = () => stats.forEach((el) => {
      const end = parseFloat(el.dataset.count);
      const dec = Number(el.dataset.decimals || 0);
      const suffix = el.dataset.suffix || '';
      const o = { v: 0 };
      gsap.to(o, {
        v: end, duration: 2.2, ease: 'expo.out',
        onUpdate: () => { el.textContent = o.v.toFixed(dec).replace('.', ',') + suffix; },
      });
    });
    if (immediate || env.reducedMotion) { countUp(); return; }
    gsap.timeline()
      .from('#heroPhoto', { scale: 1.22, duration: 2.6, ease: 'expo.out' }, 0)
      .from('#heroIntro .eyebrow', { autoAlpha: 0, x: -20, duration: 1, ease: 'expo.out' }, 0.2)
      .from(split ? split.lines : title, { yPercent: 110, duration: 1.4, stagger: 0.1, ease: 'expo.out' }, 0.25)
      .from('.hero__lead', { autoAlpha: 0, y: 24, duration: 1.1, ease: 'expo.out' }, 0.55)
      .from('.hero__ctas .btn', { autoAlpha: 0, y: 24, duration: 1, stagger: 0.08, ease: 'expo.out' }, 0.7)
      .from('#heroStats li', { autoAlpha: 0, y: 20, duration: 1, stagger: 0.08, ease: 'expo.out', onStart: countUp }, 0.8)
      // Anima os filhos: o contêiner #heroCue pertence à linha do tempo da rolagem
      .fromTo('#heroCue > *', { autoAlpha: 0, y: 10 }, { autoAlpha: 1, y: 0, duration: 1, stagger: 0.1 }, 1.4);
  }

  return { ready, init, intro, get renderer() { return renderer; } };
}

export { clamp };
