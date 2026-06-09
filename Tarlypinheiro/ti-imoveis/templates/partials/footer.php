<?php defined('ABSPATH') || exit; ?>
<!-- FOOTER -->
<footer id="contato">
  <div class="footer-top">
    <div class="footer-brand">
      <div class="logo">
        <img src="<?= esc_url(home_url('/')) ?>image/tarlypinheiro%20tarlypinheiro.com.br.png"
             alt="Tarly Pinheiro Imóveis" class="logo-img" onerror="this.style.display='none'">
        <div class="logo-text">
          <span class="logo-name">Tarly Pinheiro</span>
          <span class="logo-sub">Imóveis · <?= ti_get_creci() ?></span>
        </div>
      </div>
      <p class="footer-desc">Mais de 15 anos conectando famílias e empresas aos melhores imóveis de Goiânia e região. Transparência, expertise e dedicação em cada negociação.</p>
      <div class="footer-contact-item"><span>📞</span> <a href="tel:+<?= ti_get_phone_wa() ?>" style="color:rgba(255,255,255,0.7);text-decoration:none;"><?= ti_get_phone_display() ?></a></div>
      <div class="footer-contact-item"><span>✉</span> <?= esc_html(get_option('ti_site_email','contato@tarlypinheiro.com.br')) ?></div>
      <div class="footer-contact-item"><span>📍</span> Goiânia – GO</div>
      <div class="footer-contact-item"><span class="creci-badge" style="font-size:11px;"><?= ti_get_creci() ?></span></div>
      <div class="footer-social">
        <a href="https://www.instagram.com/tarlypinheiroimoveis/" target="_blank" rel="noopener" class="social-btn" title="Instagram @tarlypinheiroimoveis" aria-label="Instagram">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
        </a>
        <a href="https://www.facebook.com/tarlypinheirobroker" target="_blank" rel="noopener" class="social-btn" title="Facebook tarlypinheirobroker" aria-label="Facebook">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
        </a>
        <a href="https://wa.me/<?= ti_get_phone_wa() ?>" target="_blank" rel="noopener" class="social-btn" title="WhatsApp" aria-label="WhatsApp">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/></svg>
        </a>
      </div>
    </div>
    <div>
      <p class="footer-h">Navegação</p>
      <div class="footer-links">
        <a href="<?= home_url('/#comprar') ?>">Comprar imóvel</a>
        <a href="<?= home_url('/#alugar') ?>">Alugar imóvel</a>
        <a href="<?= home_url('/#lancamentos') ?>">Lançamentos</a>
        <a href="<?= home_url('/imoveis/') ?>">Buscar Imóveis</a>
        <a href="javascript:void(0)" onclick="openLeadPopup(null)">Falar no WhatsApp</a>
      </div>
    </div>
    <div>
      <p class="footer-h">Regiões</p>
      <div class="footer-links">
        <a href="<?= home_url('/imoveis/?busca=Setor+Bueno') ?>">Setor Bueno</a>
        <a href="<?= home_url('/imoveis/?busca=Setor+Marista') ?>">Setor Marista</a>
        <a href="<?= home_url('/imoveis/?busca=Jardim+Goias') ?>">Jardim Goiás</a>
        <a href="<?= home_url('/imoveis/?busca=Negrao+de+Lima') ?>">Negrão de Lima</a>
        <a href="<?= home_url('/imoveis/?busca=Aparecida+de+Goiania') ?>">Aparecida de Goiânia</a>
        <a href="<?= home_url('/imoveis/?busca=Alphaville') ?>">Alphaville Goiânia</a>
      </div>
    </div>
    <div>
      <p class="footer-h">Newsletter</p>
      <p style="font-size:13px;color:rgba(255,255,255,0.5);margin-bottom:16px;">Receba os melhores imóveis antes de todos.</p>
      <div class="footer-newsletter">
        <input type="email" id="footer-email" placeholder="Seu melhor e-mail">
        <button class="btn-newsletter" onclick="footerSubscribe()">Quero receber novidades</button>
      </div>
      <p style="font-size:11px;color:rgba(255,255,255,0.3);margin-top:12px;">Não enviamos spam. Cancele quando quiser.</p>
    </div>
  </div>
  <div class="footer-bottom">
    <p>© <?= date('Y') ?> Tarly Pinheiro Imóveis | <?= TI_CRECI ?> | Todos os direitos reservados |
      <a href="#">Política de Privacidade</a> | <a href="#">Termos de Uso</a></p>
    <p class="dev-credit">Desenvolvido por <strong>Inline Digital &amp; Tecnologia</strong></p>
  </div>
</footer>

<!-- LEAD POPUP -->
<div id="leadPopup" class="lead-popup" onclick="if(event.target===this)closeLeadPopup()">
  <div class="lead-popup-box">
    <button class="lead-popup-close" onclick="closeLeadPopup()">✕</button>
    <h3>Falar com Tarly</h3>
    <p>Preencha seus dados e entraremos em contato agora mesmo</p>
    <div id="leadImovelInfo" class="lead-imovel-info"></div>
    <input type="text"  id="leadNome"     placeholder="Seu nome completo *">
    <input type="tel"   id="leadTelefone" placeholder="<?= TI_PHONE_BR ?> *" oninput="tiMaskPhone(this)" maxlength="16">
    <input type="email" id="leadEmail"    placeholder="Seu e-mail (opcional)">
    <button class="btn-lead-submit" onclick="submitLead()">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/></svg>
      Falar no WhatsApp agora
    </button>
    <p style="font-size:11px;color:var(--text-muted);text-align:center;margin-top:10px;">Seus dados são 100% seguros.</p>
  </div>
</div>

<!-- FLOATING WHATSAPP -->
<div class="wa-float" onclick="openLeadPopup(null)" title="Falar com Tarly" role="button" tabindex="0">
  <span class="wa-label">Falar agora</span>
  <div class="wa-float-btn">
    <svg width="26" height="26" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
  </div>
</div>

<script>
function footerSubscribe() {
  var email = document.getElementById('footer-email').value.trim();
  if (!email) return;
  openLeadPopup(null);
  document.getElementById('leadEmail').value = email;
}
</script>

<?php wp_footer(); ?>
</body>
</html>
