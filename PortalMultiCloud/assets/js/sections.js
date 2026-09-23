// Coreografia das seções após o hero. Cada movimento revela, explica ou conecta.
import { env, clamp, lerp, smooth, $, $$ } from './env.js';
import { createShield } from './security-canvas.js';

const { gsap, ScrollTrigger, SplitText } = window;
const RM = env.reducedMotion;

export function initSections(ctx) {
  headings();
  cost();
  about();
  band();
  solutions(ctx);
  arch();
  security();
  support(ctx);
  clients();
  leaders();
  journey();
  faq();
}

// ---------------------------------------------------------------------------
// Títulos, sobretítulos e textos de apoio (revelação por máscara de linha)
function headings() {
  if (RM) return;
  $$('main .h2, .contact__title, .band__quote').forEach((el) => {
    if (el.closest('.solutions')) return; // tratados pela rolagem horizontal (desktop)
    SplitText.create(el, {
      type: 'lines',
      mask: 'lines',
      linesClass: 'ln',
      autoSplit: true,
      onSplit: (self) => gsap.from(self.lines, {
        yPercent: 108, duration: 1.2, stagger: 0.09, ease: 'expo.out',
        scrollTrigger: { trigger: el, start: 'top 88%', once: true },
      }),
    });
  });
  $$('main .eyebrow').forEach((el) => {
    if (el.closest('.hero, .solutions')) return;
    gsap.timeline({ scrollTrigger: { trigger: el, start: 'top 90%', once: true } })
      .from($('.eyebrow__line', el), { scaleX: 0, duration: 0.9, ease: 'expo.out' })
      .from(el, { autoAlpha: 0, x: -12, duration: 0.8, ease: 'expo.out' }, 0);
  });
  $$('main .lead').forEach((el) => {
    if (el.closest('.hero, .solutions')) return;
    gsap.from(el, { autoAlpha: 0, y: 26, duration: 1.1, ease: 'expo.out', scrollTrigger: { trigger: el, start: 'top 90%', once: true } });
  });
}

// ---------------------------------------------------------------------------
// 02 — 1 hora de indisponibilidade, minuto a minuto
function cost() {
  const sec = $('#custo');
  if (!sec) return;
  const ticks = $('#costTicks');
  ticks.innerHTML = '<i></i>'.repeat(60);
  const tickEls = [...ticks.children];
  const time = $('#costMinutes');
  const bar = $('#costBar');
  const values = $$('.cost__value', sec);
  const fmt = (v) => (v >= 1e6 ? 'R$ 1M' : `R$ ${Math.round(v / 1000)}k`);
  let lastLit = -1;

  const update = (p) => {
    const m = clamp(p / 0.78) * 60;
    const mm = Math.floor(m), ss = Math.floor((m % 1) * 60);
    time.textContent = m >= 60 ? '60:00' : `${String(mm).padStart(2, '0')}:${String(ss).padStart(2, '0')}`;
    bar.style.transform = `scaleX(${(m / 60).toFixed(4)})`;
    const lit = Math.floor(m);
    if (lit !== lastLit) { tickEls.forEach((t, i) => t.classList.toggle('on', i < lit)); lastLit = lit; }
    values.forEach((el) => {
      el.textContent = m >= 60 ? el.dataset.final : fmt((Number(el.dataset.cost) * m) / 60);
    });
  };

  if (RM) { update(1); return; }
  update(0);
  ScrollTrigger.create({ trigger: sec, start: 'top top', end: 'bottom bottom', onUpdate: (s) => update(s.progress) });
  gsap.from($$('.cost__item', sec), {
    autoAlpha: 0, y: 40, duration: 1, stagger: 0.1, ease: 'expo.out',
    scrollTrigger: { trigger: sec, start: 'top 45%', once: true },
  });
  gsap.from('.cost__clock', { autoAlpha: 0, duration: 1.2, scrollTrigger: { trigger: sec, start: 'top 45%', once: true } });
}

// ---------------------------------------------------------------------------
// 03 — Quem somos: texto que acende palavra a palavra e o "23" que se preenche
function about() {
  const text = $('.about__text');
  if (!text || RM) return;
  const split = SplitText.create(text, { type: 'words', wordsClass: 'w' });
  gsap.to(split.words, {
    opacity: 1, stagger: 0.12, ease: 'none',
    scrollTrigger: { trigger: text, start: 'top 82%', end: 'bottom 48%', scrub: true },
  });
  gsap.fromTo('#aboutNum', { '--fill': '0%' }, {
    '--fill': '100%', ease: 'none',
    scrollTrigger: { trigger: '.about__grid', start: 'top 75%', end: 'bottom 70%', scrub: true },
  });
  $$('.pillar').forEach((p) => {
    gsap.timeline({ scrollTrigger: { trigger: p, start: 'top 88%', once: true } })
      .fromTo(p, { '--draw': 0 }, { '--draw': 1, duration: 1.3, ease: 'expo.inOut' })
      .from(p.children, { autoAlpha: 0, y: 26, duration: 1, stagger: 0.08, ease: 'expo.out' }, 0.25);
  });
}

// Faixa do datacenter: a fresta se abre até ocupar a tela
function band() {
  const el = $('#aboutBand');
  if (!el || RM) return;
  gsap.fromTo(el, { '--band-y': '36%', '--band-x': '28%', '--band-s': 1.4 }, {
    '--band-y': '0%', '--band-x': '0%', '--band-s': 1, ease: 'none',
    scrollTrigger: { trigger: el, start: 'top bottom', end: 'top 12%', scrub: true },
  });
}

// ---------------------------------------------------------------------------
// 04 — Soluções: trilho horizontal no desktop, empilhado no mobile
function solutions(ctx) {
  const sec = $('#solutions');
  const track = $('#solTrack');
  if (!sec) return;
  const bar = $('#solProgress');
  orbitPulses();
  const path = $('.bp__path');
  const len = path.getTotalLength();
  gsap.set(path, { strokeDasharray: len, strokeDashoffset: len });

  const mm = gsap.matchMedia();
  mm.add('(min-width: 901px) and (prefers-reduced-motion: no-preference)', () => {
    const dist = () => track.scrollWidth - window.innerWidth;
    const setHeight = () => { sec.style.height = `${dist() + window.innerHeight}px`; };
    setHeight();
    const tween = gsap.to(track, {
      x: () => -dist(),
      ease: 'none',
      scrollTrigger: {
        trigger: sec, start: 'top top', end: 'bottom bottom', scrub: true, invalidateOnRefresh: true,
        onRefreshInit: setHeight,
        onUpdate: (s) => { bar.style.transform = `scaleX(${s.progress.toFixed(4)})`; },
      },
    });
    ctx.horizontal = { tween, track, dist };

    const intro = $('.sol-intro', track);
    gsap.timeline({ scrollTrigger: { trigger: sec, start: 'top 60%', once: true } })
      .from($('.eyebrow', intro), { autoAlpha: 0, x: -14, duration: 0.9, ease: 'expo.out' })
      .from(SplitText.create($('.h2', intro), { type: 'lines', mask: 'lines', linesClass: 'ln' }).lines, { yPercent: 108, duration: 1.2, stagger: 0.09, ease: 'expo.out' }, 0.1)
      .from([$('.lead', intro), ...$$('.sol-intro__index li', intro)], { autoAlpha: 0, y: 24, duration: 0.9, stagger: 0.06, ease: 'expo.out' }, 0.35);

    $$('.sol', track).forEach((panel) => {
      const st = (start, extra = {}) => ({ trigger: panel, containerAnimation: tween, start, ...extra });
      gsap.fromTo($('.sol__visual', panel), { clipPath: 'inset(0% 0% 0% 100%)' }, {
        clipPath: 'inset(0% 0% 0% 0%)', duration: 1.5, ease: 'expo.inOut',
        scrollTrigger: st('left 78%', { toggleActions: 'play none none reverse' }),
      });
      const title = SplitText.create($('.sol__title', panel), { type: 'lines', mask: 'lines', linesClass: 'ln' });
      gsap.timeline({ scrollTrigger: st('left 62%', { toggleActions: 'play none none reverse' }) })
        .from(title.lines, { yPercent: 108, duration: 1.1, stagger: 0.08, ease: 'expo.out' })
        .from([$('.sol__desc', panel), ...$$('.sol__list li', panel), $('.link-arrow', panel)], {
          autoAlpha: 0, y: 26, duration: 0.9, stagger: 0.05, ease: 'expo.out',
        }, 0.15);
      gsap.fromTo($('.sol__n', panel), { x: 160 }, { x: -160, ease: 'none', scrollTrigger: st('left right', { end: 'right left', scrub: true }) });
      const img = $('.sol__photo img', panel);
      if (img) gsap.fromTo(img, { xPercent: -7 }, { xPercent: 7, ease: 'none', scrollTrigger: st('left right', { end: 'right left', scrub: true }) });
    });

    // Camadas de segurança se separam; blueprint de governança se desenha
    gsap.fromTo('.plates__stack', { '--gap': '3px' }, {
      '--gap': '38px', ease: 'none',
      scrollTrigger: { trigger: '#governance', containerAnimation: tween, start: 'left 85%', end: 'center 55%', scrub: true },
    });
    gsap.to(path, {
      strokeDashoffset: 0, ease: 'none',
      scrollTrigger: { trigger: '#compliance', containerAnimation: tween, start: 'left 85%', end: 'center 50%', scrub: true },
    });
    gsap.from('.bp__node', {
      autoAlpha: 0, scale: 0.4, transformOrigin: '0 0', stagger: 0.12, duration: 0.6, ease: 'back.out(2)',
      scrollTrigger: { trigger: '#compliance', containerAnimation: tween, start: 'left 55%', toggleActions: 'play none none reverse' },
    });

    return () => { sec.style.height = ''; ctx.horizontal = null; };
  });

  mm.add('(max-width: 900px), (prefers-reduced-motion: reduce)', () => {
    gsap.set('.plates__stack', { '--gap': '30px' });
    if (RM) { gsap.set(path, { strokeDashoffset: 0 }); return; }
    $$('.sol', track).forEach((panel) => {
      gsap.fromTo($('.sol__visual', panel), { clipPath: 'inset(100% 0% 0% 0%)' }, {
        clipPath: 'inset(0% 0% 0% 0%)', duration: 1.4, ease: 'expo.inOut',
        scrollTrigger: { trigger: panel, start: 'top 80%', once: true },
      });
      gsap.from($('.sol__body', panel).children, {
        autoAlpha: 0, y: 30, duration: 1, stagger: 0.06, ease: 'expo.out',
        scrollTrigger: { trigger: $('.sol__body', panel), start: 'top 85%', once: true },
      });
    });
    gsap.to(path, { strokeDashoffset: 0, duration: 2.2, ease: 'power2.inOut', scrollTrigger: { trigger: '#compliance', start: 'top 70%', once: true } });
  });
}

// Pulsos de dados entre MultiCloud e os provedores (Azure, Oracle Cloud, AWS)
function orbitPulses() {
  const svg = $('.orbit');
  if (!svg || RM) return;
  const core = [300, 300];
  const nodes = [[300, 90], [481.9, 405], [118.1, 405]];
  const dots = $$('.pulse', svg);
  let visible = false;
  new IntersectionObserver(([e]) => { visible = e.isIntersecting; }).observe(svg);
  gsap.ticker.add((time) => {
    if (!visible) return;
    dots.forEach((d, i) => {
      const n = nodes[i];
      const raw = (time * 0.32 + i / 3) % 2;
      const t = raw < 1 ? raw : 2 - raw; // vai e volta
      const e = t * t * (3 - 2 * t);
      d.setAttribute('cx', lerp(core[0], n[0], e).toFixed(1));
      d.setAttribute('cy', lerp(core[1], n[1], e).toFixed(1));
    });
  });
}

// ---------------------------------------------------------------------------
// 05 — Arquitetura: a pilha de camadas se abre e acende de cima para baixo
function arch() {
  const sec = $('#arquitetura');
  if (!sec) return;
  const iso = $('#iso');
  const layers = $$('.iso__layer', iso);
  const items = $$('#archList li');
  const setIdx = (idx) => {
    layers.forEach((l) => l.classList.toggle('is-active', Number(l.dataset.layer) <= idx));
    items.forEach((li) => li.classList.toggle('is-active', Number(li.dataset.layer) <= idx));
  };
  if (RM) { setIdx(4); return; }
  const maxSpread = () => (window.innerWidth < 1025 ? 58 : 88);
  const update = (p) => {
    iso.style.setProperty('--spread', `${lerp(14, maxSpread(), smooth(0, 0.22, p)).toFixed(1)}px`);
    setIdx(p < 0.2 ? -1 : Math.min(4, Math.floor((p - 0.2) / 0.15)));
  };
  update(0);
  ScrollTrigger.create({ trigger: sec, start: 'top top', end: 'bottom bottom', onUpdate: (s) => update(s.progress) });
  gsap.from(layers, {
    autoAlpha: 0, y: -80, duration: 1.2, stagger: 0.08, ease: 'expo.out',
    scrollTrigger: { trigger: sec, start: 'top 55%', once: true },
  });
}

// ---------------------------------------------------------------------------
// 06 — Segurança: camadas ao redor do núcleo defletem ameaças
function security() {
  const sec = $('#seguranca');
  const canvas = $('#secCanvas');
  if (!sec || !canvas) return;
  const items = $$('#secLayers li');
  const shield = createShield(canvas, env);
  const apply = (p) => {
    shield.setProgress(p);
    items.forEach((li, i) => li.classList.toggle('is-on', p >= 0.12 + i * 0.12));
  };
  if (RM) { apply(1); shield.drawOnce(); return; }
  apply(0);
  ScrollTrigger.create({
    trigger: sec, start: 'top bottom', end: 'bottom top',
    onToggle: (s) => shield.setActive(s.isActive),
  });
  ScrollTrigger.create({ trigger: sec, start: 'top top', end: 'bottom bottom', onUpdate: (s) => apply(s.progress) });
  window.addEventListener('resize', () => shield.resize());
}

// ---------------------------------------------------------------------------
// 07 — Suporte: relógio 24h ao vivo (Goiânia) e faixa que reage à rolagem
function support(ctx) {
  const ticksG = $('#dialTicks');
  const hand = $('#dialHand');
  const arc = $('#dialArc');
  const timeEl = $('#dialTime');
  if (!ticksG) return;
  const NS = 'http://www.w3.org/2000/svg';
  for (let i = 0; i < 96; i++) {
    const major = i % 24 === 0, hour = i % 4 === 0;
    const a = (i / 96) * Math.PI * 2 - Math.PI / 2;
    const r1 = 180, r2 = major ? 162 : hour ? 170 : 175;
    const l = document.createElementNS(NS, 'line');
    l.setAttribute('x1', 200 + Math.cos(a) * r1); l.setAttribute('y1', 200 + Math.sin(a) * r1);
    l.setAttribute('x2', 200 + Math.cos(a) * r2); l.setAttribute('y2', 200 + Math.sin(a) * r2);
    l.setAttribute('class', `dial__tick${major ? ' dial__tick--major' : ''}`);
    ticksG.appendChild(l);
    if (major) {
      const t = document.createElementNS(NS, 'text');
      t.setAttribute('x', 200 + Math.cos(a) * 144); t.setAttribute('y', 200 + Math.sin(a) * 144);
      t.setAttribute('class', 'dial__num');
      t.textContent = String((i / 96) * 24).padStart(2, '0') + 'h';
      ticksG.appendChild(t);
    }
  }
  const C = 2 * Math.PI * 180;
  arc.style.strokeDasharray = `${C}`;
  const fmt = new Intl.DateTimeFormat('pt-BR', { timeZone: 'America/Sao_Paulo', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
  const now = () => {
    const parts = Object.fromEntries(fmt.formatToParts(new Date()).map((p) => [p.type, p.value]));
    const h = Number(parts.hour) % 24, m = Number(parts.minute), s = Number(parts.second);
    return { frac: (h * 3600 + m * 60 + s) / 86400, text: `${parts.hour}:${parts.minute}:${parts.second}` };
  };
  const state = { frac: 0 };
  const draw = () => {
    hand.style.transform = `rotate(${(state.frac * 360).toFixed(2)}deg)`;
    arc.style.strokeDashoffset = `${(C * (1 - state.frac)).toFixed(1)}`;
  };
  let started = false, timer = null;
  const tick = () => { const n = now(); timeEl.textContent = n.text; state.frac = n.frac; draw(); };
  new IntersectionObserver(([e]) => {
    if (e.isIntersecting) {
      if (!started && !RM) {
        started = true;
        const n = now();
        timeEl.textContent = n.text;
        gsap.fromTo(state, { frac: 0 }, { frac: n.frac, duration: 2.4, ease: 'expo.inOut', onUpdate: draw, onComplete: () => { timer = setInterval(tick, 1000); } });
      } else if (!timer) { tick(); timer = setInterval(tick, 1000); }
    } else if (timer) { clearInterval(timer); timer = null; }
  }, { threshold: 0.2 }).observe($('.dial'));
  tick();

  // Faixa contínua: a velocidade e o sentido acompanham a rolagem
  const track = $('#tickerTrack');
  if (!track || RM) return;
  let x = 0, dir = -1, visible = false, half = 0;
  const measure = () => { half = (track.scrollWidth + 40) / 2; };
  measure();
  window.addEventListener('resize', measure);
  new IntersectionObserver(([e]) => { visible = e.isIntersecting; }).observe(track);
  gsap.ticker.add((_, dtMs) => {
    if (!visible) return;
    const v = ctx.lenis ? ctx.lenis.velocity : 0;
    if (Math.abs(v) > 0.5) dir = v > 0 ? -1 : 1;
    x += dir * (dtMs / 16.67) * (0.8 + Math.min(Math.abs(v) * 0.35, 14));
    if (x <= -half) x += half;
    if (x > 0) x -= half;
    track.style.transform = `translate3d(${x.toFixed(2)}px,0,0)`;
  });
}

// ---------------------------------------------------------------------------
function clients() {
  const grid = $('#logos');
  if (!grid || RM) return;
  gsap.from($$('li', grid), {
    autoAlpha: 0, y: 34, duration: 1, ease: 'expo.out',
    stagger: { each: 0.035, grid: 'auto', from: 'center' },
    scrollTrigger: { trigger: grid, start: 'top 85%', once: true },
  });
  const count = $('.clients__count strong');
  const o = { v: 0 };
  gsap.to(o, {
    v: 21, duration: 1.8, ease: 'expo.out',
    onUpdate: () => { count.textContent = `+${Math.round(o.v)}`; },
    scrollTrigger: { trigger: count, start: 'top 90%', once: true },
  });
}

function leaders() {
  if (RM) return;
  $$('.leader').forEach((card, i) => {
    const photo = $('.leader__photo', card);
    gsap.timeline({ scrollTrigger: { trigger: card, start: 'top 88%', once: true } })
      .fromTo(photo, { clipPath: 'inset(100% 0% 0% 0%)' }, { clipPath: 'inset(0% 0% 0% 0%)', duration: 1.5, ease: 'expo.inOut' })
      .from($('img', photo), { scale: 1.35, duration: 2, ease: 'expo.out' }, 0.2)
      .from($('.leader__meta', card).children, { autoAlpha: 0, y: 20, duration: 0.9, stagger: 0.06, ease: 'expo.out' }, 0.6);
    if (window.innerWidth > 720) {
      gsap.fromTo(card, { y: 40 + i * 30 }, { y: -(40 + i * 30), ease: 'none', scrollTrigger: { trigger: '.leaders__grid', start: 'top bottom', end: 'bottom top', scrub: true } });
    }
  });
}

function journey() {
  const steps = $$('.step');
  const line = $('#stepsLine');
  if (!steps.length) return;
  if (RM) { steps.forEach((s) => s.classList.add('is-on')); return; }
  ScrollTrigger.create({
    trigger: '.steps', start: 'top 72%', end: 'bottom 55%', scrub: true,
    onUpdate: (s) => {
      if (line) line.style.transform = `scaleX(${s.progress.toFixed(4)})`;
      steps.forEach((st, i) => st.classList.toggle('is-on', s.progress >= i / steps.length - 0.001 && s.progress > 0.01));
    },
  });
  gsap.from(steps, { autoAlpha: 0, y: 40, duration: 1, stagger: 0.1, ease: 'expo.out', scrollTrigger: { trigger: '.steps', start: 'top 85%', once: true } });
}

function faq() {
  $$('.qa').forEach((d) => {
    const summary = $('summary', d);
    const body = $('.qa__body', d);
    summary.addEventListener('click', (e) => {
      if (RM) return;
      e.preventDefault();
      if (d.open) {
        gsap.to(body, { height: 0, duration: 0.5, ease: 'expo.inOut', onComplete: () => { d.open = false; gsap.set(body, { clearProps: 'height' }); ScrollTrigger.refresh(); } });
      } else {
        d.open = true;
        gsap.fromTo(body, { height: 0 }, { height: body.scrollHeight, duration: 0.7, ease: 'expo.out', onComplete: () => { gsap.set(body, { clearProps: 'height' }); ScrollTrigger.refresh(); } });
        gsap.from($('p', body), { autoAlpha: 0, y: 10, duration: 0.6, delay: 0.1 });
      }
    });
  });
  if (RM) return;
  gsap.from('.qa', { autoAlpha: 0, y: 24, duration: 0.9, stagger: 0.06, ease: 'expo.out', scrollTrigger: { trigger: '#faqList', start: 'top 85%', once: true } });
}
