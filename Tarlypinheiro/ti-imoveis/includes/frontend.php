<?php
defined('ABSPATH') || exit;

// ── Template override ──────────────────────────────────────────────────────────
// NÃO sobrescrevemos a front page — o index.html é servido pelo plugin externo
// (GitHub reader) configurado em Configurações → Leitura → Página inicial.
// Apenas sobrescrevemos single de imóvel e a página de busca.
add_filter('template_include', function ($tpl) {
    if ( is_singular('imovel') ) {
        $custom = TI_PATH . 'templates/single-imovel.php';
        return file_exists($custom) ? $custom : $tpl;
    }
    if ( is_page('imoveis') ) {
        $custom = TI_PATH . 'templates/search-imoveis.php';
        return file_exists($custom) ? $custom : $tpl;
    }
    return $tpl;
});

// ── Enqueue scripts ────────────────────────────────────────────────────────────
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('ti-public', TI_URL . 'assets/css/public.css', [], TI_VER);
    wp_enqueue_script('ti-public', TI_URL . 'assets/js/public.js', [], TI_VER, true);
    wp_localize_script('ti-public', 'ti_ajax', [
        'url'    => admin_url('admin-ajax.php'),
        'nonce'  => wp_create_nonce('ti_public_nonce'),
        'phone'  => ti_get_phone_wa(),
        'phoneDisplay' => ti_get_phone_display(),
        'creci'  => ti_get_creci(),
        'msgTpl' => ti_get_msg_template(),
        'siteUrl'=> home_url('/'),
    ]);
});

// ── Injeta WP globals no HTML servido pelo InlineSyncPlugin ───────────────────
// InlineSyncPlugin usa template_redirect pri=1 → echo $post_content → exit.
// Isso bypassa wp_head completamente. Solução: ob_start em pri=0 captura o
// output quando exit é chamado e injeta os globals no </head>.
add_action('template_redirect', function () {
    if ( ! is_page() ) return;
    $pid = get_queried_object_id();
    if ( ! get_post_meta($pid, '_isa_full_page', true) ) return;

    ob_start(function ($html) {
        $scheme   = is_ssl() ? 'https' : 'http';
        $rest_url = esc_js(set_url_scheme(rest_url('ti/v1/imoveis'), $scheme));
        $ajax_url = esc_js(set_url_scheme(admin_url('admin-ajax.php'), $scheme));
        $site_url = esc_js(set_url_scheme(home_url('/'), $scheme));
        $nonce    = wp_create_nonce('ti_public_nonce');
        $phone    = esc_js(ti_get_phone_wa());
        $script   = "<script>window.TI_WP_REST='{$rest_url}';window.TI_AJAX_URL='{$ajax_url}';window.TI_NONCE='{$nonce}';window.TI_PHONE='{$phone}';window.TI_SITE_URL='{$site_url}';</script>";
        return str_replace('</head>', $script . "\n</head>", $html);
    });
}, 0);

// Fallback via wp_head para páginas fora do InlineSyncPlugin (single, search, etc.)
add_action('wp_head', function () {
    $scheme   = is_ssl() ? 'https' : 'http';
    $rest_url = esc_js(set_url_scheme(rest_url('ti/v1/imoveis'), $scheme));
    $ajax_url = esc_js(set_url_scheme(admin_url('admin-ajax.php'), $scheme));
    $site_url = esc_js(set_url_scheme(home_url('/'), $scheme));
    $nonce    = wp_create_nonce('ti_public_nonce');
    $phone    = esc_js(ti_get_phone_wa());
    echo "<script>window.TI_WP_REST='{$rest_url}';window.TI_AJAX_URL='{$ajax_url}';window.TI_NONCE='{$nonce}';window.TI_PHONE='{$phone}';window.TI_SITE_URL='{$site_url}';</script>\n";
}, 1);

// ── REST API: endpoint público de imóveis (para index.html estático) ──────────
add_action('rest_api_init', function () {
    register_rest_route('ti/v1', '/imoveis', [
        'methods'             => 'GET',
        'callback'            => 'ti_rest_imoveis',
        'permission_callback' => '__return_true',
    ]);
});
function ti_rest_imoveis(): WP_REST_Response {
    $fmt = function ($id) {
        $d = ti_get_imovel_data($id);
        $price = $d['negocio'] === 'Aluguel' ? $d['valor_aluguel'] : $d['valor_venda'];
        return [
            'id'       => $d['id'],
            'title'    => $d['title'],
            'loc'      => trim("{$d['bairro']}, {$d['cidade']}", ' ,'),
            'price'    => ti_fmt_price($price),
            'area'     => $d['metragem'] ? $d['metragem'] . 'm²' : '',
            'quartos'  => $d['quartos']  ?: '',
            'vagas'    => $d['vagas']    ?: '',
            'thumb'    => $d['thumb']    ?: '',
            'url'      => $d['permalink'],
            'negocio'  => $d['negocio'],
            'destaque' => (bool)$d['destaque'],
        ];
    };

    $q_dest = ti_query_imoveis(['destaque' => '1', 'limit' => 4]);
    $q_novos = ti_query_imoveis(['limit' => 6]);

    $destaque = $novos = [];
    foreach ($q_dest->posts  as $p) $destaque[] = $fmt($p->ID);
    foreach ($q_novos->posts as $p) $novos[]    = $fmt($p->ID);

    return new WP_REST_Response(['destaque' => $destaque, 'novos' => $novos], 200);
}

// ── WebP: converte imagens no upload ──────────────────────────────────────────
add_filter('wp_generate_attachment_metadata', function ($meta, $att_id) {
    if ( ! function_exists('imagewebp') ) return $meta;
    $file = get_attached_file($att_id);
    $type = get_post_mime_type($att_id);
    if ( ! in_array($type, ['image/jpeg', 'image/jpg', 'image/png']) ) return $meta;

    $base  = trailingslashit(dirname($file));
    $files = [$file];
    foreach ( $meta['sizes'] ?? [] as $s ) {
        if ( ! empty($s['file']) ) $files[] = $base . $s['file'];
    }

    foreach ($files as $src) {
        if ( ! file_exists($src) ) continue;
        $dst = preg_replace('/\.(jpe?g|png)$/i', '.webp', $src);
        if ( file_exists($dst) ) continue;

        $img = $type === 'image/png' ? @imagecreatefrompng($src) : @imagecreatefromjpeg($src);
        if ( ! $img ) continue;

        // Preservar transparência PNG
        if ( $type === 'image/png' ) {
            imagepalettetotruecolor($img);
            imagealphablending($img, true);
            imagesavealpha($img, true);
        }

        @imagewebp($img, $dst, 82);
        imagedestroy($img);
    }
    return $meta;
}, 10, 2);

// Servir WebP quando disponível (adiciona cabeçalho Accept para compatibilidade)
add_filter('wp_get_attachment_image_src', function ($image) {
    if ( ! $image || ! function_exists('imagewebp') ) return $image;
    $src  = $image[0];
    $webp = preg_replace('/\.(jpe?g|png)(\?.*)?$/i', '.webp$2', $src);
    $path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $webp);
    if ( file_exists($path) ) {
        $image[0] = $webp;
    }
    return $image;
});

// ── SEO meta tags ──────────────────────────────────────────────────────────────
add_action('wp_head', function () {
    if ( ! is_singular('imovel') ) return;
    $id    = get_the_ID();
    $d     = ti_get_imovel_data($id);
    $desc  = $d['descricao'] ? wp_trim_words($d['descricao'], 30) : "{$d['title']} em {$d['bairro']}, {$d['cidade']}. " . TI_CRECI;
    $price = $d['valor_venda'] ?: $d['valor_aluguel'];

    printf('<meta name="description" content="%s">' . "\n", esc_attr($desc));
    printf('<meta property="og:title" content="%s | Tarly Pinheiro Imóveis">' . "\n", esc_attr($d['title']));
    printf('<meta property="og:description" content="%s">' . "\n", esc_attr($desc));
    if ($d['thumb']) printf('<meta property="og:image" content="%s">' . "\n", esc_url($d['thumb']));
    echo '<meta property="og:type" content="product">' . "\n";

    $schema = [
        '@context'    => 'https://schema.org',
        '@type'       => 'RealEstateListing',
        'name'        => $d['title'],
        'description' => $desc,
        'url'         => get_permalink($id),
        'image'       => $d['thumb'] ?: '',
        'address'     => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => trim("{$d['rua']}, {$d['numero']}"),
            'addressLocality' => $d['cidade'],
            'addressRegion'   => $d['estado'] ?: 'GO',
            'addressCountry'  => 'BR',
        ],
        'offers' => $price ? [
            '@type'         => 'Offer',
            'price'         => (float)$price,
            'priceCurrency' => 'BRL',
        ] : null,
    ];
    echo '<script type="application/ld+json">' . wp_json_encode(array_filter($schema), JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}, 5);

// ── Shortcode fallback [ti_resultados] ────────────────────────────────────────
add_shortcode('ti_resultados', function () {
    ob_start();
    include TI_PATH . 'templates/search-imoveis.php';
    return ob_get_clean();
});

// ── Helper: dados completos do imóvel ────────────────────────────────────────
function ti_get_imovel_data(int $id): array {
    $meta     = fn($k) => get_post_meta($id, $k, true);
    $gal_ids  = array_filter(explode(',', $meta('_ti_gallery') ?: ''));
    $negocio  = get_the_terms($id, 'negocio');
    $tipo     = get_the_terms($id, 'tipo_imovel');

    // Obter thumb webp se disponível
    $thumb_id  = get_post_thumbnail_id($id);
    $thumb_src = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'large') : '';

    $gallery = [];
    if ($thumb_src) $gallery[] = $thumb_src;
    foreach ($gal_ids as $gid) {
        $url = wp_get_attachment_image_url((int)$gid, 'large');
        if ($url && !in_array($url, $gallery)) $gallery[] = $url;
    }

    return [
        'id'           => $id,
        'title'        => get_the_title($id),
        'permalink'    => get_permalink($id),
        'thumb'        => $thumb_src,
        'gallery'      => $gallery,
        'metragem'     => $meta('_ti_metragem'),
        'quartos'      => $meta('_ti_quartos'),
        'suites'       => $meta('_ti_suites'),
        'banheiros'    => $meta('_ti_banheiros'),
        'vagas'        => $meta('_ti_vagas'),
        'andar'        => $meta('_ti_andar'),
        'valor_venda'  => $meta('_ti_valor_venda'),
        'valor_aluguel'=> $meta('_ti_valor_aluguel'),
        'valor_cond'   => $meta('_ti_valor_condominio'),
        'valor_iptu'   => $meta('_ti_valor_iptu'),
        'descricao'    => $meta('_ti_descricao'),
        'rua'          => $meta('_ti_rua'),
        'numero'       => $meta('_ti_numero'),
        'bairro'       => $meta('_ti_bairro'),
        'cidade'       => $meta('_ti_cidade') ?: 'Goiânia',
        'estado'       => $meta('_ti_estado') ?: 'GO',
        'cod_anunc'    => $meta('_ti_codigo_anunciante'),
        'cod_viva'     => $meta('_ti_codigo_vivareal'),
        'status'       => $meta('_ti_status') ?: 'disponivel',
        'destaque'     => $meta('_ti_destaque'),
        'aceita_anim'  => $meta('_ti_aceita_animais'),
        'lavanderia'   => $meta('_ti_lavanderia'),
        'area_serv'    => $meta('_ti_area_servico'),
        'lavabo'       => $meta('_ti_lavabo'),
        'piscina'      => $meta('_ti_piscina'),
        'academia'     => $meta('_ti_academia'),
        'churrasq'     => $meta('_ti_churrasqueira'),
        'negocio'      => $negocio ? wp_list_pluck($negocio, 'name')[0] : '',
        'tipo'         => $tipo    ? wp_list_pluck($tipo,    'name')[0] : '',
        'publicado'    => $meta('_ti_publicado_em') ?: get_the_date('d \d\e F \d\e Y', $id),
    ];
}

// ── Helper: formatar preço ────────────────────────────────────────────────────
function ti_fmt_price(string $val): string {
    if ( ! $val ) return '—';
    return 'R$ ' . number_format((float)$val, 0, ',', '.');
}

// ── Helper: WP_Query para imóveis ────────────────────────────────────────────
function ti_query_imoveis(array $args = []): WP_Query {
    $defaults = [
        'post_type'      => 'imovel',
        'post_status'    => 'publish',
        'posts_per_page' => $args['limit'] ?? 12,
        'paged'          => max(1, get_query_var('paged')),
        'no_found_rows'  => isset($args['limit']),
    ];

    $tax_query = [];
    if ( ! empty($args['negocio']) ) {
        $tax_query[] = ['taxonomy' => 'negocio', 'field' => 'name', 'terms' => $args['negocio']];
    }
    if ( ! empty($args['tipo']) ) {
        $tax_query[] = ['taxonomy' => 'tipo_imovel', 'field' => 'name', 'terms' => $args['tipo']];
    }
    if ( count($tax_query) > 1 ) $tax_query['relation'] = 'AND';
    if ( $tax_query ) $defaults['tax_query'] = $tax_query;

    if ( ! empty($args['busca']) ) $defaults['s'] = $args['busca'];

    // Destaque
    if ( ! empty($args['destaque']) ) {
        $defaults['meta_query'] = [['key' => '_ti_destaque', 'value' => '1', 'compare' => '=']];
    }

    // Faixa de preço
    if ( ! empty($args['faixa']) ) {
        $faixa = $args['faixa'];
        $mq    = ['relation' => 'OR'];
        if ( str_contains($faixa, 'Até') ) {
            preg_match('/[\d.]+/', $faixa, $m);
            $max  = (int)str_replace('.', '', $m[0] ?? '999999');
            $mq[] = ['key' => '_ti_valor_venda',   'value' => $max, 'type' => 'NUMERIC', 'compare' => '<='];
            $mq[] = ['key' => '_ti_valor_aluguel',  'value' => $max, 'type' => 'NUMERIC', 'compare' => '<='];
        } elseif ( str_contains($faixa, 'Acima') ) {
            $mq[] = ['key' => '_ti_valor_venda', 'value' => 1000000, 'type' => 'NUMERIC', 'compare' => '>='];
        }
        if ( count($mq) > 1 ) {
            $defaults['meta_query'] = isset($defaults['meta_query'])
                ? array_merge($defaults['meta_query'], $mq)
                : $mq;
        }
    }

    return new WP_Query($defaults);
}

// ── Helper: posts sociais ─────────────────────────────────────────────────────
function ti_get_social_posts(string $rede = 'instagram', int $limit = 8): array {
    global $wpdb;
    $table = $wpdb->prefix . 'ti_social_posts';
    if ( ! $wpdb->get_var("SHOW TABLES LIKE '{$table}'") ) return [];
    return $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table WHERE rede = %s ORDER BY created_at DESC LIMIT %d", $rede, $limit)
    ) ?: [];
}
