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
            // args sem required:true para não bloquear antes do fallback de parsing.
            'args'                => [
                'slug'  => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_title',
                ],
                'title' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'html'  => [
                    'type' => 'string',
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
    // Leitura primária via get_param (funciona quando Content-Type: application/json está correto).
    $slug  = $request->get_param( 'slug' );
    $title = $request->get_param( 'title' );
    $html  = $request->get_param( 'html' );

    // Fallback: lê o body raw como JSON — resolve casos em que o cliente não envia
    // Content-Type: application/json (problema frequente após atualização do WP 6.6+).
    if ( empty( $slug ) || empty( $title ) || empty( $html ) ) {
        $body = $request->get_json_params();

        if ( is_array( $body ) ) {
            if ( empty( $slug ) && ! empty( $body['slug'] ) ) {
                $slug = sanitize_title( $body['slug'] );
            }
            if ( empty( $title ) && ! empty( $body['title'] ) ) {
                $title = sanitize_text_field( $body['title'] );
            }
            if ( empty( $html ) && ! empty( $body['html'] ) ) {
                $html = $body['html'];
            }
        }
    }

    if ( empty( $slug ) || empty( $title ) || empty( $html ) ) {
        error_log( sprintf(
            '[InlineSyncPlugin] Payload inválido — slug: %s | title: %s | html_length: %d | content_type: %s',
            var_export( $slug, true ),
            var_export( $title, true ),
            strlen( (string) $html ),
            $request->get_header( 'content_type' ) ?? 'ausente'
        ) );

        return new WP_Error(
            'invalid_payload',
            'Os campos slug, title e html são obrigatórios e não podem estar vazios.',
            [
                'status'  => 422,
                'received' => [
                    'slug'       => empty( $slug )  ? 'vazio' : 'ok',
                    'title'      => empty( $title ) ? 'vazio' : 'ok',
                    'html'       => empty( $html )  ? 'vazio' : 'ok (' . strlen( (string) $html ) . ' chars)',
                    'content_type' => $request->get_header( 'content_type' ) ?? 'ausente',
                ],
            ]
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
