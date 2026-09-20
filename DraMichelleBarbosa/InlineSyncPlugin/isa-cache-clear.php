<?php
/**
 * ARQUIVO TEMPORÁRIO — APAGUE APÓS USAR.
 * Invalida o OPcache apenas dos arquivos PHP do InlineSyncPlugin.
 * Não afeta outros sites ou plugins no servidor.
 *
 * Como usar:
 *   https://seusite.com/wp-content/plugins/InlineSyncPlugin/isa-cache-clear.php?key=ISA_CLEAR
 *
 * Após acessar, apague este arquivo via FTP ou cPanel.
 */

if ( ( $_GET['key'] ?? '' ) !== 'ISA_CLEAR' ) {
    http_response_code( 403 );
    exit( 'Acesso negado. Use ?key=ISA_CLEAR' );
}

$plugin_dir = __DIR__;
$cleared    = [];
$failed     = [];
$skipped    = false;

if ( ! function_exists( 'opcache_invalidate' ) ) {
    exit( 'OPcache nao esta disponivel neste servidor. Nenhuma acao necessaria.' );
}

if ( function_exists( 'opcache_get_status' ) ) {
    $status = @opcache_get_status( false );
    if ( empty( $status['opcache_enabled'] ) ) {
        $skipped = true;
    }
}

if ( ! $skipped ) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator( $plugin_dir, RecursiveDirectoryIterator::SKIP_DOTS )
    );

    foreach ( $files as $file ) {
        if ( $file->getExtension() !== 'php' ) {
            continue;
        }
        $path   = $file->getPathname();
        $result = @opcache_invalidate( $path, true );
        if ( $result ) {
            $cleared[] = str_replace( $plugin_dir, '', $path );
        } else {
            $failed[] = str_replace( $plugin_dir, '', $path );
        }
    }
}

header( 'Content-Type: text/plain; charset=utf-8' );

if ( $skipped ) {
    echo "OPcache esta desativado no servidor. Nenhuma acao necessaria.\n";
} else {
    echo "=== OPcache limpo com sucesso ===\n";
    foreach ( $cleared as $f ) {
        echo "  OK  $f\n";
    }
    if ( $failed ) {
        echo "\n=== Falha ao limpar ===\n";
        foreach ( $failed as $f ) {
            echo "  FAIL  $f\n";
        }
    }
    echo "\nAPAGUE este arquivo do servidor agora.\n";
}
