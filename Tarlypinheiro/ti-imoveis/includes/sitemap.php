<?php
defined('ABSPATH') || exit;

// Endpoint /sitemap-imoveis.xml
add_action('init', function () {
    add_rewrite_rule('^sitemap-imoveis\.xml$', 'index.php?ti_sitemap=1', 'top');
    add_rewrite_tag('%ti_sitemap%', '([^&]+)');
});

add_action('template_redirect', function () {
    if ( ! get_query_var('ti_sitemap') ) return;
    header('Content-Type: application/xml; charset=UTF-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
         xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

    // Página principal
    echo ti_sitemap_url(home_url('/'), '1.0', 'daily');

    // Página de busca
    $search_page = get_page_by_path('imoveis');
    if ($search_page) echo ti_sitemap_url(get_permalink($search_page->ID), '0.9', 'daily');

    // Imóveis
    $query = new WP_Query([
        'post_type'      => 'imovel',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ]);
    foreach ($query->posts as $id) {
        $mod   = get_the_modified_date('c', $id);
        $thumb = get_the_post_thumbnail_url($id, 'large');
        $title = esc_html(get_the_title($id));
        $loc   = esc_url(get_permalink($id));
        echo "<url>\n";
        echo "  <loc>{$loc}</loc>\n";
        echo "  <lastmod>{$mod}</lastmod>\n";
        echo "  <changefreq>weekly</changefreq>\n";
        echo "  <priority>0.8</priority>\n";
        if ($thumb) {
            echo "  <image:image>\n";
            echo "    <image:loc>" . esc_url($thumb) . "</image:loc>\n";
            echo "    <image:title>{$title}</image:title>\n";
            echo "  </image:image>\n";
        }
        echo "</url>\n";
    }

    // Taxonomias relevantes (negocio, tipo)
    foreach (['negocio','tipo_imovel'] as $tax) {
        $terms = get_terms(['taxonomy' => $tax, 'hide_empty' => true]);
        if ( is_array($terms) ) {
            foreach ($terms as $term) {
                echo ti_sitemap_url(get_term_link($term), '0.6', 'weekly');
            }
        }
    }

    // Posts de redes sociais (com imagem para Google Imagens)
    $social_posts = ti_social_list('', 200);
    foreach ($social_posts as $sp) {
        $img_url = $sp->image_id ? wp_get_attachment_image_url((int)$sp->image_id, 'large') : '';
        $loc     = $sp->link ? esc_url($sp->link) : home_url('/');
        echo "<url>\n";
        echo "  <loc>{$loc}</loc>\n";
        echo "  <changefreq>monthly</changefreq>\n";
        echo "  <priority>0.4</priority>\n";
        if ($img_url) {
            $alt = esc_html($sp->alt_text ?: $sp->legenda);
            echo "  <image:image>\n";
            echo "    <image:loc>" . esc_url($img_url) . "</image:loc>\n";
            if ($alt) echo "    <image:caption>{$alt}</image:caption>\n";
            if ($sp->alt_text) echo "    <image:title>" . esc_html($sp->alt_text) . "</image:title>\n";
            echo "  </image:image>\n";
        }
        echo "</url>\n";
    }

    echo '</urlset>';
    exit;
});

function ti_sitemap_url(string $loc, string $priority, string $freq): string {
    return "<url>\n  <loc>" . esc_url($loc) . "</loc>\n  <changefreq>{$freq}</changefreq>\n  <priority>{$priority}</priority>\n</url>\n";
}

// Adicionar referência no sitemap do WP (Yoast/Rank Math compatível)
add_filter('wpseo_sitemap_index', function ($index) {
    return $index . '<sitemap><loc>' . home_url('/sitemap-imoveis.xml') . '</loc></sitemap>';
});
add_filter('rank_math/sitemap/index', function ($index) {
    return $index . '<sitemap><loc>' . home_url('/sitemap-imoveis.xml') . '</loc></sitemap>';
});

// Robots.txt hint
add_filter('robots_txt', function ($output) {
    return $output . "\nSitemap: " . home_url('/sitemap-imoveis.xml') . "\n";
});
