<?php
/**
 * Gerenciamento de paginas WordPress - criar ou atualizar.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cria ou atualiza uma pagina WordPress com base no slug.
 * O HTML completo e salvo sem filtragem KSES e entregue
 * diretamente ao navegador (sem tema do WordPress).
 *
 * @param string $slug  Slug da pagina.
 * @param string $title Titulo da pagina.
 * @param string $html  Conteudo HTML completo.
 * @return array{page_id: int, slug: string, status: string}|WP_Error
 */
function isa_upsert_page( string $slug, string $title, string $html ): array|WP_Error {
    $existing  = isa_get_page_by_slug( $slug );
    $html_hash = hash( 'sha256', $html );

    // Evita escrita desnecessária no banco: retorna 'unchanged' se conteúdo e título não mudaram.
    if ( $existing ) {
        $old_hash = get_post_meta( $existing->ID, '_isa_html_hash', true );

        if ( $old_hash === $html_hash && $existing->post_title === $title ) {
            return [
                'page_id' => $existing->ID,
                'slug'    => $slug,
                'status'  => 'unchanged',
            ];
        }
    }

    $post_data = [
        'post_title'   => $title,
        'post_content' => $html,
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_name'    => $slug,
    ];

    // Remove filtros KSES para preservar <style>, <script> e HTML completo.
    kses_remove_filters();

    if ( $existing ) {
        $post_data['ID'] = $existing->ID;
        $page_id         = wp_update_post( $post_data, true );
        $op_status       = 'updated';
    } else {
        $page_id   = wp_insert_post( $post_data, true );
        $op_status = 'created';
    }

    kses_init_filters();

    if ( is_wp_error( $page_id ) ) {
        return $page_id;
    }

    update_post_meta( $page_id, '_isa_html_hash',   $html_hash );
    update_post_meta( $page_id, '_isa_last_deploy', gmdate( 'c' ) );
    update_post_meta( $page_id, '_isa_full_page',   '1' );

    return [
        'page_id' => $page_id,
        'slug'    => $slug,
        'status'  => $op_status,
    ];
}

/**
 * Entrega o HTML completo diretamente, sem tema do WordPress,
 * para paginas marcadas com _isa_full_page.
 */
add_action( 'template_redirect', 'isa_maybe_render_full_page', 1 );

function isa_maybe_render_full_page(): void {
    if ( ! is_page() ) {
        return;
    }

    $post_id = get_queried_object_id();

    if ( ! get_post_meta( $post_id, '_isa_full_page', true ) ) {
        return;
    }

    $post = get_post( $post_id );

    if ( ! $post ) {
        return;
    }

    // Envia o HTML sem passar por nenhum filtro do WordPress.
    header( 'Content-Type: text/html; charset=UTF-8' );
    echo $post->post_content; // phpcs:ignore WordPress.Security.EscapeOutput
    exit;
}

/**
 * Busca uma pagina pelo slug (post_name).
 *
 * @param string $slug
 * @return WP_Post|null
 */
function isa_get_page_by_slug( string $slug ): ?WP_Post {
    $pages = get_posts( [
        'post_type'      => 'page',
        'name'           => $slug,
        'post_status'    => [ 'publish', 'draft', 'private' ],
        'posts_per_page' => 1,
        'no_found_rows'  => true,
    ] );

    return $pages[0] ?? null;
}
