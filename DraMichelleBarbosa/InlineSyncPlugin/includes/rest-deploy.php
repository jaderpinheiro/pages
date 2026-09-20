<?php
/**
 * Registro e callback do endpoint REST.
 *
 * POST /wp-json/inline-sync/v1/deploy
 */

defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', 'isa_register_routes' );

/**
 * Registra o endpoint REST do plugin.
 */
function isa_register_routes(): void {
    register_rest_route(
        'inline-sync/v1',
        '/deploy',
        [
            'methods'             => WP_REST_Server::CREATABLE, // POST
            'callback'            => 'isa_handle_deploy',
            'permission_callback' => 'isa_permission_check',
            'args'                => [
                'slug'  => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_title',
                ],
                'title' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'html'  => [
                    'required' => true,
                    'type'     => 'string',
                ],
            ],
        ]
    );
}

/**
 * Verifica a permissão via Bearer Token antes de executar o callback.
 *
 * @param WP_REST_Request $request
 * @return bool|WP_Error
 */
function isa_permission_check( WP_REST_Request $request ) {
    return isa_validate_token( $request );
}

/**
 * Processa o deploy: cria ou atualiza a página WordPress.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function isa_handle_deploy( WP_REST_Request $request ): WP_REST_Response|WP_Error {
    $slug  = $request->get_param( 'slug' );
    $title = $request->get_param( 'title' );
    $html  = $request->get_param( 'html' );

    if ( empty( $slug ) || empty( $title ) || empty( $html ) ) {
        return new WP_Error(
            'invalid_payload',
            'Os campos slug, title e html são obrigatórios e não podem estar vazios.',
            [ 'status' => 422 ]
        );
    }

    $result = isa_upsert_page( $slug, $title, $html );

    if ( is_wp_error( $result ) ) {
        return new WP_Error(
            'page_save_failed',
            $result->get_error_message(),
            [ 'status' => 500 ]
        );
    }

    return new WP_REST_Response(
        [
            'success' => true,
            'page_id' => $result['page_id'],
            'slug'    => $result['slug'],
            'status'  => $result['status'],
        ],
        200
    );
}
