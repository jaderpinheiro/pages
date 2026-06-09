<?php
defined('ABSPATH') || exit;

// Parâmetros da busca
$busca   = sanitize_text_field($_GET['busca']   ?? '');
$negocio = sanitize_text_field($_GET['negocio'] ?? '');
$tipo    = sanitize_text_field($_GET['tipo']    ?? '');
$faixa   = sanitize_text_field($_GET['faixa']   ?? '');

$query = ti_query_imoveis(compact('busca','negocio','tipo','faixa'));

$seo_title = 'Busca de Imóveis' . ($busca ? ' — ' . $busca : '') . ($negocio ? ' para ' . $negocio : '');
$seo_desc  = 'Encontre imóveis em Goiânia e Aparecida de Goiânia. Tarly Pinheiro Imóveis. ' . TI_CRECI;

include TI_PATH . 'templates/partials/head.php';
include TI_PATH . 'templates/partials/topbar-nav.php';
?>

<!-- HERO MENOR DE BUSCA -->
<section class="search-hero">
  <div class="search-hero-inner">
    <p style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:var(--gold);margin-bottom:8px;">Busca de Imóveis</p>
    <h1 style="font-family:'Cormorant Garamond',serif;font-size:clamp(24px,3vw,36px);font-weight:600;color:var(--navy);margin-bottom:20px;">
      <?= $busca ? 'Resultados para "' . esc_html($busca) . '"' : 'Encontre seu imóvel ideal' ?>
    </h1>
    <!-- SEARCH BAR -->
    <form class="search-bar-inline" method="get" action="<?= home_url('/imoveis/') ?>">
      <div class="sbi-tabs">
        <?php foreach (['Comprar','Alugar','Lançamento'] as $tab): ?>
        <button type="button" class="sbi-tab<?= $negocio===$tab?' active':'' ?>" onclick="setSBITab(this,'<?= $tab ?>')"><?= $tab ?></button>
        <?php endforeach; ?>
        <input type="hidden" name="negocio" id="sbi-negocio" value="<?= esc_attr($negocio) ?>">
      </div>
      <div class="sbi-fields">
        <input type="text" name="busca" placeholder="Bairro, cidade, tipo..." value="<?= esc_attr($busca) ?>" class="sbi-input">
        <select name="tipo" class="sbi-select">
          <option value="">Todos os tipos</option>
          <?php foreach (['Casa','Apartamento','Casa de Condomínio','Cobertura','Flat','Terreno','Comercial','Chácara'] as $t): ?>
          <option value="<?= esc_attr($t) ?>" <?= selected($tipo,$t,false) ?>><?= esc_html($t) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="faixa" class="sbi-select">
          <option value="">Qualquer valor</option>
          <?php foreach (['Até R$ 300.000','R$ 300.000 – R$ 600.000','R$ 600.000 – R$ 1.000.000','Acima de R$ 1.000.000'] as $f): ?>
          <option value="<?= esc_attr($f) ?>" <?= selected($faixa,$f,false) ?>><?= esc_html($f) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="sbi-btn">
          <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          Buscar
        </button>
      </div>
    </form>
  </div>
</section>

<!-- RESULTADOS -->
<section class="section" style="padding:48px 0;">
  <div class="section-inner">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px;">
      <p style="font-size:14px;color:var(--text-muted);">
        <?= $query->found_posts ?> imóve<?= $query->found_posts == 1 ? 'l encontrado' : 'is encontrados' ?>
        <?php if ($busca || $negocio || $tipo): ?>
        <a href="<?= home_url('/imoveis/') ?>" style="color:var(--gold-dark);font-size:13px;margin-left:12px;">✕ Limpar filtros</a>
        <?php endif; ?>
      </p>
    </div>

    <?php if ($query->have_posts()): ?>
    <div class="cards-grid">
      <?php while ($query->have_posts()): $query->the_post();
        $id   = get_the_ID();
        $d    = ti_get_imovel_data($id);
        $price= $d['negocio']==='Aluguel' ? $d['valor_aluguel'] : $d['valor_venda'];
        $neg  = $d['negocio'] ?: 'Venda';
        $thumb= $d['thumb'] ?: '';
      ?>
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
            <button class="share-btn share-wa" title="Compartilhar" onclick="event.stopPropagation();shareCardWA(<?= $id ?>, '<?= esc_js($d['title']) ?>', '<?= esc_js(ti_fmt_price($price)) ?>', '<?= esc_url($d['permalink']) ?>')">
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
          <div class="card-price"><?= ti_fmt_price($price) ?></div>
        </div>
        <div class="card-footer">
          <button class="btn-card btn-card-primary" onclick="event.stopPropagation();openLeadPopup(<?= $id ?>)">Tenho interesse</button>
          <a href="<?= esc_url($d['permalink']) ?>" class="btn-card btn-card-outline" onclick="event.stopPropagation()">Ver detalhes →</a>
        </div>
      </div>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>

    <!-- PAGINAÇÃO -->
    <?php if ($query->max_num_pages > 1):
      $big = 999999;
      $pages = paginate_links(['base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
        'format' => '?paged=%#%', 'current' => max(1, get_query_var('paged')),
        'total' => $query->max_num_pages, 'type' => 'array', 'prev_text' => '←', 'next_text' => '→']);
      if ($pages): ?>
      <div class="ti-pagination">
        <?php foreach ($pages as $pg) echo $pg; ?>
      </div>
      <?php endif; endif; ?>

    <?php else: ?>
    <div style="text-align:center;padding:80px 24px;">
      <div style="font-size:48px;margin-bottom:16px;">🏠</div>
      <h2 style="font-family:'Cormorant Garamond',serif;font-size:28px;color:var(--navy);margin-bottom:12px;">Nenhum imóvel encontrado</h2>
      <p style="color:var(--text-muted);margin-bottom:24px;">Tente ajustar os filtros ou entre em contato para imóveis fora do catálogo online.</p>
      <button class="btn-search" onclick="openLeadPopup(null)" style="max-width:280px;margin:0 auto;">Falar com Tarly via WhatsApp</button>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- SEO REGIONS -->
<section class="regions-section">
  <div class="section-inner">
    <div style="text-align:center;margin-bottom:32px;">
      <p class="section-label" style="text-align:center;">Onde atuamos</p>
      <h2 class="section-title" style="color:white;text-align:center;">Principais Regiões de Goiânia</h2>
    </div>
    <div class="regions-grid">
      <?php
      $regioes = [
        'Setor Sul / Central'    => ['Setor Bueno','Setor Marista','Setor Oeste','Jardim Goiás','Setor Sul'],
        'Região Norte'           => ['Negrão de Lima','Vila Nova','Setor Norte Ferroviário','Jardim Atlântico','Residencial Itaipu'],
        'Região Leste'           => ['Setor Leste Universitário','Jardim Europa','Setor Pedro Ludovico','Setor Santos Dumont','Alphaville Goiânia'],
        'Região Sudoeste'        => ['Setor Sudoeste','Jardim Florença','Parque Amazônia','Setor Bela Vista','Setor Aeroporto'],
        'Aparecida de Goiânia'   => ['Setor Garavelo','Jardim Tiradentes','Residencial Paraíso','Setor Central','Buriti Real'],
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

<script>
function setSBITab(el, negocio) {
  document.querySelectorAll('.sbi-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('sbi-negocio').value = negocio;
}
function shareCardWA(id, title, price, url) {
  var msg = '🏠 *'+title+'*\n💰 '+price+'\n\nVeja mais: '+url+'\n\n*Tarly Pinheiro Imóveis | <?= TI_CRECI ?>*\n📱 <?= TI_PHONE_BR ?>';
  window.open('https://wa.me/?text='+encodeURIComponent(msg),'_blank');
}

// Lead popup WP
var _leadImovelWP = null;
function openLeadPopup(id) {
  _leadImovelWP = id;
  var info = document.getElementById('leadImovelInfo');
  info.style.display = id ? 'block' : 'none';
  document.getElementById('leadPopup').classList.add('active');
  document.getElementById('leadNome').focus();
}
function closeLeadPopup() { document.getElementById('leadPopup').classList.remove('active'); }
function submitLead() {
  var nome = document.getElementById('leadNome').value.trim();
  var tel  = document.getElementById('leadTelefone').value.trim();
  var email= document.getElementById('leadEmail').value.trim();
  if(!nome||!tel) { alert('Preencha nome e telefone.'); return; }

  if(typeof ti_ajax !== 'undefined') {
    fetch(ti_ajax.url, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: new URLSearchParams({action:'ti_save_lead', nonce:ti_ajax.nonce,
        nome, telefone:tel, email, imovel_id: _leadImovelWP||0, imovel_nome:''})
    });
  }
  var msg = 'Olá Tarly! Me chamo '+nome+'. Encontrei seu site e gostaria de informações sobre imóveis. Meu celular: '+tel;
  closeLeadPopup();
  window.open('https://wa.me/<?= TI_PHONE ?>?text='+encodeURIComponent(msg),'_blank');
}

// Reveal on scroll
var obs = new IntersectionObserver(function(entries){
  entries.forEach(function(e){ if(e.isIntersecting){e.target.classList.add('visible');obs.unobserve(e.target);} });
},{threshold:0.1});
document.querySelectorAll('.reveal').forEach(function(el){obs.observe(el);});

// Nav scroll
window.addEventListener('scroll', function(){
  var n = document.getElementById('mainNav');
  if(n) n.classList.toggle('scrolled', window.scrollY > 40);
});
</script>

<?php include TI_PATH . 'templates/partials/footer.php'; ?>
