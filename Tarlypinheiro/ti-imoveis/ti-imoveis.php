<?php
/**
 * Plugin Name: T Imóveis
 * Plugin URI:  https://tarlypinheiroimoveis.com.br
 * Description: Sistema completo de imóveis — CPT, galeria, CRM de leads e SEO avançado.
 * Version:     1.0.0
 * Author:      Tarly Pinheiro Imóveis
 * Text Domain: ti-imoveis
 * License:     GPL-2.0+
 */
defined('ABSPATH') || exit;

define('TI_VER',      '1.0.0');
define('TI_PATH',     plugin_dir_path(__FILE__));
define('TI_URL',      plugin_dir_url(__FILE__));
define('TI_PHONE',    '5562995219521');
define('TI_PHONE_BR', '(62) 9 9521-9521');
define('TI_CRECI',    'CRECI 105224');

foreach (['cpt', 'meta-boxes', 'leads', 'settings', 'admin-pages', 'frontend', 'ajax', 'sitemap', 'social'] as $m) {
    require_once TI_PATH . "includes/{$m}.php";
}

register_activation_hook(__FILE__, 'ti_activate');
register_deactivation_hook(__FILE__, static function () { flush_rewrite_rules(); });

function ti_activate(): void {
    ti_register_cpt();
    flush_rewrite_rules();
    ti_install_db();

    if ( ! get_page_by_path('imoveis') ) {
        wp_insert_post([
            'post_title'   => 'Imóveis',
            'post_name'    => 'imoveis',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '[ti_resultados]',
        ]);
    }
}

function ti_install_db(): void {
    global $wpdb;
    $c = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ti_leads (
        id          BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
        nome        VARCHAR(120)     NOT NULL,
        telefone    VARCHAR(25)      NOT NULL,
        email       VARCHAR(120)     DEFAULT '',
        imovel_id   BIGINT           DEFAULT 0,
        imovel_nome VARCHAR(300)     DEFAULT '',
        status      ENUM('lead','contato','negociacao','fechamento','noshow') DEFAULT 'lead',
        descricao   TEXT,
        enviou_docs TINYINT(1)       DEFAULT 0,
        indicou     VARCHAR(255)     DEFAULT '',
        created_at  DATETIME         DEFAULT CURRENT_TIMESTAMP,
        updated_at  DATETIME         DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_status (status),
        KEY idx_imovel (imovel_id)
    ) {$c};";

    $sql_social = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ti_social_posts (
        id         BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
        rede       VARCHAR(20)      DEFAULT 'instagram',
        link       VARCHAR(500)     DEFAULT '',
        image_id   BIGINT           DEFAULT 0,
        legenda    TEXT             DEFAULT '',
        alt_text   VARCHAR(300)     DEFAULT '',
        tags       VARCHAR(500)     DEFAULT '',
        created_at DATETIME         DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_rede (rede)
    ) {$c};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    dbDelta($sql_social);

    // Migração: adicionar coluna embed_html se a tabela social já existia
    $t_social = $wpdb->prefix . 'ti_social_posts';
    if ( $wpdb->get_var("SHOW TABLES LIKE '$t_social'")
         && $wpdb->get_var("SHOW COLUMNS FROM $t_social LIKE 'embed_html'") === null ) {
        $wpdb->query("ALTER TABLE $t_social ADD COLUMN embed_html MEDIUMTEXT DEFAULT ''");
    }
}
