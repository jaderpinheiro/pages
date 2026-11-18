/* ==========================================================================
   Registros de campo — filtros e visualizador (dialog nativo)
   Sem JS, a grade completa continua visível e cada imagem tem legenda.
   ========================================================================== */
// ---- vídeos: botão de play, um vídeo por vez, cenas clicáveis ----
// Sem JS, cada vídeo mantém os controles nativos.
(function () {
  'use strict';
  const cards = Array.prototype.slice.call(document.querySelectorAll('.vcard'));
  if (!cards.length) return;
  const vids = [];
  function tocar(v) {
    vids.forEach((o) => { if (o !== v && !o.paused) o.pause(); });
    v.controls = true;
    const p = v.play(); if (p && p.catch) p.catch(() => {});
  }
  cards.forEach((card) => {
    const v = card.querySelector('video'), media = card.querySelector('.vcard__media');
    if (!v || !media) return;
    vids.push(v);
    v.controls = false;
    const b = document.createElement('button');
    b.type = 'button'; b.className = 'vcard__play';
    const nome = card.querySelector('.atv');
    b.setAttribute('aria-label', 'Assistir: ' + (nome ? nome.textContent.replace(/^\d/, '').trim() : 'vídeo'));
    b.innerHTML = '<i aria-hidden="true"></i>';
    media.appendChild(b);
    b.addEventListener('click', () => { b.remove(); tocar(v); v.focus(); });
    v.addEventListener('play', () => { card.classList.add('is-playing'); if (b.isConnected) b.remove(); v.controls = true; vids.forEach((o) => { if (o !== v && !o.paused) o.pause(); }); });
  });
  document.querySelectorAll('[data-cenas]').forEach((box) => {
    const v = document.getElementById(box.dataset.cenas);
    if (!v) return;
    const bts = Array.prototype.slice.call(box.querySelectorAll('button[data-t]'));
    const marcar = () => {
      const t = v.currentTime; let ativo = null;
      bts.forEach((x) => { if (t >= +x.dataset.t - 0.05) ativo = x; });
      bts.forEach((x) => x.setAttribute('aria-current', String(x === ativo && (t > 0 || !v.paused))));
    };
    bts.forEach((x) => x.addEventListener('click', () => {
      const ir = () => { v.currentTime = +x.dataset.t; tocar(v); marcar(); };
      if (v.readyState >= 1) ir(); else { v.preload = 'auto'; v.addEventListener('loadedmetadata', ir, { once: true }); v.load(); }
    }));
    v.addEventListener('timeupdate', marcar);
  });
  // setas para as faixas com mais vídeos do que cabem na tela
  document.querySelectorAll('.vcap').forEach((cap) => {
    const strip = cap.querySelector('.vstrip'), head = cap.querySelector('.vcap__head');
    if (!strip || !head || strip.classList.contains('vstrip--cenas')) return;
    const nav = document.createElement('div');
    nav.className = 'vnav'; nav.hidden = true;
    nav.innerHTML = '<button type="button" aria-label="Vídeo anterior">←</button><button type="button" aria-label="Próximo vídeo">→</button>';
    head.appendChild(nav);
    const [ant, prox] = nav.querySelectorAll('button');
    const passo = () => { const c = strip.querySelector('.vcard'); return c ? c.offsetWidth + 16 : strip.clientWidth * 0.8; };
    const estado = () => {
      const max = strip.scrollWidth - strip.clientWidth;
      nav.hidden = max < 8;
      ant.disabled = strip.scrollLeft < 4; prox.disabled = strip.scrollLeft > max - 4;
    };
    ant.addEventListener('click', () => strip.scrollBy({ left: -passo(), behavior: 'smooth' }));
    prox.addEventListener('click', () => strip.scrollBy({ left: passo(), behavior: 'smooth' }));
    strip.addEventListener('scroll', estado, { passive: true });
    window.addEventListener('resize', estado);
    estado();
  });
})();

(function () {
  'use strict';
  const $ = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.prototype.slice.call((c || document).querySelectorAll(s));
  const fotos = $$('.grade .foto');
  if (!fotos.length) return;

  // ---- filtros ----
  let cat = 'todas', rod = 'todas';
  const cont = $('[data-contador]');
  function aplicar() {
    let n = 0;
    fotos.forEach((f) => {
      const ok = (cat === 'todas' || f.dataset.cat === cat) && (rod === 'todas' || f.dataset.rod === rod);
      f.hidden = !ok; if (ok) n++;
    });
    if (cont) cont.textContent = n;
    if (window.ScrollTrigger) window.ScrollTrigger.refresh();
  }
  $$('[data-f-cat]').forEach((b) => b.addEventListener('click', () => { cat = b.dataset.fCat; $$('[data-f-cat]').forEach((x) => x.setAttribute('aria-pressed', String(x === b))); aplicar(); }));
  $$('[data-f-rod]').forEach((b) => b.addEventListener('click', () => { rod = b.dataset.fRod; $$('[data-f-rod]').forEach((x) => x.setAttribute('aria-pressed', String(x === b))); aplicar(); }));

  // ---- visualizador ----
  const dlg = $('.lightbox');
  if (!dlg || typeof dlg.showModal !== 'function') return;
  const img = $('[data-lb-img]', dlg), atv = $('[data-lb-atv]', dlg), meta = $('[data-lb-meta]', dlg), pos = $('[data-lb-pos]', dlg), mapa = $('[data-lb-mapa]', dlg);
  let atual = -1, origem = null;
  const visiveis = () => fotos.filter((f) => !f.hidden);
  function mostrar(i) {
    const lista = visiveis(); if (!lista.length) return;
    atual = (i + lista.length) % lista.length;
    const f = lista[atual], th = $('img', f);
    img.src = window.TJWAsset ? window.TJWAsset(f.dataset.full) : f.dataset.full; img.width = +f.dataset.w; img.height = +f.dataset.h; img.alt = th.alt;
    atv.textContent = $('.atv', f).textContent;
    meta.innerHTML = $('.meta', f).innerHTML;
    pos.textContent = (atual + 1) + ' / ' + lista.length;
    if (f.dataset.mapa) { mapa.href = f.dataset.mapa; mapa.hidden = false; } else mapa.hidden = true;
  }
  fotos.forEach((f) => {
    const b = $('button.abrir', f);
    if (b) b.addEventListener('click', () => { origem = b; mostrar(visiveis().indexOf(f)); dlg.showModal(); if (window.__tjwLenis) window.__tjwLenis.stop(); });
  });
  dlg.addEventListener('click', (e) => {
    const a = e.target.closest('[data-lb]');
    if (a) { const k = a.dataset.lb; if (k === 'close') dlg.close(); else mostrar(atual + (k === 'next' ? 1 : -1)); return; }
    if (e.target === dlg) dlg.close();
  });
  dlg.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowRight') { e.preventDefault(); mostrar(atual + 1); }
    if (e.key === 'ArrowLeft') { e.preventDefault(); mostrar(atual - 1); }
  });
  dlg.addEventListener('close', () => { img.removeAttribute('src'); if (window.__tjwLenis) window.__tjwLenis.start(); if (origem) origem.focus(); });
})();
