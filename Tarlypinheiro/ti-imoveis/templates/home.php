<?php
/**
 * Template: Home page — carrega imóveis reais do CPT WordPress.
 * Servido pelo plugin quando is_front_page() = true.
 */
defined('ABSPATH') || exit;

// ── Queries ────────────────────────────────────────────────────────────────────
// Destaque: com meta _ti_destaque=1; se vazio, mostra os mais recentes
$q_destaque = ti_query_imoveis(['destaque' => '1', 'limit' => 4]);
if ( ! $q_destaque->have_posts() ) {
    $q_destaque = ti_query_imoveis(['limit' => 4]);
}

// Novos: tenta filtrar por "Venda"; se vazio, mostra todos publicados
$q_novos = ti_query_imoveis(['negocio' => 'Venda', 'limit' => 6]);
if ( ! $q_novos->have_posts() ) {
    $q_novos = ti_query_imoveis(['limit' => 6]);
}

// Lançamentos: filtra por taxonomia (pode ficar vazio)
$q_lancamentos = ti_query_imoveis(['negocio' => 'Lançamento', 'limit' => 4]);

// Instagram posts dinâmicos
$ig_posts = ti_get_social_posts('instagram', 8);

$seo_title = 'Tarly Pinheiro Imóveis | ' . TI_CRECI . ' | Goiânia';
$seo_desc  = 'Os melhores imóveis em Goiânia e Aparecida de Goiânia. Compre, alugue ou encontre lançamentos exclusivos. ' . TI_CRECI;

include TI_PATH . 'templates/partials/head.php';
include TI_PATH . 'templates/partials/topbar-nav.php';

// Helper local para card HTML
function _ti_card($id) {
    $d     = ti_get_imovel_data($id);
    $price = $d['negocio'] === 'Aluguel' ? $d['valor_aluguel'] : $d['valor_venda'];
    $neg   = $d['negocio'] ?: 'Venda';
    $thumb = $d['thumb'];
    $fmt   = $price ? 'R$ ' . number_format((float)$price, 0, ',', '.') : 'Consulte';
    ob_start(); ?>
    <div class="card reveal" onclick="window.location='<?= esc_url($d['permalink']) ?>'">
      <div class="card-img">
        <?php if ($thumb): ?>
        <img src="<?= esc_url($thumb) ?>" alt="<?= esc_attr($d['title']) ?>" loading="lazy">
        <?php else: ?>
        <div class="card-img-placeholder">
          <svg width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="rgba(100,130,160,0.3)" stroke-width="1"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        </div>
        <?php endif; ?>
        <span class="card-badge<?= $d['destaque'] ? ' gold' : '' ?>"><?= esc_html($neg) ?></span>
        <div class="card-share">
          <button class="share-btn share-wa" title="Compartilhar" onclick="event.stopPropagation();tiShareWA(<?= $id ?>, '<?= esc_js($d['title']) ?>', '<?= esc_js($fmt) ?>', '<?= esc_url($d['permalink']) ?>')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/></svg>
          </button>
        </div>
      </div>
      <div class="card-body">
        <div class="card-title"><?= esc_html($d['title']) ?></div>
        <div class="card-loc">📍 <?= esc_html("{$d['bairro']}, {$d['cidade']}") ?></div>
        <div class="card-features">
          <?php if ($d['metragem']): ?><span class="feat">📐 <?= esc_html($d['metragem']) ?> m²</span><?php endif; ?>
          <?php if ($d['quartos']): ?><span class="feat">🛏 <?= esc_html($d['quartos']) ?></span><?php endif; ?>
          <?php if ($d['banheiros']): ?><span class="feat">🚿 <?= esc_html($d['banheiros']) ?></span><?php endif; ?>
          <?php if ($d['vagas']): ?><span class="feat">🚗 <?= esc_html($d['vagas']) ?></span><?php endif; ?>
        </div>
        <div class="card-price"><?= esc_html($fmt) ?></div>
      </div>
      <div class="card-footer">
        <button class="btn-card btn-card-primary" onclick="event.stopPropagation();openLeadPopup(<?= $id ?>)">Tenho interesse</button>
        <a href="<?= esc_url($d['permalink']) ?>" class="btn-card btn-card-outline" onclick="event.stopPropagation()">Ver detalhes →</a>
      </div>
    </div>
    <?php return ob_get_clean();
}
?>

<!-- HERO -->
<section class="hero">
  <div class="hero-left">
    <div class="hero-tag">Especialista em Goiânia</div>
    <h1 class="hero-h1">O imóvel que você<br>sempre sonhou está<br><em>mais perto</em> do que<br>você imagina.</h1>
    <p class="hero-sub">Com expertise, dedicação e o melhor portfólio de imóveis em Goiânia e região, transformamos a busca pelo lar ideal em uma jornada segura e inesquecível.</p>
    <div class="hero-stats">
      <div class="stat"><span class="stat-num">+1.200</span><span class="stat-label">Imóveis negociados</span></div>
      <div class="stat"><span class="stat-num">15+</span><span class="stat-label">Anos de mercado</span></div>
      <div class="stat"><span class="stat-num">98%</span><span class="stat-label">Clientes satisfeitos</span></div>
    </div>
  </div>
  <div class="hero-right">
    <div class="search-box">
      <p class="search-title">A chance de encontrar o imóvel dos sonhos <em style="font-style:normal;color:var(--gold)">agora!</em></p>
      <p class="search-subtitle">Busque entre centenas de opções exclusivas</p>
      <form action="<?= home_url('/imoveis/') ?>" method="get" onsubmit="return homeSearchSubmit(this)">
        <input type="hidden" name="negocio" id="homeSearchNegocio" value="Comprar">
        <div class="search-tabs">
          <button type="button" class="stab active" onclick="setHomeTab(this,'Comprar')">Comprar</button>
          <button type="button" class="stab" onclick="setHomeTab(this,'Alugar')">Alugar</button>
          <button type="button" class="stab" onclick="setHomeTab(this,'Lançamento')">Imóvel novo</button>
        </div>
        <div class="search-field">
          <label>Onde deseja morar?</label>
          <input type="text" name="busca" placeholder="Bairro, cidade ou região...">
        </div>
        <div class="search-field">
          <label>Tipo de imóvel</label>
          <select name="tipo">
            <option value="">Todos os imóveis</option>
            <option>Casa</option><option>Apartamento</option><option>Casa de Condomínio</option>
            <option>Cobertura</option><option>Flat</option><option>Terreno</option>
            <option>Comercial</option><option>Chácara</option>
          </select>
        </div>
        <div class="search-field">
          <label>Faixa de preço</label>
          <select name="faixa">
            <option value="">Qualquer valor</option>
            <option>Até R$ 300.000</option>
            <option>R$ 300.000 – R$ 600.000</option>
            <option>R$ 600.000 – R$ 1.000.000</option>
            <option>Acima de R$ 1.000.000</option>
          </select>
        </div>
        <button type="submit" class="btn-search">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          Buscar Imóvel
        </button>
      </form>
    </div>
  </div>
</section>

<!-- DESTAQUE & OPORTUNIDADES -->
<section class="section" id="comprar">
  <div class="section-inner">
    <div class="section-header reveal">
      <div>
        <p class="section-label">Seleção exclusiva</p>
        <h2 class="section-title">Imóveis em Destaque &amp; Oportunidades</h2>
      </div>
      <a href="<?= home_url('/imoveis/?negocio=Venda') ?>" class="section-link">Ver todos →</a>
    </div>
    <div class="cards-grid">
      <?php if ($q_destaque->have_posts()):
        while ($q_destaque->have_posts()) { $q_destaque->the_post(); echo _ti_card(get_the_ID()); }
        wp_reset_postdata();
      else: ?>
      <p style="color:var(--text-muted);grid-column:1/-1;text-align:center;padding:48px 0;">
        Nenhum imóvel em destaque. <a href="<?= admin_url('post-new.php?post_type=imovel') ?>" style="color:var(--gold-dark);">Adicionar imóvel →</a>
      </p>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- NOVOS ENTREGUES -->
<section class="section" style="background:var(--blue-light);" id="alugar">
  <div class="section-inner">
    <div class="section-header reveal">
      <div>
        <p class="section-label">Prontos para morar</p>
        <h2 class="section-title">Imóveis para Venda</h2>
      </div>
      <a href="<?= home_url('/imoveis/?negocio=Venda') ?>" class="section-link">Ver todos →</a>
    </div>
    <div class="cards-grid">
      <?php if ($q_novos->have_posts()):
        while ($q_novos->have_posts()) { $q_novos->the_post(); echo _ti_card(get_the_ID()); }
        wp_reset_postdata();
      else: ?>
      <p style="color:var(--text-muted);grid-column:1/-1;text-align:center;padding:48px 0;">Imóveis em breve.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- LANÇAMENTOS -->
<section class="section" id="lancamentos">
  <div class="section-inner">
    <div class="section-header reveal">
      <div>
        <p class="section-label">Pré-lançamento</p>
        <h2 class="section-title">Lançamentos Exclusivos</h2>
      </div>
      <a href="<?= home_url('/imoveis/?negocio=Lançamento') ?>" class="section-link">Ver todos →</a>
    </div>
    <div class="cards-grid">
      <?php if ($q_lancamentos->have_posts()):
        while ($q_lancamentos->have_posts()) { $q_lancamentos->the_post(); echo _ti_card(get_the_ID()); }
        wp_reset_postdata();
      else: ?>
      <p style="color:var(--text-muted);grid-column:1/-1;text-align:center;padding:48px 0;">Lançamentos em breve.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- INSTAGRAM / REDES SOCIAIS -->
<section class="ig-section" id="financiamento">
  <div class="section-inner">
    <div class="section-header reveal">
      <div>
        <p class="section-label">@tarlypinheiro.imoveis</p>
        <h2 class="section-title">Inspirações &amp; Novidades no Instagram</h2>
      </div>
      <a href="https://instagram.com/tarlypinheiro.imoveis" class="section-link" target="_blank" rel="noopener">Seguir →</a>
    </div>
    <?php
    // Separar posts com embed de posts com imagem
    $embed_posts = array_filter($ig_posts, fn($p) => !empty($p->embed_html));
    $image_posts = array_filter($ig_posts, fn($p)  => empty($p->embed_html));
    ?>

    <?php if ($embed_posts): ?>
    <!-- Embeds nativos (YouTube, TikTok, Instagram iframe, etc.) -->
    <div class="ti-social-embeds">
      <?php foreach ($embed_posts as $post): ?>
      <div class="ti-social-embed-item">
        <div class="ti-social-embed-wrap">
          <?= $post->embed_html /* embed já sanitizado no save */ ?>
        </div>
        <?php if ($post->legenda): ?>
        <p class="ti-social-embed-caption"><?= esc_html(mb_substr($post->legenda, 0, 120)) ?></p>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="ig-grid">
      <?php if ($image_posts): foreach ($image_posts as $post):
        $img_url = $post->image_id ? wp_get_attachment_image_url((int)$post->image_id, 'medium') : '';
        $link    = $post->link ?: 'https://instagram.com/tarlypinheiro.imoveis';
        $alt     = $post->alt_text ?: $post->legenda ?: 'Tarly Pinheiro Imóveis';
        ?>
        <a class="ig-card" href="<?= esc_url($link) ?>" target="_blank" rel="noopener" title="<?= esc_attr($alt) ?>">
          <?php if ($img_url): ?>
          <img src="<?= esc_url($img_url) ?>" alt="<?= esc_attr($alt) ?>" loading="lazy">
          <?php else: ?>
          <div class="ig-placeholder" style="background:linear-gradient(135deg,var(--blue-light),var(--cream));">
            <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="#B8D0E8" stroke-width="1.5"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4" fill="none" stroke="#B8D0E8" stroke-width="1.5"/><circle cx="17.5" cy="6.5" r="1.2" fill="#B8D0E8"/></svg>
            <span>Instagram</span>
          </div>
          <?php endif; ?>
          <div class="ig-overlay">
            <span><?= esc_html(mb_substr($post->legenda ?: 'Ver no Instagram', 0, 60)) ?></span>
          </div>
        </a>
      <?php endforeach; elseif (!$embed_posts):
        $ph = [['#E8F0F7','#C8DFF0'],['#F5F0E8','#EAD8A8'],['#E8F0F7','#D4EAD4'],['#F0E8F5','#D8B8E8'],['#FCEEE8','#F8C8B0'],['#E8F5F0','#A8D8C8'],['#E8ECF8','#B8C4E8'],['#F8F0E8','#E8D098']];
        foreach ($ph as [$c1,$c2]): ?>
        <a class="ig-card" href="https://instagram.com/tarlypinheiro.imoveis" target="_blank" rel="noopener">
          <div class="ig-placeholder" style="background:linear-gradient(135deg,<?= $c1 ?>,<?= $c2 ?>);">
            <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="#B8D0E8" stroke-width="1.5"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4" fill="none" stroke="#B8D0E8" stroke-width="1.5"/></svg>
            <span>Ver no Instagram</span>
          </div>
          <div class="ig-overlay"><span>🏠 Tarly Pinheiro Imóveis</span></div>
        </a>
        <?php endforeach; endif; ?>
    </div>
    <p style="text-align:center;margin-top:24px;">
      <a href="https://instagram.com/tarlypinheiro.imoveis" class="btn-search" style="max-width:240px;margin:0 auto;text-decoration:none;" target="_blank" rel="noopener">
        Seguir no Instagram →
      </a>
    </p>
  </div>
</section>

<!-- REGIÕES SEO -->
<section class="regions-section">
  <div class="section-inner">
    <div style="margin-bottom:40px;text-align:center;">
      <p class="section-label" style="text-align:center;">Onde atuamos</p>
      <h2 class="section-title" style="color:white;text-align:center;">Principais Regiões de Goiânia</h2>
    </div>
    <div class="regions-grid">
      <?php
      $regioes = [
        'Setor Sul / Central'  => ['Setor Bueno','Setor Marista','Setor Oeste','Jardim Goiás','Setor Sul'],
        'Região Norte'         => ['Negrão de Lima','Vila Nova','Setor Norte Ferroviário','Jardim Atlântico','Residencial Itaipu'],
        'Região Leste'         => ['Setor Leste Universitário','Jardim Europa','Setor Pedro Ludovico','Setor Santos Dumont','Alphaville Goiânia'],
        'Região Sudoeste'      => ['Setor Sudoeste','Jardim Florença','Parque Amazônia','Setor Bela Vista','Setor Aeroporto'],
        'Aparecida de Goiânia' => ['Setor Garavelo','Jardim Tiradentes','Residencial Paraíso','Setor Central','Buriti Real'],
      ];
      foreach ($regioes as $cidade => $bairros): ?>
      <div class="region-col">
        <div class="region-city"><?= esc_html($cidade) ?></div>
        <div class="region-links">
          <?php foreach ($bairros as $b): ?>
          <a href="<?= home_url('/imoveis/?busca=' . urlencode($b)) ?>" class="region-link"><?= esc_html($b) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- SEO PHRASES -->
<section class="seo-phrases">
  <div class="section-inner">
    <div style="text-align:center;margin-bottom:28px;">
      <p class="section-label" style="text-align:center;">Encontre exatamente o que procura</p>
    </div>
    <div class="seo-phrases-grid">
      <?php
      $phrases = [
        'Casa à venda no Negrão de Lima',
        'Apartamento no Setor Bueno',
        'Imóveis em Aparecida de Goiânia',
        'Casa com piscina em Goiânia',
        'Apartamento 3 quartos no Marista',
        'Terreno à venda no Sudoeste',
        'Imóvel para alugar em Goiânia',
        'Lançamento no Jardim Goiás',
      ];
      foreach ($phrases as $ph): ?>
      <a href="<?= home_url('/imoveis/?busca=' . urlencode($ph)) ?>" class="seo-phrase"><?= esc_html($ph) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<script>
function setHomeTab(el, negocio) {
  document.querySelectorAll('.stab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('homeSearchNegocio').value = negocio;
}
function homeSearchSubmit(form) {
  // Remove selects com valor vazio para URL limpa
  Array.from(form.elements).forEach(el => {
    if ((el.tagName === 'SELECT' || el.tagName === 'INPUT') && !el.value) el.disabled = true;
  });
  return true;
}
function tiShareWA(id, title, price, url) {
  var msg = '🏠 *'+title+'*\n💰 '+price+'\n\nVeja mais: '+url+'\n\n*Tarly Pinheiro Imóveis | <?= TI_CRECI ?>*\n📱 <?= TI_PHONE_BR ?>';
  window.open('https://wa.me/?text='+encodeURIComponent(msg),'_blank');
}
// Nav scroll
window.addEventListener('scroll', function() {
  var n = document.getElementById('mainNav');
  if (n) n.classList.toggle('scrolled', window.scrollY > 40);
});
// Reveal on scroll
var obs = new IntersectionObserver(function(entries){
  entries.forEach(function(e){ if(e.isIntersecting){e.target.classList.add('visible');obs.unobserve(e.target);} });
},{threshold:0.1,rootMargin:'0px 0px -40px 0px'});
setTimeout(function(){ document.querySelectorAll('.reveal').forEach(function(el){obs.observe(el);}); }, 100);
</script>

<?php include TI_PATH . 'templates/partials/footer.php'; ?>
