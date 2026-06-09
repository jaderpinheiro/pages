<?php
defined('ABSPATH') || exit;

// ── Menu de Configurações ─────────────────────────────────────────────────────
add_action('admin_menu', function () {
    add_submenu_page('ti-dashboard', 'Configurações', 'Configurações', 'manage_options', 'ti-config', 'ti_page_config');
}, 30);

// ── Página ────────────────────────────────────────────────────────────────────
function ti_page_config(): void {
    if ( isset($_POST['_ti_save_config']) && check_admin_referer('ti_save_config_action') ) {
        $fields = [
            'ti_phone_wa'      => sanitize_text_field($_POST['phone_wa']      ?? ''),
            'ti_phone_display' => sanitize_text_field($_POST['phone_display'] ?? ''),
            'ti_creci'         => sanitize_text_field($_POST['creci']         ?? ''),
            'ti_site_email'    => sanitize_email($_POST['site_email']         ?? ''),
            'ti_msg_default'   => sanitize_textarea_field($_POST['msg_default'] ?? ''),
        ];
        foreach ($fields as $key => $val) update_option($key, $val);
        echo '<div class="notice notice-success is-dismissible"><p><strong>Configurações salvas!</strong></p></div>';
    }

    $phone_wa  = get_option('ti_phone_wa',      TI_PHONE);
    $phone_br  = get_option('ti_phone_display',  TI_PHONE_BR);
    $creci     = get_option('ti_creci',          TI_CRECI);
    $email     = get_option('ti_site_email',     'contato@tarlypinheiro.com.br');
    $msg       = get_option('ti_msg_default',    'Olá Tarly! Me chamo {nome}. {imovel}Gostaria de mais informações sobre imóveis. Meu celular: {celular}');
    ?>
    <div class="wrap ti-admin-wrap">
      <h1>Configurações — T Imóveis</h1>
      <form method="post">
        <?php wp_nonce_field('ti_save_config_action'); ?>
        <input type="hidden" name="_ti_save_config" value="1">

        <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:32px;align-items:start;margin-top:16px;">
          <!-- COLUNA ESQUERDA -->
          <div>
            <h2 style="font-size:15px;margin-bottom:14px;color:#0D1B2A;">WhatsApp &amp; Contato</h2>
            <div class="ti-form-card">
              <div class="ti-form-row">
                <label>Número WhatsApp <small style="color:#888;">(internacional, sem + ou espaços)</small></label>
                <input type="text" name="phone_wa" value="<?= esc_attr($phone_wa) ?>" placeholder="5562995219521">
                <small style="color:#888;">Formato: 55 + DDD + número. Ex: <code>5562995219521</code></small>
              </div>
              <div class="ti-form-row">
                <label>Número para exibição no site</label>
                <input type="text" name="phone_display" value="<?= esc_attr($phone_br) ?>" placeholder="(62) 9 9521-9521">
              </div>
              <div class="ti-form-row">
                <label>CRECI</label>
                <input type="text" name="creci" value="<?= esc_attr($creci) ?>" placeholder="CRECI 000000">
              </div>
              <div class="ti-form-row">
                <label>E-mail de contato</label>
                <input type="email" name="site_email" value="<?= esc_attr($email) ?>" placeholder="contato@empresa.com.br">
              </div>
            </div>

            <h2 style="font-size:15px;margin-bottom:14px;margin-top:24px;color:#0D1B2A;">Template da mensagem WhatsApp</h2>
            <div class="ti-form-card">
              <p style="font-size:13px;color:#555;margin-bottom:12px;line-height:1.6;">
                Variáveis disponíveis:
                <code style="background:#f0f4f8;padding:2px 6px;border-radius:4px;">{nome}</code> nome do lead &nbsp;
                <code style="background:#f0f4f8;padding:2px 6px;border-radius:4px;">{celular}</code> celular &nbsp;
                <code style="background:#f0f4f8;padding:2px 6px;border-radius:4px;">{imovel}</code> título + preço (se houver) &nbsp;
                <code style="background:#f0f4f8;padding:2px 6px;border-radius:4px;">{url}</code> link do imóvel
              </p>
              <div class="ti-form-row">
                <label>Mensagem padrão</label>
                <textarea name="msg_default" rows="5" id="ti_msg_tpl"><?= esc_textarea($msg) ?></textarea>
                <small style="color:#888;">Os dados do imóvel são <strong>sempre incluídos</strong> quando disponíveis (preenchidos via <code>{imovel}</code>).</small>
              </div>
            </div>
          </div>

          <!-- COLUNA DIREITA -->
          <div>
            <div class="ti-form-card" style="background:#EEF2FF;border-color:#C7D2FE;">
              <h3 style="font-size:13px;color:#3730A3;margin-bottom:10px;">💡 Lógica de mensagem</h3>
              <ul style="font-size:13px;color:#5046e5;line-height:1.8;padding-left:16px;">
                <li>Lead clica WA em um imóvel → template com <code>{imovel}</code> preenchido</li>
                <li>Lead clica WA geral (sem imóvel) → template sem dados de imóvel</li>
                <li>Sempre inclui nome + celular do lead</li>
              </ul>
            </div>

            <div class="ti-form-card" style="margin-top:20px;">
              <h3 style="font-size:14px;margin-bottom:10px;">Preview da mensagem</h3>
              <div id="ti_preview_box" style="background:#f5f5f5;padding:12px 14px;border-radius:8px;font-size:13px;line-height:1.7;white-space:pre-wrap;font-family:monospace;color:#333;min-height:80px;border:1px solid #E5EAF0;"></div>
              <button type="button" class="ti-btn-sm" style="margin-top:10px;" onclick="tiUpdatePreview()">🔄 Atualizar preview</button>
            </div>

            <div class="ti-form-card" style="margin-top:20px;background:#FFF7ED;border-color:#FED7AA;">
              <h3 style="font-size:13px;color:#92400E;margin-bottom:8px;">⚠️ Sobre o index.html</h3>
              <p style="font-size:12px;color:#78350F;line-height:1.7;">
                O arquivo <code>index.html</code> estático não usa estas configurações automaticamente — ele tem valores hardcoded. No WordPress (live), a página inicial é servida pelo template <code>templates/home.php</code> que já usa estas configurações em tempo real.
              </p>
            </div>
          </div>
        </div>

        <div style="margin-top:24px;">
          <button type="submit" class="ti-btn">💾 Salvar configurações</button>
        </div>
      </form>
    </div>

    <script>
    function tiUpdatePreview() {
      var tpl = document.getElementById('ti_msg_tpl').value;
      document.getElementById('ti_preview_box').textContent = tpl
        .replace(/{nome}/g,    'João Silva')
        .replace(/{celular}/g, '(62) 9 9999-9999')
        .replace(/{imovel}/g,  'Tenho interesse em: *Casa 3 quartos no Setor Bueno — R$ 550.000*. ')
        .replace(/{url}/g,     '<?= esc_js(home_url('/imoveis/')) ?>casa-setor-bueno');
    }
    tiUpdatePreview();
    document.getElementById('ti_msg_tpl').addEventListener('input', tiUpdatePreview);
    </script>
    <?php
}

// ── Funções para ler configurações ───────────────────────────────────────────
// Helpers com fallback nos defines do plugin
function ti_get_phone_wa(): string {
    return get_option('ti_phone_wa', TI_PHONE) ?: TI_PHONE;
}
function ti_get_phone_display(): string {
    return get_option('ti_phone_display', TI_PHONE_BR) ?: TI_PHONE_BR;
}
function ti_get_creci(): string {
    return get_option('ti_creci', TI_CRECI) ?: TI_CRECI;
}
function ti_get_msg_template(): string {
    return get_option('ti_msg_default',
        'Olá Tarly! Me chamo {nome}. {imovel}Gostaria de mais informações. Meu celular: {celular}'
    ) ?: 'Olá Tarly! Me chamo {nome}. {imovel}Gostaria de mais informações. Meu celular: {celular}';
}
