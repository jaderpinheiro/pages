<?php
/**
 * Segurança — validação do Bearer Token.
 *
 * O token deve ser definido no wp-config.php:
 *   define( 'INLINE_SYNC_TOKEN', 'seu-token-secreto' );
 */

defined( 'ABSPATH' ) || exit;

/**
 * Valida o token Bearer enviado no header Authorization.
 *
 * @param WP_REST_Request $request
 * @return true|WP_Error
 */
function isa_validate_token( WP_REST_Request $request ) {
    if ( ! defined( 'INLINE_SYNC_TOKEN' ) || empty( INLINE_SYNC_TOKEN ) ) {
        return new WP_Error(
            'token_not_configured',
            'INLINE_SYNC_TOKEN não está configurado no servidor.',
            [ 'status' => 500 ]
        );
    }

    $auth_header = $request->get_header( 'Authorization' );

    if ( empty( $auth_header ) ) {
        return new WP_Error(
            'missing_token',
            'Token de autorização ausente.',
            [ 'status' => 401 ]
        );
    }

    // Aceita "Bearer <token>" ou apenas "<token>"
    $token = preg_replace( '/^Bearer\s+/i', '', trim( $auth_header ) );

    if ( ! hash_equals( INLINE_SYNC_TOKEN, $token ) ) {
        return new WP_Error(
            'invalid_token',
            'Token de autorização inválido.',
            [ 'status' => 401 ]
        );
    }

    return true;
}
