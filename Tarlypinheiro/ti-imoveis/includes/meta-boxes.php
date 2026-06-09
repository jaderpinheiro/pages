<?php
defined('ABSPATH') || exit;

add_action('add_meta_boxes', function () {
    add_meta_box('ti_imovel_dados',   'Características & Valores', 'ti_mb_dados',   'imovel', 'normal', 'high');
    add_meta_box('ti_imovel_local',   'Localização',               'ti_mb_local',   'imovel', 'normal', 'default');
    add_meta_box('ti_imovel_galeria', 'Galeria de Fotos',          'ti_mb_galeria', 'imovel', 'normal', 'default');
    add_meta_box('ti_imovel_extra',   'Informações Adicionais',    'ti_mb_extra',   'imovel', 'side',   'default');
});

// ── META BOX: Características & Valores ───────────────────────────────────────
function ti_mb_dados($post): void {
    wp_nonce_field('ti_save_meta', 'ti_nonce');
    $m = fn($k) => get_post_meta($post->ID, $k, true);
    ?>
    <style>
    .ti-mb-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;}
    .ti-mb-grid-2{display:grid;grid-template-columns:repeat(2,1fr);gap:14px;margin-bottom:20px;}
    .ti-mb-field label{display:block;font-size:11px;font-weight:600;text-transform:uppercase;color:#666;margin-bottom:5px;}
    .ti-mb-field input,.ti-mb-field select{width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:6px;font-size:14px;}
    .ti-mb-section-title{font-size:12px;font-weight:700;text-transform:uppercase;color:#0D1B2A;border-bottom:2px solid #C9A84C;padding-bottom:6px;margin:16px 0 14px;}
    .ti-bool-grid{display:flex;flex-wrap:wrap;gap:10px 20px;margin-bottom:16px;}
    .ti-bool-grid label{display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;}
    .ti-amen-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;}
    .ti-amen-grid label{display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;}
    </style>

    <p class="ti-mb-section-title">Características Básicas</p>
    <div class="ti-mb-grid">
        <?php foreach ([
            ['_ti_metragem',  'Área (m²)',    'number'],
            ['_ti_quartos',   'Quartos',      'number'],
            ['_ti_suites',    'Suítes',       'number'],
            ['_ti_banheiros', 'Banheiros',    'number'],
            ['_ti_vagas',     'Vagas',        'number'],
            ['_ti_andar',     'Andar',        'number'],
        ] as [$key, $label, $type]): ?>
        <div class="ti-mb-field">
            <label><?= esc_html($label) ?></label>
            <input type="<?= $type ?>" name="<?= $key ?>" value="<?= esc_attr($m($key)) ?>" min="0">
        </div>
        <?php endforeach; ?>
        <div class="ti-mb-field">
            <label>Status</label>
            <select name="_ti_status">
                <?php foreach (['disponivel'=>'Disponível','vendido'=>'Vendido','alugado'=>'Alugado','reservado'=>'Reservado'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= selected($m('_ti_status'),$v,false) ?>><?= $l ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <p class="ti-mb-section-title">Comodidades</p>
    <div class="ti-bool-grid">
        <?php foreach ([
            '_ti_aceita_animais' => 'Aceita Animais',
            '_ti_lavanderia'     => 'Lavanderia',
            '_ti_area_servico'   => 'Área de Serviço',
            '_ti_lavabo'         => 'Lavabo',
            '_ti_churrasqueira'  => 'Churrasqueira',
            '_ti_piscina'        => 'Piscina',
            '_ti_academia'       => 'Academia',
            '_ti_playground'     => 'Playground',
            '_ti_salao_festas'   => 'Salão de Festas',
            '_ti_portaria_24h'   => 'Portaria 24h',
            '_ti_elevador'       => 'Elevador',
            '_ti_pet_friendly'   => 'Pet Friendly',
        ] as $key => $label): ?>
        <label><input type="checkbox" name="<?= $key ?>" value="1" <?= checked($m($key),'1',false) ?>> <?= esc_html($label) ?></label>
        <?php endforeach; ?>
    </div>

    <p class="ti-mb-section-title">Valores</p>
    <div class="ti-mb-grid">
        <?php foreach ([
            ['_ti_valor_venda',      'Venda (R$)'],
            ['_ti_valor_aluguel',    'Aluguel (R$/mês)'],
            ['_ti_valor_condominio', 'Condomínio (R$/mês)'],
            ['_ti_valor_iptu',       'IPTU (R$/mês)'],
        ] as [$key, $label]):
            $raw = $m($key);
            $display = ($raw && (float)$raw > 0)
                ? 'R$ ' . number_format((float)$raw, 2, ',', '.')
                : '';
        ?>
        <div class="ti-mb-field">
            <label><?= esc_html($label) ?></label>
            <input type="text" name="<?= $key ?>" value="<?= esc_attr($display) ?>"
                   class="ti-money" oninput="tiMaskMoney(this)" placeholder="R$ 0,00">
        </div>
        <?php endforeach; ?>
    </div>

    <p class="ti-mb-section-title">Descrição Completa</p>
    <div class="ti-mb-field">
        <textarea name="_ti_descricao" rows="8" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;font-size:14px;resize:vertical;"><?= esc_textarea($m('_ti_descricao')) ?></textarea>
    </div>
    <?php
}

// ── META BOX: Localização ─────────────────────────────────────────────────────
function ti_mb_local($post): void {
    $m = fn($k) => get_post_meta($post->ID, $k, true);
    ?>
    <div style="display:grid;grid-template-columns:3fr 1fr;gap:14px;margin-bottom:14px;">
        <div class="ti-mb-field"><label>Rua / Logradouro</label>
            <input type="text" name="_ti_rua" value="<?= esc_attr($m('_ti_rua')) ?>" style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:6px;"></div>
        <div class="ti-mb-field"><label>Número</label>
            <input type="text" name="_ti_numero" value="<?= esc_attr($m('_ti_numero')) ?>" style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:6px;"></div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;">
        <?php foreach ([
            ['_ti_bairro', 'Bairro'],
            ['_ti_cidade', 'Cidade'],
            ['_ti_estado', 'Estado (UF)'],
        ] as [$key, $label]): ?>
        <div class="ti-mb-field"><label><?= esc_html($label) ?></label>
            <input type="text" name="<?= $key ?>" value="<?= esc_attr($m($key)) ?>" style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:6px;"></div>
        <?php endforeach; ?>
    </div>
    <?php
}

// ── META BOX: Galeria ─────────────────────────────────────────────────────────
function ti_mb_galeria($post): void {
    $gallery = get_post_meta($post->ID, '_ti_gallery', true) ?: '';
    $ids     = array_filter(explode(',', $gallery));
    ?>
    <div id="ti-gallery-wrap" style="display:flex;flex-wrap:wrap;gap:10px;min-height:80px;margin-bottom:12px;">
        <?php foreach ($ids as $id): $url = wp_get_attachment_image_url((int)$id,'thumbnail'); if(!$url) continue; ?>
        <div class="ti-gallery-item" data-id="<?= (int)$id ?>" style="position:relative;cursor:move;">
            <img src="<?= esc_url($url) ?>" style="width:90px;height:90px;object-fit:cover;border-radius:6px;">
            <button type="button" onclick="tiRemovePhoto(<?= (int)$id ?>)" style="position:absolute;top:2px;right:2px;background:rgba(255,0,0,0.8);color:#fff;border:none;border-radius:50%;width:20px;height:20px;cursor:pointer;font-size:12px;line-height:1;padding:0;">✕</button>
        </div>
        <?php endforeach; ?>
    </div>
    <input type="hidden" name="_ti_gallery" id="ti_gallery_input" value="<?= esc_attr($gallery) ?>">
    <button type="button" class="button button-primary" id="ti-add-photos">+ Adicionar Fotos</button>
    <script>
    (function(){
        var frame, input = document.getElementById('ti_gallery_input'),
            wrap = document.getElementById('ti-gallery-wrap');

        document.getElementById('ti-add-photos').addEventListener('click', function(){
            if(frame){ frame.open(); return; }
            frame = wp.media({title:'Selecionar Fotos', button:{text:'Adicionar à Galeria'}, multiple:true});
            frame.on('select', function(){
                var ids = input.value ? input.value.split(',').filter(Boolean) : [];
                frame.state().get('selection').each(function(a){
                    var id = a.get('id').toString();
                    if(!ids.includes(id)){
                        ids.push(id);
                        var img = document.createElement('div');
                        img.className = 'ti-gallery-item';
                        img.dataset.id = id;
                        img.style.cssText = 'position:relative;cursor:move;';
                        img.innerHTML = '<img src="'+a.attributes.sizes.thumbnail.url+'" style="width:90px;height:90px;object-fit:cover;border-radius:6px;"><button type="button" onclick="tiRemovePhoto('+id+')" style="position:absolute;top:2px;right:2px;background:rgba(255,0,0,0.8);color:#fff;border:none;border-radius:50%;width:20px;height:20px;cursor:pointer;font-size:12px;line-height:1;padding:0;">✕</button>';
                        wrap.appendChild(img);
                    }
                });
                input.value = ids.join(',');
            });
            frame.open();
        });
    })();

    function tiRemovePhoto(id) {
        var input = document.getElementById('ti_gallery_input');
        var ids   = input.value.split(',').filter(Boolean).filter(function(i){ return i != id; });
        input.value = ids.join(',');
        var el = document.querySelector('.ti-gallery-item[data-id="'+id+'"]');
        if(el) el.remove();
    }
    </script>
    <?php
}

// ── META BOX: Informações Adicionais ─────────────────────────────────────────
function ti_mb_extra($post): void {
    $m = fn($k) => get_post_meta($post->ID, $k, true);
    $fields = [
        ['_ti_codigo_anunciante', 'Código do Anunciante'],
        ['_ti_codigo_vivareal',   'Código Viva Real'],
        ['_ti_publicado_em',      'Publicado em (data)'],
        ['_ti_destaque',          'Destaque (1=sim)'],
    ];
    foreach ($fields as [$key, $label]):
    ?>
    <p style="margin-bottom:10px;">
        <label style="display:block;font-size:11px;font-weight:600;text-transform:uppercase;color:#666;margin-bottom:4px;"><?= esc_html($label) ?></label>
        <input type="text" name="<?= $key ?>" value="<?= esc_attr($m($key)) ?>" style="width:100%;padding:7px 10px;border:1px solid #ddd;border-radius:6px;font-size:13px;">
    </p>
    <?php endforeach;
}

// ── SAVE META ─────────────────────────────────────────────────────────────────
add_action('save_post_imovel', function ($post_id) {
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( ! isset($_POST['ti_nonce']) || ! wp_verify_nonce($_POST['ti_nonce'], 'ti_save_meta') ) return;
    if ( ! current_user_can('edit_post', $post_id) ) return;

    $text_keys = ['_ti_metragem','_ti_quartos','_ti_suites','_ti_banheiros','_ti_vagas','_ti_andar',
                  '_ti_status','_ti_descricao','_ti_rua','_ti_numero','_ti_bairro','_ti_cidade','_ti_estado',
                  '_ti_codigo_anunciante','_ti_codigo_vivareal','_ti_publicado_em','_ti_destaque','_ti_gallery'];

    foreach ($text_keys as $key) {
        if ( array_key_exists($key, $_POST) ) {
            update_post_meta($post_id, $key, sanitize_textarea_field(wp_unslash($_POST[$key])));
        }
    }

    // Campos de valor: strip máscara monetária "R$ 1.500.000,00" → "1500000.00"
    $money_keys = ['_ti_valor_venda','_ti_valor_aluguel','_ti_valor_condominio','_ti_valor_iptu'];
    foreach ($money_keys as $key) {
        if ( array_key_exists($key, $_POST) ) {
            $v = wp_unslash($_POST[$key]);
            $v = preg_replace('/[^\d,]/', '', $v);  // remove R$, spaces, pontos de milhar
            $v = str_replace(',', '.', $v);          // vírgula decimal → ponto
            $v = $v !== '' ? (string)(float)$v : '';
            update_post_meta($post_id, $key, $v);
        }
    }

    $bool_keys = ['_ti_aceita_animais','_ti_lavanderia','_ti_area_servico','_ti_lavabo','_ti_churrasqueira',
                  '_ti_piscina','_ti_academia','_ti_playground','_ti_salao_festas','_ti_portaria_24h',
                  '_ti_elevador','_ti_pet_friendly'];
    foreach ($bool_keys as $key) {
        update_post_meta($post_id, $key, isset($_POST[$key]) ? '1' : '0');
    }
});
