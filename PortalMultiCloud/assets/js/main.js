// Portal MultiCloud — ponto de entrada.
// Ordem: rolagem suave → abertura → palco 3D → animações de seção → revelação.
import { env, $ } from './env.js';
import { createLoader } from './loader.js';
import { initStage } from './infra.js';
import { initUI } from './ui.js';
import { initSections } from './sections.js';
import { initContact } from './contact.js';

window.__mcBooted = true; // desarma a proteção de abertura do index.html
const { gsap, ScrollTrigger, SplitText, Lenis } = window;
gsap.registerPlugin(ScrollTrigger, SplitText);
ScrollTrigger.config({ ignoreMobileResize: true });

const ctx = { lenis: null, horizontal: null, menuOpen: false };
document.body.classList.add('is-loading');

// Rolagem suave sincronizada com o ScrollTrigger (um único relógio: gsap.ticker)
if (!env.reducedMotion && env.smooth && Lenis) {
  ctx.lenis = new Lenis({
    duration: 1.15,
    easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
    smoothWheel: true,
    touchMultiplier: 1.4,
  });
  ctx.lenis.on('scroll', ScrollTrigger.update);
  gsap.ticker.add((time) => ctx.lenis.raf(time * 1000));
  gsap.ticker.lagSmoothing(0);
  ctx.lenis.stop();
}

const withTimeout = (p, ms) => Promise.race([p, new Promise((r) => setTimeout(r, ms))]);

async function boot() {
  const loader = createLoader();
  const stage = initStage();
  if (env.debug) window.__mc = { ctx, stage, env, ScrollTrigger };
  const heroImg = $('#heroPhoto img');

  const tasks = [
    withTimeout(document.fonts?.ready ?? Promise.resolve(), 3000),
    withTimeout(heroImg.decode?.().catch(() => {}) ?? Promise.resolve(), 6000),
    withTimeout(stage.ready, 9000),
  ];
  let done = 0;
  tasks.forEach((t) => t.then(() => loader.progress(++done / tasks.length)));
  await Promise.all(tasks);

  initUI(ctx);
  stage.init();
  initSections(ctx);
  initContact(ctx);
  ScrollTrigger.refresh();

  const startedMidPage = window.scrollY > window.innerHeight * 0.5;
  await loader.finish(() => stage.intro({ immediate: startedMidPage }));

  document.body.classList.remove('is-loading');
  ctx.lenis?.start();

  // Link direto para uma seção (ex.: multicloud.com.br/#contact)
  if (location.hash.length > 1) {
    const target = document.getElementById(decodeURIComponent(location.hash.slice(1)));
    if (target) ctx.scrollToElement?.(target, { immediate: true });
  }
}

boot().catch((err) => {
  console.error('[MultiCloud] Falha na inicialização', err);
  $('#loader')?.remove();
  document.body.classList.remove('is-loading');
  ctx.lenis?.start();
});
