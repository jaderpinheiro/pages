<?php
/**
 * ARQUIVO TEMPORÁRIO — APAGUE APÓS USAR.
 * Diagnóstico e limpeza de OPcache para InlineSyncPlugin.
 *
 * Acesse: https://seusite.com/wp-content/plugins/InlineSyncPlugin/isa-cache-clear.php?key=ISA_CLEAR
 */

if ( ( $_GET['key'] ?? '' ) !== 'ISA_CLEAR' ) {
    http_response_code( 403 );
    exit( 'Acesso negado. Use ?key=ISA_CLEAR' );
}

header( 'Content-Type: text/plain; charset=utf-8' );

$target = __DIR__ . '/includes/security.php';

echo "=== PHP VERSION ===\n";
echo PHP_VERSION . "\n\n";

echo "=== OPCACHE CONFIG ===\n";
if ( function_exists( 'opcache_get_configuration' ) ) {
    $cfg = opcache_get_configuration();
    $dir = $cfg['directives']['opcache.file_cache'] ?? '(não definido)';
    $val = $cfg['directives']['opcache.validate_timestamps'] ?? '(desconhecido)';
    $ena = $cfg['directives']['opcache.enable'] ?? false;
    echo "opcache.enable           = " . ( $ena ? 'On' : 'Off' ) . "\n";
    echo "opcache.validate_timestamps = $val\n";
    echo "opcache.file_cache       = $dir\n\n";
} else {
    echo "opcache_get_configuration() indisponivel\n\n";
    $dir = null;
}

echo "=== ARQUIVO NO DISCO ===\n";
if ( file_exists( $target ) ) {
    echo "Existe: SIM\n";
    echo "Modificado: " . date( 'Y-m-d H:i:s', filemtime( $target ) ) . "\n";
    echo "Tamanho: " . filesize( $target ) . " bytes\n";

    // Mostra linha 17 exata do arquivo em disco
    $lines = file( $target );
    echo "Linha 17 no disco: " . trim( $lines[16] ?? '(vazia)' ) . "\n\n";
} else {
    echo "ARQUIVO NAO ENCONTRADO: $target\n\n";
}

echo "=== LIMPEZA OPCACHE (memoria) ===\n";
if ( function_exists( 'opcache_invalidate' ) ) {
    $ok = @opcache_invalidate( $target, true );
    echo "opcache_invalidate(security.php, force=true): " . ( $ok ? 'OK' : 'FALHOU' ) . "\n\n";
} else {
    echo "opcache_invalidate() indisponivel\n\n";
}

echo "=== LIMPEZA FILE_CACHE (disco) ===\n";
if ( ! empty( $dir ) && $dir !== '(não definido)' && is_dir( $dir ) ) {
    // Percorre o diretório de file cache procurando arquivos relacionados ao plugin
    $found   = 0;
    $deleted = 0;
    $plugin_path = realpath( __DIR__ );
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS )
    );
    foreach ( $iterator as $f ) {
        // OPcache file cache não guarda o path original no nome, mas podemos
        // deletar tudo do diretório que corresponde ao UID atual
        if ( $f->getExtension() === 'bin' ) {
            $found++;
            if ( @unlink( $f->getPathname() ) ) {
                $deleted++;
            }
        }
    }
    echo "Arquivos .bin encontrados: $found\n";
    echo "Deletados: $deleted\n\n";
} else {
    echo "opcache.file_cache nao configurado ou inacessivel — sem acao.\n\n";
}

echo "=== TOQUE NO ARQUIVO (force recompile) ===\n";
$touched = @touch( $target );
echo "touch(security.php): " . ( $touched ? 'OK — timestamp atualizado' : 'FALHOU' ) . "\n\n";

echo "=== PRONTO ===\n";
echo "Acesse o site e teste. Depois APAGUE este arquivo.\n";
