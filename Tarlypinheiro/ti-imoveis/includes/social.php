<?php
defined('ABSPATH') || exit;

// ── Helpers de embed ──────────────────────────────────────────────────────────
/**
 * Gera HTML de embed a partir de uma URL de rede social.
 * Usa wp_oembed_get() para YouTube, TikTok, Vimeo, Twitter, etc.
 * Para Instagram usa o iframe embed oficial.
 * Retorna '' se não conseguir gerar.
 */
function ti_social_generate_embed(string $url): string {
    if ( ! $url ) return '';

    // Instagram: usar iframe embed
    if ( preg_match('#instagram\.com/(?:p|reel|tv)/([A-Za-z0-9_-]+)#', $url, $m) ) {
        $id = $m[1];
        return '<blockquote class="instagram-media" data-instgrm-permalink="https://www.instagram.com/p/' . $id . '/?utm_source=ig_embed" data-instgrm-version="14" style="width:100%;min-width:0;max-width:540px;">'
             . '</blockquote><script async src="//www.instagram.com/embed.js"></script>';
    }

    // TikTok: usar oembed
    if ( str_contains($url, 'tiktok.com') ) {
        $oe = wp_oembed_get($url, ['maxwidth' => 540]);
        if ( $oe ) return $oe;
        // Fallback: iframe embed
        if ( preg_match('#tiktok\.com/@[^/]+/video/(\d+)#', $url, $m) ) {
            return '<blockquote class="tiktok-embed" cite="' . esc_url($url) . '" data-video-id="' . $m[1] . '" style="max-width:605px;min-width:325px;">'
                 . '</blockquote><script async src="https://www.tiktok.com/embed.js"></script>';
        }
    }

    // YouTube, Vimeo, Twitter, etc. via WP oEmbed
    $oe = wp_oembed_get($url, ['maxwidth' => 640]);
    if ( $oe ) return $oe;

    // Fallback: link clicável
    return '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html($url) . '</a>';
}

// ── CRUD helpers ──────────────────────────────────────────────────────────────
function ti_social_save(array $data): int {
    global $wpdb;
    $table = $wpdb->prefix . 'ti_social_posts';

    $embed_html = '';
    $link = esc_url_raw($data['link'] ?? '');
    if ( $link ) {
        $embed_html = ti_social_generate_embed($link);
    }

    $row = [
        'rede'       => sanitize_text_field($data['rede']     ?? 'instagram'),
        'link'       => $link,
        'image_id'   => (int)($data['image_id']               ?? 0),
        'legenda'    => sanitize_textarea_field($data['legenda']  ?? ''),
        'alt_text'   => sanitize_text_field($data['alt_text'] ?? ''),
        'tags'       => sanitize_text_field($data['tags']     ?? ''),
        'embed_html' => $embed_html,
    ];

    if ( ! empty($data['id']) ) {
        $wpdb->update($table, $row, ['id' => (int)$data['id']]);
        return (int)$data['id'];
    }
    $wpdb->insert($table, $row);
    return (int)$wpdb->insert_id;
}

function ti_social_delete(int $id): bool {
    global $wpdb;
    return (bool)$wpdb->delete($wpdb->prefix . 'ti_social_posts', ['id' => $id]);
}

function ti_social_get(int $id): ?object {
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ti_social_posts WHERE id = %d", $id
    ));
}

function ti_social_list(string $rede = '', int $limit = 100, int $offset = 0): array {
    global $wpdb;
    $where = $rede ? $wpdb->prepare("WHERE rede = %s", $rede) : '';
    return $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM {$wpdb->prefix}ti_social_posts $where ORDER BY created_at DESC LIMIT %d OFFSET %d", $limit, $offset)
    ) ?: [];
}
