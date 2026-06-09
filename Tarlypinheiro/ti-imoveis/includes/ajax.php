<?php
defined('ABSPATH') || exit;

// Salvar lead (frontend — visitantes)
add_action('wp_ajax_ti_save_lead',        'ti_ajax_save_lead');
add_action('wp_ajax_nopriv_ti_save_lead', 'ti_ajax_save_lead');
function ti_ajax_save_lead(): void {
    check_ajax_referer('ti_public_nonce', 'nonce');
    $id = ti_save_lead([
        'nome'        => sanitize_text_field($_POST['nome'] ?? ''),
        'telefone'    => sanitize_text_field($_POST['telefone'] ?? ''),
        'email'       => sanitize_email($_POST['email'] ?? ''),
        'imovel_id'   => (int)($_POST['imovel_id'] ?? 0),
        'imovel_nome' => sanitize_text_field($_POST['imovel_nome'] ?? ''),
    ]);
    wp_send_json_success(['lead_id' => $id]);
}

// Mover lead no kanban (admin)
add_action('wp_ajax_ti_move_lead', 'ti_ajax_move_lead');
function ti_ajax_move_lead(): void {
    check_ajax_referer('ti_admin_nonce', 'nonce');
    if ( ! current_user_can('manage_options') ) wp_send_json_error('Permissão negada', 403);
    $ok = ti_update_lead_status((int)$_POST['id'], sanitize_key($_POST['status']));
    $ok ? wp_send_json_success() : wp_send_json_error('Falha ao atualizar');
}

// Atualizar info do lead (admin)
add_action('wp_ajax_ti_update_lead', 'ti_ajax_update_lead');
function ti_ajax_update_lead(): void {
    check_ajax_referer('ti_admin_nonce', 'nonce');
    if ( ! current_user_can('manage_options') ) wp_send_json_error('Permissão negada', 403);
    $ok = ti_update_lead_info((int)$_POST['id'], [
        'descricao'    => $_POST['descricao'] ?? '',
        'enviou_docs'  => (int)($_POST['enviou_docs'] ?? 0),
        'indicou'      => $_POST['indicou'] ?? '',
    ]);
    $ok ? wp_send_json_success() : wp_send_json_error('Falha ao salvar');
}

// Salvar / editar post social (admin)
add_action('wp_ajax_ti_social_save', 'ti_ajax_social_save');
function ti_ajax_social_save(): void {
    check_ajax_referer('ti_admin_nonce', 'nonce');
    if ( ! current_user_can('manage_options') ) wp_send_json_error('Permissão negada', 403);
    $id = ti_social_save([
        'id'       => (int)($_POST['id'] ?? 0),
        'rede'     => $_POST['rede']     ?? 'instagram',
        'link'     => $_POST['link']     ?? '',
        'image_id' => (int)($_POST['image_id'] ?? 0),
        'legenda'  => $_POST['legenda']  ?? '',
        'alt_text' => $_POST['alt_text'] ?? '',
        'tags'     => $_POST['tags']     ?? '',
    ]);
    wp_send_json_success(['id' => $id]);
}

// Excluir post social (admin)
add_action('wp_ajax_ti_social_delete', 'ti_ajax_social_delete');
function ti_ajax_social_delete(): void {
    check_ajax_referer('ti_admin_nonce', 'nonce');
    if ( ! current_user_can('manage_options') ) wp_send_json_error('Permissão negada', 403);
    ti_social_delete((int)($_POST['id'] ?? 0));
    wp_send_json_success();
}

// Export leads CSV (download direto via GET)
add_action('wp_ajax_ti_export_leads', 'ti_ajax_export_leads');
function ti_ajax_export_leads(): void {
    if ( ! current_user_can('manage_options') ) wp_die('Permissão negada', 403);
    check_ajax_referer('ti_admin_nonce', 'nonce');
    ti_export_leads_csv(sanitize_key($_GET['status'] ?? ''));
}

// Import leads CSV (upload de arquivo)
add_action('wp_ajax_ti_import_leads', 'ti_ajax_import_leads');
function ti_ajax_import_leads(): void {
    check_ajax_referer('ti_admin_nonce', 'nonce');
    if ( ! current_user_can('manage_options') ) wp_send_json_error('Permissão negada', 403);
    if ( empty($_FILES['csv_file']['tmp_name']) ) wp_send_json_error('Nenhum arquivo enviado.');
    $result = ti_import_leads_csv($_FILES['csv_file']['tmp_name']);
    wp_send_json_success($result);
}

// Preview embed oEmbed (admin — gera HTML de embed para uma URL social)
add_action('wp_ajax_ti_oembed_preview', 'ti_ajax_oembed_preview');
function ti_ajax_oembed_preview(): void {
    check_ajax_referer('ti_admin_nonce', 'nonce');
    if ( ! current_user_can('manage_options') ) wp_send_json_error('Permissão negada', 403);
    $url = esc_url_raw(wp_unslash($_POST['url'] ?? ''));
    if ( ! $url ) wp_send_json_error('URL inválida');
    $html = ti_social_generate_embed($url);
    wp_send_json_success(['html' => $html]);
}

// Busca de imóveis via AJAX (para autocomplete)
add_action('wp_ajax_ti_buscar',        'ti_ajax_buscar');
add_action('wp_ajax_nopriv_ti_buscar', 'ti_ajax_buscar');
function ti_ajax_buscar(): void {
    $term   = sanitize_text_field($_GET['q'] ?? '');
    $query  = new WP_Query([
        's'              => $term,
        'post_type'      => 'imovel',
        'post_status'    => 'publish',
        'posts_per_page' => 5,
        'no_found_rows'  => true,
    ]);
    $result = [];
    foreach ($query->posts as $p) {
        $result[] = [
            'id'    => $p->ID,
            'title' => $p->post_title,
            'url'   => get_permalink($p->ID),
            'thumb' => get_the_post_thumbnail_url($p->ID, 'thumbnail'),
        ];
    }
    wp_send_json_success($result);
}
