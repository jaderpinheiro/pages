// Interface global: navegação, menu, âncoras, trilho de progresso, cursor,
// grão de filme, botões magnéticos e WhatsApp flutuante.
import { env, $, $$ } from './env.js';

const { gsap, ScrollTrigger } = window;

export function initUI(ctx) {
  grain();
  nav(ctx);
  menu(ctx);
  anchors(ctx);
  rail();
  cursor();
  magnetic();
  waFloat();
}

function grain() {
  const el = $('.grain');
  if (!el || env.reducedMotion) return;
  const c = document.createElement('canvas');
  c.width = c.height = 160;
  const g = c.getContext('2d');
  const img = g.createImageData(160, 160);
  for (let i = 0; i < img.data.length; i += 4) {
    const v = Math.random() * 255;
    img.data[i] = img.data[i + 1] = img.data[i + 2] = v;
    img.data[i + 3] = 255;
  }
  g.putImageData(img, 0, 0);
  el.style.backgroundImage = `url(${c.toDataURL('image/png')})`;
}

function nav(ctx) {
  const header = $('#header');
  ScrollTrigger.create({
    start: 0,
    end: 'max',
    onUpdate(self) {
      const y = self.scroll();
      header.classList.toggle('is-scrolled', y > 40);
      if (ctx.menuOpen) return;
      if (y > 320 && self.direction === 1) header.classList.add('is-hidden');
      else if (self.direction === -1) header.classList.remove('is-hidden');
    },
  });
  header.addEventListener('focusin', () => header.classList.remove('is-hidden'));

  // Link ativo conforme a seção em tela
  $$('.nav__links a').forEach((link) => {
    const section = document.getElementById(link.getAttribute('href').slice(1));
    if (!section) return;
    ScrollTrigger.create({
      trigger: section,
      start: 'top 50%',
      end: 'bottom 50%',
      onToggle: (s) => link.classList.toggle('is-active', s.isActive),
    });
  });
}

function menu(ctx) {
  const toggle = $('#menuToggle');
  const panel = $('#menu');
  const header = $('#header');
  const links = $$('.menu__links li');
  let tl = null;

  const open = () => {
    ctx.menuOpen = true;
    panel.hidden = false;
    toggle.setAttribute('aria-expanded', 'true');
    $('.nav__toggle-label', toggle).textContent = 'Fechar';
    header.classList.remove('is-hidden');
    ctx.lenis?.stop();
    tl?.kill();
    tl = gsap.timeline()
      .fromTo(panel, { clipPath: 'inset(0 0 100% 0)' }, { clipPath: 'inset(0 0 0% 0)', duration: env.reducedMotion ? 0 : 0.9, ease: 'expo.inOut' })
      .from(links, { yPercent: 60, autoAlpha: 0, duration: 0.8, stagger: 0.05, ease: 'expo.out' }, 0.35)
      .from('.menu__foot', { autoAlpha: 0, y: 20, duration: 0.6 }, 0.6);
    $('a', panel)?.focus({ preventScroll: true });
  };
  const close = (focusToggle = true) => {
    if (!ctx.menuOpen) return;
    ctx.menuOpen = false;
    toggle.setAttribute('aria-expanded', 'false');
    $('.nav__toggle-label', toggle).textContent = 'Menu';
    tl?.kill();
    tl = gsap.timeline({ onComplete: () => { panel.hidden = true; gsap.set([links, '.menu__foot'], { clearProps: 'all' }); } })
      .to(panel, { clipPath: 'inset(0 0 100% 0)', duration: env.reducedMotion ? 0 : 0.7, ease: 'expo.inOut' });
    ctx.lenis?.start();
    if (focusToggle) toggle.focus({ preventScroll: true });
  };
  ctx.closeMenu = close;
  toggle.addEventListener('click', () => (ctx.menuOpen ? close() : open()));
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
  window.addEventListener('resize', () => { if (window.innerWidth > 1024) close(false); });
}

function anchors(ctx) {
  const resolve = (el) => {
    if (el.id === 'home') return 0;
    const h = ctx.horizontal;
    if (h && h.track.contains(el)) {
      const st = h.tween.scrollTrigger;
      const prog = Math.min(1, Math.max(0, el.offsetLeft / h.dist()));
      return st.start + prog * (st.end - st.start) + 2;
    }
    return el.getBoundingClientRect().top + window.scrollY;
  };

  ctx.scrollToElement = (el, { immediate = false, offset = 0 } = {}) => {
    const y = Math.max(0, resolve(el) + offset);
    if (ctx.lenis) ctx.lenis.scrollTo(y, { immediate, duration: 1.8, easing: (t) => 1 - Math.pow(1 - t, 4), force: true });
    else window.scrollTo({ top: y, behavior: immediate || env.reducedMotion ? 'auto' : 'smooth' });
  };

  document.addEventListener('click', (e) => {
    const a = e.target.closest('a[href^="#"]');
    if (!a) return;
    const id = decodeURIComponent(a.getAttribute('href').slice(1));
    let el = id ? document.getElementById(id) : null;
    if (!el) return;
    e.preventDefault();
    let offset = 0;
    // "Solicitar proposta" leva direto ao formulário
    if (a.dataset.solution) { el = $('#contact-form'); offset = -110; }
    if (ctx.menuOpen) ctx.closeMenu(false);
    ctx.scrollToElement(el, { offset });
    if (a.dataset.solution) setTimeout(() => $('#f-name')?.focus({ preventScroll: true }), 1900);
  });
}

function rail() {
  const railEl = $('#rail');
  const idx = $('#railIndex');
  const name = $('#railName');
  const fill = $('#railFill');
  $$('[data-chapter]').forEach((sec, i) => {
    ScrollTrigger.create({
      trigger: sec,
      start: 'top 55%',
      end: 'bottom 55%',
      onToggle: (s) => {
        if (!s.isActive) return;
        idx.textContent = String(i).padStart(2, '0');
        name.textContent = sec.dataset.chapter;
      },
    });
  });
  ScrollTrigger.create({
    start: 0,
    end: 'max',
    onUpdate: (s) => {
      fill.style.transform = `scaleY(${s.progress.toFixed(4)})`;
      railEl.classList.toggle('is-visible', s.scroll() > window.innerHeight * 0.3);
    },
  });
}

function cursor() {
  if (!env.finePointer || env.reducedMotion) return;
  const root = $('#cursor');
  const dot = $('.cursor__dot', root);
  const ring = $('.cursor__ring', root);
  document.documentElement.classList.add('has-cursor');
  gsap.set([dot, ring], { x: -100, y: -100 });
  const xd = gsap.quickTo(dot, 'x', { duration: 0.12, ease: 'power3' });
  const yd = gsap.quickTo(dot, 'y', { duration: 0.12, ease: 'power3' });
  const xr = gsap.quickTo(ring, 'x', { duration: 0.5, ease: 'power3' });
  const yr = gsap.quickTo(ring, 'y', { duration: 0.5, ease: 'power3' });
  window.addEventListener('pointermove', (e) => {
    xd(e.clientX); yd(e.clientY); xr(e.clientX); yr(e.clientY);
    root.classList.remove('is-hidden');
  }, { passive: true });
  document.documentElement.addEventListener('pointerleave', () => root.classList.add('is-hidden'));
  const interactive = 'a, button, summary, select, input, textarea, .logos li';
  document.addEventListener('pointerover', (e) => { if (e.target.closest(interactive)) root.classList.add('is-hover'); });
  document.addEventListener('pointerout', (e) => { if (e.target.closest(interactive)) root.classList.remove('is-hover'); });
}

function magnetic() {
  if (!env.finePointer || env.reducedMotion) return;
  $$('.btn').forEach((btn) => {
    const xTo = gsap.quickTo(btn, 'x', { duration: 0.5, ease: 'power3' });
    const yTo = gsap.quickTo(btn, 'y', { duration: 0.5, ease: 'power3' });
    btn.addEventListener('pointermove', (e) => {
      const r = btn.getBoundingClientRect();
      xTo((e.clientX - r.left - r.width / 2) * 0.18);
      yTo((e.clientY - r.top - r.height / 2) * 0.3);
    });
    btn.addEventListener('pointerleave', () => { xTo(0); yTo(0); });
  });
}

function waFloat() {
  const wa = $('.wa-float');
  if (!wa) return;
  let afterHero = false, inForm = false;
  const apply = () => wa.classList.toggle('is-visible', afterHero && !inForm);
  ScrollTrigger.create({ trigger: '#custo', start: 'top 70%', endTrigger: 'html', end: 'bottom bottom', onToggle: (s) => { afterHero = s.isActive; apply(); } });
  ScrollTrigger.create({ trigger: '.contact__grid', start: 'top 80%', end: 'bottom top', onToggle: (s) => { inForm = s.isActive; apply(); } });
}
