<?php
defined('ABSPATH') || exit;
if ( ! have_posts() ) { wp_redirect( home_url('/') ); exit; }
the_post();

$d         = ti_get_imovel_data(get_the_ID());
$fotos     = $d['gallery']; // inclui thumb + galeria
$price_val = $d['negocio'] === 'Aluguel' ? $d['valor_aluguel'] : $d['valor_venda'];
$price_lbl = $d['negocio'] === 'Aluguel' ? 'Aluguel/mês' : 'Venda';
$neg_lbl   = $d['negocio'] ?: 'Venda';

$seo_title = $d['title'];
$seo_desc  = $d['descricao'] ? wp_trim_words($d['descricao'], 30) : "{$d['title']} — {$d['bairro']}, {$d['cidade']}";
$seo_image = $fotos[0] ?? '';

include TI_PATH . 'templates/partials/head.php';
include TI_PATH . 'templates/partials/topbar-nav.php';

// Upsell: imóveis da mesma categoria
$q_upsell = new WP_Query([
    'post_type'      => 'imovel',
    'post_status'    => 'publish',
    'posts_per_page' => 6,
    'post__not_in'   => [$d['id']],
    'tax_query'      => $d['negocio'] ? [['taxonomy'=>'negocio','field'=>'name','terms'=>$d['negocio']]] : [],
    'no_found_rows'  => true,
]);
?>

<!-- GALERIA HERO ─────────────────────────────────────────────────────────── -->
<div class="ti-gallery-hero">
  <div class="ti-gallery-main" id="tiGalleryMain">
    <a href="<?= home_url('/imoveis/') ?>" class="detail-back">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
      Voltar à busca
    </a>

    <?php if ($fotos): ?>
      <?php foreach ($fotos as $i => $url): ?>
      <div class="ti-gal-slide<?= $i === 0 ? ' active' : '' ?>" data-index="<?= $i ?>">
        <img src="<?= esc_url($url) ?>"
             alt="<?= esc_attr($d['title']) ?> — foto <?= $i + 1 ?>"
             loading="<?= $i === 0 ? 'eager' : 'lazy' ?>"
             onclick="tiOpenLightbox(<?= $i ?>)">
      </div>
      <?php endforeach; ?>

      <?php if (count($fotos) > 1): ?>
      <button class="ti-gal-btn ti-gal-prev" onclick="tiGalPrev()" aria-label="Foto anterior">&#8249;</button>
      <button class="ti-gal-btn ti-gal-next" onclick="tiGalNext()" aria-label="Próxima foto">&#8250;</button>
      <div class="ti-gal-counter">
        <span id="tiGalCurrent">1</span>/<?= count($fotos) ?>
      </div>
      <?php endif; ?>

      <button class="ti-gal-fullscreen" onclick="tiOpenLightbox(tiCurrentSlide)" title="Tela cheia">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
        </svg>
      </button>

    <?php else: ?>
      <div style="width:100%;height:100%;background:linear-gradient(135deg,var(--blue-light),var(--cream));display:flex;align-items:center;justify-content:center;">
        <svg width="64" height="64" fill="none" viewBox="0 0 24 24" stroke="rgba(100,130,160,0.25)" stroke-width="1">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
      </div>
    <?php endif; ?>
  </div>

  <!-- MINIATURAS ─────────────────────────────────────────────────────────── -->
  <?php if (count($fotos) > 1): ?>
  <div class="ti-gallery-thumbs" id="tiGalleryThumbs">
    <?php foreach ($fotos as $i => $url): ?>
    <div class="ti-gal-thumb<?= $i === 0 ? ' active' : '' ?>" onclick="tiGoToSlide(<?= $i ?>)">
      <img src="<?= esc_url($url) ?>" alt="Foto <?= $i + 1 ?>" loading="lazy">
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- LIGHTBOX ──────────────────────────────────────────────────────────────── -->
<div class="ti-lightbox" id="tiLightbox"
     onclick="if(event.target===this)tiCloseLightbox()"
     role="dialog" aria-label="Visualização ampliada da foto">
  <button class="ti-lb-close" onclick="tiCloseLightbox()" aria-label="Fechar">✕</button>
  <button class="ti-lb-btn ti-lb-prev" onclick="tiLbPrev()" aria-label="Anterior">&#8249;</button>
  <img class="ti-lb-img" id="tiLbImg" src="" alt="">
  <button class="ti-lb-btn ti-lb-next" onclick="tiLbNext()" aria-label="Próxima">&#8250;</button>
  <div class="ti-lb-counter" id="tiLbCounter">1/1</div>
  <button class="ti-lb-back" onclick="tiCloseLightbox()">← Voltar ao imóvel</button>
</div>

<!-- DETALHE BODY ──────────────────────────────────────────────────────────── -->
<div class="detail-body">
  <!-- COLUNA PRINCIPAL -->
  <div class="detail-main">
    <div class="detail-badge-row">
      <span class="card-badge<?= $d['destaque'] ? ' gold' : '' ?>"><?= esc_html($neg_lbl) ?></span>
      <?php if ($d['tipo']): ?>
      <span class="card-badge" style="background:var(--blue-light);color:var(--slate);"><?= esc_html($d['tipo']) ?></span>
      <?php endif; ?>
      <?php if ($d['status'] !== 'disponivel'):
        $st_colors = ['vendido'=>'#DC2626','alugado'=>'#D97706','reservado'=>'#2563EB']; ?>
      <span class="card-badge" style="background:<?= $st_colors[$d['status']] ?? '#666' ?>;color:white;">
        <?= ucfirst(esc_html($d['status'])) ?>
      </span>
      <?php endif; ?>
    </div>

    <h1 class="detail-title"><?= esc_html($d['title']) ?></h1>
    <p class="detail-address">
      📍 <?= esc_html(trim("{$d['rua']}, {$d['numero']} — {$d['bairro']}, {$d['cidade']} - {$d['estado']}", " ,—")) ?>
    </p>

    <!-- CARACTERÍSTICAS ──────────────────────────────────────────────────── -->
    <div class="detail-features">
      <?php $feats = [
        [$d['metragem'], 'm²',   '📐', 'Área'],
        [$d['quartos'],  '',     '🛏',  $d['suites'] ? "Quartos ({$d['suites']} suítes)" : 'Quartos'],
        [$d['banheiros'],'',    '🚿',  'Banheiros'],
        [$d['vagas'],    '',     '🚗',  'Vagas'],
      ];
      foreach ($feats as [$val, $sfx, $icon, $lbl]):
        if (!$val) continue; ?>
      <div class="detail-feat">
        <div class="detail-feat-icon"><?= $icon ?></div>
        <div class="detail-feat-val"><?= esc_html($val . $sfx) ?></div>
        <div class="detail-feat-label"><?= esc_html($lbl) ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- COMODIDADES ──────────────────────────────────────────────────────── -->
    <?php $amens = [];
    $amen_map = [
      'aceita_anim'=>'Aceita Animais','lavanderia'=>'Lavanderia','area_serv'=>'Área de Serviço',
      'lavabo'=>'Lavabo','piscina'=>'Piscina','academia'=>'Academia','churrasq'=>'Churrasqueira'
    ];
    foreach ($amen_map as $key => $label) { if ($d[$key]) $amens[] = $label; }
    if ($amens): ?>
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:28px;">
      <?php foreach ($amens as $a): ?>
      <span style="background:var(--blue-light);padding:6px 14px;border-radius:20px;font-size:12px;color:var(--slate);">✓ <?= esc_html($a) ?></span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- DESCRIÇÃO ────────────────────────────────────────────────────────── -->
    <?php if ($d['descricao']): ?>
    <h2 class="detail-desc-title">Sobre o Imóvel</h2>
    <div class="detail-desc"><?= nl2br(esc_html($d['descricao'])) ?></div>
    <?php endif; ?>

    <!-- INFORMAÇÕES ──────────────────────────────────────────────────────── -->
    <div style="margin-top:28px;padding:16px 20px;background:var(--blue-light);border-radius:12px;font-size:13px;color:var(--slate-light);line-height:1.8;">
      <?php if ($d['cod_anunc']): ?>
      <span>Código do anunciante: <strong style="color:var(--slate)"><?= esc_html($d['cod_anunc']) ?></strong></span>
      <?php endif; ?>
      <?php if ($d['cod_viva']): ?>
      <span style="margin-left:16px;">Cód. Viva Real: <strong style="color:var(--slate)"><?= esc_html($d['cod_viva']) ?></strong></span>
      <?php endif; ?>
      <p style="margin-top:6px;">Publicado em <?= esc_html($d['publicado']) ?>.</p>
      <small>⚠️ Valores de condomínio e IPTU podem sofrer alteração. Sujeito à confirmação de disponibilidade.</small>
    </div>

    <!-- SEGURANÇA ────────────────────────────────────────────────────────── -->
    <div style="margin-top:16px;padding:16px 20px;border:1px solid var(--border);border-radius:12px;">
      <strong style="color:var(--navy);font-size:14px;">🔒 Segurança em primeiro lugar!</strong>
      <ul style="margin-top:8px;padding-left:20px;font-size:13px;color:var(--slate);line-height:1.8;">
        <li>Nunca transfira dinheiro sem visitar o imóvel e verificar a documentação</li>
        <li>Não compartilhe seus dados pessoais fora deste canal oficial</li>
        <li>B&G Imóveis — Tarly Pinheiro Imóveis — <?= TI_CRECI ?></li>
      </ul>
    </div>
  </div>

  <!-- SIDEBAR ──────────────────────────────────────────────────────────── -->
  <div class="detail-sidebar">
    <div class="price-card">
      <?php if ($price_val): ?>
      <div class="price-label"><?= esc_html($price_lbl) ?></div>
      <div class="price-big"><?= ti_fmt_price($price_val) ?></div>
      <?php if ($d['valor_cond'] || $d['valor_iptu']): ?>
      <div class="price-cond">
        <?= $d['valor_cond'] ? 'Cond. ' . ti_fmt_price($d['valor_cond']) : '' ?>
        <?= $d['valor_iptu'] ? ' · IPTU ' . ti_fmt_price($d['valor_iptu']) . '/mês' : '' ?>
      </div>
      <?php endif; ?>
      <?php else: ?>
      <div class="price-label">Valor</div>
      <div class="price-big" style="font-size:20px;">Consulte-nos</div>
      <?php endif; ?>

      <div style="padding:14px 0 16px;border-top:1px solid var(--border);margin-top:16px;">
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:4px;">📞 <?= TI_PHONE_BR ?></div>
        <div style="font-size:11px;color:var(--text-muted);"><?= TI_CRECI ?></div>
      </div>

      <button class="btn-wa-detail" onclick="openLeadPopup(<?= (int)$d['id'] ?>)">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/></svg>
        Tenho interesse
      </button>
      <button class="btn-share-detail"
              onclick="tiShareWADetail(<?= (int)$d['id'] ?>, '<?= esc_js($d['title']) ?>', '<?= esc_js(ti_fmt_price($price_val)) ?>', '<?= esc_url(get_permalink()) ?>')">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/>
          <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
        </svg>
        Compartilhar
      </button>
    </div>
  </div>
</div>

<!-- UPSELL — IMÓVEIS SIMILARES ─────────────────────────────────────────────── -->
<?php if ($q_upsell->have_posts()): ?>
<section class="ti-upsell">
  <div class="section-inner">
    <div class="section-header reveal" style="margin-bottom:24px;">
      <div>
        <p class="section-label">Você também pode gostar</p>
        <h2 class="section-title">Imóveis Similares</h2>
      </div>
      <a href="<?= home_url('/imoveis/?negocio=' . urlencode($d['negocio'])) ?>" class="section-link">Ver todos →</a>
    </div>
    <div class="ti-carousel-wrap">
      <button class="ti-carousel-btn ti-carousel-prev" onclick="tiCarouselScroll('upsellCarousel',-1)" aria-label="Anterior">&#8249;</button>
      <div class="ti-carousel" id="upsellCarousel">
        <?php while ($q_upsell->have_posts()): $q_upsell->the_post();
          $u     = ti_get_imovel_data(get_the_ID());
          $uprice= $u['negocio']==='Aluguel' ? $u['valor_aluguel'] : $u['valor_venda'];
        ?>
        <div class="card" style="width:280px;flex-shrink:0;" onclick="window.location='<?= esc_url($u['permalink']) ?>'">
          <div class="card-img" style="height:170px;">
            <?php if ($u['thumb']): ?>
            <img src="<?= esc_url($u['thumb']) ?>" alt="<?= esc_attr($u['title']) ?>" loading="lazy">
            <?php else: ?>
            <div class="card-img-placeholder"><svg width="36" height="36" fill="none" viewBox="0 0 24 24" stroke="rgba(100,130,160,0.3)" stroke-width="1"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
            <?php endif; ?>
            <span class="card-badge<?= $u['destaque']?' gold':'' ?>"><?= esc_html($u['negocio']?:'Venda') ?></span>
          </div>
          <div class="card-body">
            <div class="card-title" style="font-size:15px;"><?= esc_html($u['title']) ?></div>
            <div class="card-loc">📍 <?= esc_html("{$u['bairro']}, {$u['cidade']}") ?></div>
            <div class="card-features">
              <?php if($u['metragem']): ?><span class="feat">📐 <?= esc_html($u['metragem']) ?>m²</span><?php endif; ?>
              <?php if($u['quartos']): ?><span class="feat">🛏 <?= esc_html($u['quartos']) ?></span><?php endif; ?>
              <?php if($u['vagas']): ?><span class="feat">🚗 <?= esc_html($u['vagas']) ?></span><?php endif; ?>
            </div>
            <div class="card-price" style="font-size:18px;"><?= ti_fmt_price($uprice) ?></div>
          </div>
          <div class="card-footer">
            <button class="btn-card btn-card-primary" onclick="event.stopPropagation();openLeadPopup(<?= get_the_ID() ?>)">Interesse</button>
            <a href="<?= esc_url($u['permalink']) ?>" class="btn-card btn-card-outline" onclick="event.stopPropagation()">Ver →</a>
          </div>
        </div>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
      <button class="ti-carousel-btn ti-carousel-next" onclick="tiCarouselScroll('upsellCarousel',1)" aria-label="Próximo">&#8250;</button>
    </div>
  </div>
</section>
<?php endif; ?>

<script>
// ── Dados das fotos ───────────────────────────────────────────────────────
var tiGalleryFotos = <?= wp_json_encode($fotos, JSON_UNESCAPED_UNICODE) ?>;
var tiCurrentSlide = 0;
var tiLbIndex      = 0;

// ── Galeria principal ─────────────────────────────────────────────────────
function tiGoToSlide(i) {
  var slides = document.querySelectorAll('.ti-gal-slide');
  var thumbs = document.querySelectorAll('.ti-gal-thumb');
  var cnt    = document.getElementById('tiGalCurrent');
  slides[tiCurrentSlide] && slides[tiCurrentSlide].classList.remove('active');
  thumbs[tiCurrentSlide] && thumbs[tiCurrentSlide].classList.remove('active');
  tiCurrentSlide = ((i % tiGalleryFotos.length) + tiGalleryFotos.length) % tiGalleryFotos.length;
  slides[tiCurrentSlide] && slides[tiCurrentSlide].classList.add('active');
  thumbs[tiCurrentSlide] && thumbs[tiCurrentSlide].classList.add('active');
  if (cnt) cnt.textContent = tiCurrentSlide + 1;
  var thumb = thumbs[tiCurrentSlide];
  if (thumb) thumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
}
function tiGalPrev() { tiGoToSlide(tiCurrentSlide - 1); }
function tiGalNext() { tiGoToSlide(tiCurrentSlide + 1); }

// Auto-avanço a cada 6s se há múltiplas fotos
<?php if (count($fotos) > 1): ?>
setInterval(function(){ tiGalNext(); }, 6000);
<?php endif; ?>

// ── Lightbox ──────────────────────────────────────────────────────────────
function tiOpenLightbox(i) {
  tiLbIndex = i;
  var lb  = document.getElementById('tiLightbox');
  var img = document.getElementById('tiLbImg');
  var cnt = document.getElementById('tiLbCounter');
  if (!lb || !img) return;
  img.src          = tiGalleryFotos[tiLbIndex] || '';
  img.alt          = '<?= esc_js($d['title']) ?> — foto ' + (tiLbIndex + 1);
  cnt.textContent  = (tiLbIndex + 1) + ' / ' + tiGalleryFotos.length;
  lb.classList.add('active');
  document.body.style.overflow = 'hidden';
}
function tiCloseLightbox() {
  document.getElementById('tiLightbox').classList.remove('active');
  document.body.style.overflow = '';
}
function tiLbPrev() {
  tiLbIndex = ((tiLbIndex - 1) + tiGalleryFotos.length) % tiGalleryFotos.length;
  document.getElementById('tiLbImg').src = tiGalleryFotos[tiLbIndex];
  document.getElementById('tiLbCounter').textContent = (tiLbIndex + 1) + ' / ' + tiGalleryFotos.length;
}
function tiLbNext() {
  tiLbIndex = (tiLbIndex + 1) % tiGalleryFotos.length;
  document.getElementById('tiLbImg').src = tiGalleryFotos[tiLbIndex];
  document.getElementById('tiLbCounter').textContent = (tiLbIndex + 1) + ' / ' + tiGalleryFotos.length;
}

// Teclado + swipe
document.addEventListener('keydown', function(e) {
  if (!document.getElementById('tiLightbox').classList.contains('active')) return;
  if (e.key === 'ArrowLeft')  tiLbPrev();
  if (e.key === 'ArrowRight') tiLbNext();
  if (e.key === 'Escape')     tiCloseLightbox();
});
(function(){
  var ts = 0, lb = document.getElementById('tiLightbox');
  if (!lb) return;
  lb.addEventListener('touchstart', function(e){ ts = e.touches[0].clientX; }, {passive:true});
  lb.addEventListener('touchend',   function(e){
    var d = ts - e.changedTouches[0].clientX;
    if (Math.abs(d) > 50) { if(d>0) tiLbNext(); else tiLbPrev(); }
  });
})();

// ── Carrossel upsell ──────────────────────────────────────────────────────
function tiCarouselScroll(id, dir) {
  var el = document.getElementById(id);
  if (el) el.scrollBy({ left: dir * 300, behavior: 'smooth' });
}

// ── Share WA ──────────────────────────────────────────────────────────────
function tiShareWADetail(id, title, price, url) {
  var msg = '🏠 *'+title+'*\n💰 '+price+'\n\nVeja em: '+url+'\n\n*Tarly Pinheiro Imóveis | <?= TI_CRECI ?>*\n📱 <?= TI_PHONE_BR ?>';
  window.open('https://wa.me/?text='+encodeURIComponent(msg), '_blank');
}

// ── Lead popup (passando dados do imóvel para o JS do popup) ──────────────
var _tiImoveisCache = {};
_tiImoveisCache[<?= (int)$d['id'] ?>] = {
  id:    <?= (int)$d['id'] ?>,
  title: <?= wp_json_encode($d['title']) ?>,
  price: <?= wp_json_encode(ti_fmt_price($price_val)) ?>,
  url:   <?= wp_json_encode(get_permalink()) ?>
};

// Sobrescreve openLeadPopup para incluir info do imóvel WP
window.openLeadPopup = function(id) {
  var info = document.getElementById('leadImovelInfo');
  var p    = id ? _tiImoveisCache[id] : null;
  if (info && p) {
    info.innerHTML = '🏠 <strong>' + p.title + '</strong> — ' + p.price;
    info.style.display = 'block';
  } else if (info) {
    info.style.display = 'none';
  }
  window._leadImovelId = id;
  document.getElementById('leadPopup').classList.add('active');
  var nome = document.getElementById('leadNome');
  if (nome) nome.focus();
};

// Nav scroll
window.addEventListener('scroll', function(){
  var n = document.getElementById('mainNav');
  if(n) n.classList.toggle('scrolled', window.scrollY > 40);
});
</script>

<?php include TI_PATH . 'templates/partials/footer.php'; ?>
