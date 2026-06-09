<?php defined('ABSPATH') || exit; ?>
<!-- TOP BAR -->
<div class="topbar">
  <div class="topbar-inner">
    <div class="topbar-left">
      <span>📍 Goiânia & Aparecida de Goiânia</span>
      <span>✉ contato@tarlypinheiro.com.br</span>
      <span class="creci-badge"><?= ti_get_creci() ?></span>
    </div>
    <div class="topbar-right">
      <a href="tel:+<?= ti_get_phone_wa() ?>" style="color:rgba(255,255,255,0.75);text-decoration:none;">☎ <?= ti_get_phone_display() ?></a>
      <a href="javascript:void(0)" onclick="openLeadPopup(null)" class="btn-contact-top">Falar no WhatsApp</a>
    </div>
  </div>
</div>

<!-- NAV -->
<nav id="mainNav">
  <div class="nav-inner">
    <a href="<?= home_url('/') ?>" class="logo">
      <img src="<?= esc_url(home_url('/')) ?>image/tarlypinheiro%20tarlypinheiro.com.br.png"
           alt="Tarly Pinheiro Imóveis" class="logo-img"
           onerror="this.style.display='none'">
      <div class="logo-text">
        <span class="logo-name">Tarly Pinheiro</span>
        <span class="logo-sub">Imóveis · <?= TI_CRECI ?></span>
      </div>
    </a>
    <div class="nav-links">
      <a href="<?= home_url('/#comprar') ?>"       class="nav-link">Comprar</a>
      <a href="<?= home_url('/#alugar') ?>"        class="nav-link">Alugar</a>
      <a href="<?= home_url('/#lancamentos') ?>"   class="nav-link">Lançamentos</a>
      <a href="<?= home_url('/#financiamento') ?>" class="nav-link">Financiamento</a>
    </div>
    <a href="javascript:void(0)" onclick="openLeadPopup(null)" class="nav-cta">📱 Contato</a>
    <div class="hamburger" onclick="document.getElementById('mobileMenu').classList.add('open')">
      <span></span><span></span><span></span>
    </div>
  </div>
</nav>

<!-- MOBILE MENU -->
<div class="mobile-menu" id="mobileMenu">
  <button class="mobile-close" onclick="document.getElementById('mobileMenu').classList.remove('open')">✕</button>
  <a href="<?= home_url('/#comprar') ?>"       class="nav-link" onclick="document.getElementById('mobileMenu').classList.remove('open')">Comprar</a>
  <a href="<?= home_url('/#alugar') ?>"        class="nav-link" onclick="document.getElementById('mobileMenu').classList.remove('open')">Alugar</a>
  <a href="<?= home_url('/#lancamentos') ?>"   class="nav-link" onclick="document.getElementById('mobileMenu').classList.remove('open')">Lançamentos</a>
  <a href="<?= home_url('/imoveis/') ?>"       class="nav-link" onclick="document.getElementById('mobileMenu').classList.remove('open')">Buscar Imóveis</a>
  <a href="javascript:void(0)" onclick="document.getElementById('mobileMenu').classList.remove('open');openLeadPopup(null)" class="nav-link">Contato</a>
</div>
