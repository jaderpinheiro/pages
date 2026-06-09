/* T Imóveis — Public JS */
(function () {
  'use strict';

  // ── Masks ──────────────────────────────────────────────────────────────────
  window.tiMaskPhone = function (el) {
    var v = el.value.replace(/\D/g, '');
    if (v.length <= 10) {
      v = v.replace(/^(\d{0,2})(\d{0,4})(\d{0,4}).*/, function (_, a, b, c) {
        return (a ? '(' + a : '') + (b ? ') ' + b : '') + (c ? '-' + c : '');
      });
    } else {
      v = v.replace(/^(\d{0,2})(\d{1})(\d{0,4})(\d{0,4}).*/, function (_, a, b, c, d) {
        return (a ? '(' + a : '') + (b ? ') ' + b : '') + (c ? ' ' + c : '') + (d ? '-' + d : '');
      });
    }
    el.value = v;
  };

  // ── Lead Popup ─────────────────────────────────────────────────────────────
  var _leadId = null;

  window.openLeadPopup = function (imovelId) {
    _leadId = imovelId || null;
    var info = document.getElementById('leadImovelInfo');
    if (info) info.style.display = _leadId ? 'block' : 'none';
    var popup = document.getElementById('leadPopup');
    if (popup) {
      popup.classList.add('active');
      var nomeEl = document.getElementById('leadNome');
      if (nomeEl) nomeEl.focus();
    }
  };

  window.closeLeadPopup = function () {
    var popup = document.getElementById('leadPopup');
    if (popup) popup.classList.remove('active');
  };

  window.submitLead = function () {
    var nome  = (document.getElementById('leadNome')     || {}).value || '';
    var tel   = (document.getElementById('leadTelefone') || {}).value || '';
    var email = (document.getElementById('leadEmail')    || {}).value || '';
    nome = nome.trim(); tel = tel.trim(); email = email.trim();

    if (!nome || !tel) {
      var target = document.getElementById(nome ? 'leadTelefone' : 'leadNome');
      if (target) { target.style.borderColor = '#DC2626'; target.focus(); }
      return;
    }

    // Dados do imóvel (se houver)
    var imovelInfo = null;
    if (_leadId && window._tiImoveisCache && window._tiImoveisCache[_leadId]) {
      imovelInfo = window._tiImoveisCache[_leadId];
    }

    var imovelNome = imovelInfo ? imovelInfo.title : '';
    var imovelId   = imovelInfo ? imovelInfo.id    : (_leadId || 0);

    // Salvar via AJAX (WordPress)
    if (typeof ti_ajax !== 'undefined') {
      var body = new URLSearchParams({
        action:      'ti_save_lead',
        nonce:       ti_ajax.nonce,
        nome:        nome,
        telefone:    tel,
        email:       email,
        imovel_id:   imovelId,
        imovel_nome: imovelNome,
      });
      fetch(ti_ajax.url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString()
      });
    }

    // Montar mensagem WA usando template configurado
    var phone  = (typeof ti_ajax !== 'undefined' && ti_ajax.phone) ? ti_ajax.phone : '5562995219521';
    var tpl    = (typeof ti_ajax !== 'undefined' && ti_ajax.msgTpl)
                    ? ti_ajax.msgTpl
                    : 'Olá Tarly! Me chamo {nome}. {imovel}Gostaria de mais informações. Meu celular: {celular}';
    var siteUrl = (typeof ti_ajax !== 'undefined' && ti_ajax.siteUrl) ? ti_ajax.siteUrl : window.location.origin + '/';

    var imovelTxt = '';
    var imovelUrl = siteUrl;
    if (imovelInfo) {
      imovelTxt = 'Tenho interesse em: *' + imovelInfo.title + '* (' + (imovelInfo.price || '') + '). ';
      imovelUrl = imovelInfo.url || siteUrl;
    }

    var msg = tpl
      .replace(/{nome}/g,    nome)
      .replace(/{celular}/g, tel)
      .replace(/{imovel}/g,  imovelTxt)
      .replace(/{preco}/g,   imovelInfo ? (imovelInfo.price || '') : '')
      .replace(/{url}/g,     imovelUrl);

    window.closeLeadPopup();
    window.open('https://wa.me/' + phone + '?text=' + encodeURIComponent(msg), '_blank');
  };

  // Fechar popup com ESC
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') window.closeLeadPopup();
  });

  // ── Nav Scroll ─────────────────────────────────────────────────────────────
  window.addEventListener('scroll', function () {
    var nav = document.getElementById('mainNav');
    if (nav) nav.classList.toggle('scrolled', window.scrollY > 40);
  });

  // ── Reveal on Scroll ───────────────────────────────────────────────────────
  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (e) {
      if (e.isIntersecting) { e.target.classList.add('visible'); observer.unobserve(e.target); }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

  function initReveal() {
    document.querySelectorAll('.reveal').forEach(function (el) { observer.observe(el); });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { setTimeout(initReveal, 100); });
  } else {
    setTimeout(initReveal, 100);
  }

  // ── Galeria / lightbox / carrossel — funções globais com fallbacks seguros ──
  if (typeof window.tiGoToSlide     === 'undefined') window.tiGoToSlide     = function(){};
  if (typeof window.tiGalPrev       === 'undefined') window.tiGalPrev       = function(){};
  if (typeof window.tiGalNext       === 'undefined') window.tiGalNext       = function(){};
  if (typeof window.tiOpenLightbox  === 'undefined') window.tiOpenLightbox  = function(){};
  if (typeof window.tiCloseLightbox === 'undefined') window.tiCloseLightbox = function(){};
  if (typeof window.tiLbPrev        === 'undefined') window.tiLbPrev        = function(){};
  if (typeof window.tiLbNext        === 'undefined') window.tiLbNext        = function(){};
  if (typeof window.tiCarouselScroll === 'undefined') {
    window.tiCarouselScroll = function (id, dir) {
      var el = document.getElementById(id);
      if (el) el.scrollBy({ left: dir * 300, behavior: 'smooth' });
    };
  }

})();
