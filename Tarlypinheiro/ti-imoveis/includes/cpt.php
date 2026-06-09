<?php
defined('ABSPATH') || exit;

add_action('init', 'ti_register_cpt');

function ti_register_cpt(): void {
    register_post_type('imovel', [
        'labels' => [
            'name'          => 'Imóveis',
            'singular_name' => 'Imóvel',
            'add_new_item'  => 'Adicionar Imóvel',
            'edit_item'     => 'Editar Imóvel',
            'search_items'  => 'Buscar Imóveis',
            'not_found'     => 'Nenhum imóvel encontrado',
            'menu_name'     => 'Imóveis',
        ],
        'public'              => true,
        'has_archive'         => false,
        'rewrite'             => ['slug' => 'imovel', 'with_front' => false],
        'supports'            => ['title', 'thumbnail', 'excerpt'],
        'menu_icon'           => 'dashicons-building',
        'show_in_rest'        => true,
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'show_in_nav_menus'   => true,
        'show_in_menu'        => false, // gerenciado pelo plugin
        'capability_type'     => 'post',
    ]);

    // Negócio: Venda / Aluguel / Lançamento
    register_taxonomy('negocio', 'imovel', [
        'labels'       => ['name' => 'Negócio', 'singular_name' => 'Negócio', 'add_new_item' => 'Adicionar Negócio'],
        'hierarchical' => true,
        'rewrite'      => ['slug' => 'negocio'],
        'show_in_rest' => true,
        'show_in_menu' => false,
    ]);

    // Tipo: Casa / Apartamento / etc.
    register_taxonomy('tipo_imovel', 'imovel', [
        'labels'       => ['name' => 'Tipo de Imóvel', 'singular_name' => 'Tipo'],
        'hierarchical' => true,
        'rewrite'      => ['slug' => 'tipo'],
        'show_in_rest' => true,
        'show_in_menu' => false,
    ]);

    // Bairro
    register_taxonomy('bairro_imovel', 'imovel', [
        'labels'       => ['name' => 'Bairro', 'singular_name' => 'Bairro'],
        'hierarchical' => false,
        'rewrite'      => ['slug' => 'bairro'],
        'show_in_rest' => true,
        'show_in_menu' => false,
    ]);
}

// Seed termos padrão na primeira ativação
add_action('init', function () {
    if ( get_option('ti_terms_seeded') ) return;
    foreach (['Venda', 'Aluguel', 'Lançamento'] as $t) {
        if ( ! term_exists($t, 'negocio') ) wp_insert_term($t, 'negocio');
    }
    foreach (['Casa', 'Apartamento', 'Casa de Condomínio', 'Cobertura', 'Flat', 'Terreno', 'Comercial', 'Chácara'] as $t) {
        if ( ! term_exists($t, 'tipo_imovel') ) wp_insert_term($t, 'tipo_imovel');
    }
    update_option('ti_terms_seeded', 1);
}, 20);

// Colunas customizadas na listagem WP
add_filter('manage_imovel_posts_columns', function ($cols) {
    return [
        'cb'       => $cols['cb'],
        'title'    => 'Imóvel',
        'negocio'  => 'Negócio',
        'tipo'     => 'Tipo',
        'preco'    => 'Preço',
        'status_i' => 'Status',
        'date'     => 'Data',
    ];
});
add_action('manage_imovel_posts_custom_column', function ($col, $id) {
    switch ($col) {
        case 'negocio':
            $terms = get_the_terms($id, 'negocio');
            echo $terms ? esc_html(wp_list_pluck($terms, 'name')[0]) : '—';
            break;
        case 'tipo':
            $terms = get_the_terms($id, 'tipo_imovel');
            echo $terms ? esc_html(wp_list_pluck($terms, 'name')[0]) : '—';
            break;
        case 'preco':
            $v = get_post_meta($id, '_ti_valor_venda', true) ?: get_post_meta($id, '_ti_valor_aluguel', true);
            echo $v ? 'R$ ' . number_format((float)$v, 0, ',', '.') : '—';
            break;
        case 'status_i':
            $s = get_post_meta($id, '_ti_status', true) ?: 'disponivel';
            $labels = ['disponivel' => '🟢 Disponível', 'vendido' => '🔴 Vendido', 'alugado' => '🟡 Alugado', 'reservado' => '🔵 Reservado'];
            echo $labels[$s] ?? esc_html($s);
            break;
    }
}, 10, 2);
