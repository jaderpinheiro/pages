<?php
/**
 * Plugin Name: InlineSyncPlugin
 * Plugin URI:  https://github.com/seu-repo/inline-sync-agent
 * Description: Recebe landing pages de origem externa e cria/atualiza páginas no WordPress automaticamente.
 * Version:     1.1.0
 * Author:      InlineSyncPlugin
 * License:     MIT
 * Requires PHP: 8.0
 * Requires at least: 6.0
 */

defined( 'ABSPATH' ) || exit;

define( 'ISA_VERSION', '1.1.0' );
define( 'ISA_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Invalida o OPcache apenas dos arquivos PHP do próprio plugin.
 * Compatível com PHP 5.5+ — não afeta outros sites no servidor.
 */
function isa_clear_plugin_opcache() {
    if ( ! function_exists( 'opcache_invalidate' ) ) {
        return;
    }

    // Verifica se o OPcache está ativo (silencia erros em hosts restritivos)
    if ( function_exists( 'opcache_get_status' ) ) {
        $status = @opcache_get_status( false );
        if ( empty( $status['opcache_enabled'] ) ) {
            return;
        }
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator( ISA_PATH, RecursiveDirectoryIterator::SKIP_DOTS )
    );

    foreach ( $files as $file ) {
        if ( $file->getExtension() === 'php' ) {
            @opcache_invalidate( $file->getPathname(), true );
        }
    }
}

// Limpa o cache ao ativar o plugin
register_activation_hook( __FILE__, 'isa_clear_plugin_opcache' );

require_once ISA_PATH . 'includes/security.php';
require_once ISA_PATH . 'includes/page-handler.php';
require_once ISA_PATH . 'includes/rest-deploy.php';
