<?php
defined('ABSPATH') || exit;

// ── Menu principal ────────────────────────────────────────────────────────────
add_action('admin_menu', function () {
    add_menu_page(
        'T Imóveis', 'T Imóveis', 'manage_options',
        'ti-dashboard', 'ti_page_dashboard',
        'dashicons-building', 25
    );
    add_submenu_page('ti-dashboard', 'Dashboard',       'Dashboard',       'manage_options', 'ti-dashboard',  'ti_page_dashboard');
    add_submenu_page('ti-dashboard', 'Todos os Imóveis','Todos os Imóveis','manage_options', 'edit.php?post_type=imovel', '');
    add_submenu_page('ti-dashboard', 'Adicionar Imóvel','Adicionar Imóvel','manage_options', 'post-new.php?post_type=imovel', '');
    add_submenu_page('ti-dashboard', 'Leads / CRM',     'Leads / CRM',     'manage_options', 'ti-leads',      'ti_page_leads');
    add_submenu_page('ti-dashboard', 'Redes Sociais',   'Redes Sociais',   'manage_options', 'ti-social',     'ti_page_social');
    add_submenu_page('ti-dashboard', 'Clientes / Leads','Clientes / Leads','manage_options', 'ti-clientes',   'ti_page_clientes');
    // 'ti-config' registrado por settings.php (prioridade 30)
});

// ── Admin assets ──────────────────────────────────────────────────────────────
add_action('admin_enqueue_scripts', function ($hook) {
    if ( ! in_array($hook, ['toplevel_page_ti-dashboard', 'timoveis_page_ti-leads']) &&
         ! str_contains($hook, 'ti-') ) {
        // Também carrega na edição do CPT
        if ( ! str_contains($hook, 'imovel') ) return;
    }
    wp_enqueue_media();
    wp_enqueue_style('ti-admin',  TI_URL . 'assets/css/admin.css',  [], TI_VER);
    wp_enqueue_script('ti-admin', TI_URL . 'assets/js/admin.js', ['jquery'], TI_VER, true);
    wp_localize_script('ti-admin', 'ti_admin', [
        'ajax'  => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ti_admin_nonce'),
    ]);
});

// ── Página: Dashboard ─────────────────────────────────────────────────────────
function ti_page_dashboard(): void {
    $total     = wp_count_posts('imovel')->publish ?? 0;
    $counts    = ti_leads_count_by_status();
    $total_leads = array_sum($counts);
    $recentes  = get_posts(['post_type'=>'imovel','posts_per_page'=>5,'post_status'=>'publish']);
    ?>
    <div class="wrap ti-admin-wrap">
      <h1>T Imóveis — Dashboard</h1>
      <div class="ti-stats-grid">
        <div class="ti-stat-card">
          <div class="ti-stat-num"><?= esc_html($total) ?></div>
          <div class="ti-stat-label">Imóveis Publicados</div>
        </div>
        <div class="ti-stat-card">
          <div class="ti-stat-num"><?= esc_html($total_leads) ?></div>
          <div class="ti-stat-label">Total de Leads</div>
        </div>
        <div class="ti-stat-card ti-stat-gold">
          <div class="ti-stat-num"><?= esc_html($counts['negociacao'] ?? 0) ?></div>
          <div class="ti-stat-label">Em Negociação</div>
        </div>
        <div class="ti-stat-card ti-stat-green">
          <div class="ti-stat-num"><?= esc_html($counts['fechamento'] ?? 0) ?></div>
          <div class="ti-stat-label">Fechamentos</div>
        </div>
      </div>

      <div class="ti-section-title">Imóveis recentes</div>
      <table class="ti-table">
        <thead><tr><th>Imóvel</th><th>Tipo</th><th>Preço</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($recentes as $p):
            $tipo  = get_the_terms($p->ID, 'tipo_imovel');
            $preco = get_post_meta($p->ID, '_ti_valor_venda', true) ?: get_post_meta($p->ID, '_ti_valor_aluguel', true);
            $st    = get_post_meta($p->ID, '_ti_status', true) ?: 'disponivel';
            $stMap = ['disponivel'=>'<span class="ti-badge green">Disponível</span>','vendido'=>'<span class="ti-badge red">Vendido</span>','alugado'=>'<span class="ti-badge gold">Alugado</span>','reservado'=>'<span class="ti-badge blue">Reservado</span>'];
          ?>
          <tr>
            <td><strong><?= esc_html($p->post_title) ?></strong></td>
            <td><?= $tipo ? esc_html(wp_list_pluck($tipo,'name')[0]) : '—' ?></td>
            <td><?= $preco ? 'R$ ' . number_format((float)$preco,0,',','.') : '—' ?></td>
            <td><?= $stMap[$st] ?? esc_html($st) ?></td>
            <td><a href="<?= esc_url(get_edit_post_link($p->ID)) ?>" class="ti-btn-sm">Editar</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p><a href="<?= admin_url('edit.php?post_type=imovel') ?>" class="ti-btn">Ver todos os imóveis →</a></p>
    </div>
    <?php
}

// ── Página: Leads / CRM ───────────────────────────────────────────────────────
function ti_page_leads(): void {
    $grouped = ti_get_leads_grouped();
    $cols    = [
        'lead'        => ['label' => 'Leads',       'color' => '#0D1B2A'],
        'contato'     => ['label' => 'Em Contato',  'color' => '#2563EB'],
        'negociacao'  => ['label' => 'Negociação',  'color' => '#D97706'],
        'fechamento'  => ['label' => 'Fechamento',  'color' => '#059669'],
        'noshow'      => ['label' => 'No-show',     'color' => '#DC2626'],
    ];
    ?>
    <div class="wrap ti-admin-wrap">
      <h1>Leads / CRM</h1>
      <p class="ti-hint">Arraste os cartões entre as colunas para atualizar o status. Clique em um cartão para ver detalhes.</p>

      <div class="ti-kanban">
        <?php foreach ($cols as $status => $cfg): ?>
        <div class="ti-kanban-col" data-status="<?= $status ?>">
          <div class="ti-col-header" style="border-color:<?= esc_attr($cfg['color']) ?>;">
            <span class="ti-col-title"><?= esc_html($cfg['label']) ?></span>
            <span class="ti-col-count" id="count-<?= $status ?>"><?= count($grouped[$status]) ?></span>
          </div>
          <div class="ti-col-body" id="col-<?= $status ?>" ondragover="event.preventDefault()" ondrop="tiDrop(event,'<?= $status ?>')">
            <?php foreach ($grouped[$status] as $lead): ?>
            <div class="ti-lead-card" draggable="true"
                 data-id="<?= (int)$lead->id ?>"
                 ondragstart="tiDragStart(event,<?= (int)$lead->id ?>)"
                 onclick="tiOpenLead(<?= (int)$lead->id ?>)">
              <div class="ti-lead-name"><?= esc_html($lead->nome) ?></div>
              <div class="ti-lead-tel">📞 <?= esc_html($lead->telefone) ?></div>
              <?php if ($lead->imovel_nome): ?>
              <div class="ti-lead-imovel">🏠 <?= esc_html(wp_trim_words($lead->imovel_nome, 6)) ?></div>
              <?php endif; ?>
              <div class="ti-lead-date"><?= esc_html(date_i18n('d/m/Y H:i', strtotime($lead->created_at))) ?></div>
              <?php if ($lead->enviou_docs): ?><span class="ti-lead-tag">📄 Docs enviados</span><?php endif; ?>
              <?php if ($lead->indicou): ?><span class="ti-lead-tag">👥 Indicou</span><?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- MODAL LEAD DETAIL -->
    <div id="tiLeadModal" class="ti-modal" style="display:none;">
      <div class="ti-modal-box">
        <button class="ti-modal-close" onclick="document.getElementById('tiLeadModal').style.display='none'">✕</button>
        <h2 id="tiLeadModalTitle">Lead</h2>
        <div class="ti-modal-grid">
          <div><strong>Nome:</strong> <span id="tl-nome"></span></div>
          <div><strong>Telefone:</strong> <span id="tl-tel"></span></div>
          <div><strong>E-mail:</strong> <span id="tl-email"></span></div>
          <div><strong>Imóvel:</strong> <span id="tl-imovel"></span></div>
          <div><strong>Data:</strong> <span id="tl-date"></span></div>
          <div><strong>Status:</strong> <span id="tl-status"></span></div>
        </div>
        <hr>
        <label><strong>Descrição / Observações</strong>
          <textarea id="tl-desc" rows="4" style="width:100%;margin-top:6px;padding:8px;border:1px solid #ddd;border-radius:6px;"></textarea>
        </label>
        <div style="display:flex;gap:16px;align-items:center;margin:12px 0;">
          <label><input type="checkbox" id="tl-docs"> Enviou documentação</label>
          <label>Indicou alguém: <input type="text" id="tl-indicou" style="padding:6px 10px;border:1px solid #ddd;border-radius:6px;margin-left:8px;width:200px;"></label>
        </div>
        <button class="ti-btn" onclick="tiSaveLead()">💾 Salvar alterações</button>
        <a id="tl-wa-btn" href="#" target="_blank" class="ti-btn ti-btn-green" style="text-decoration:none;">💬 Abrir no WhatsApp</a>
      </div>
    </div>

    <script>
    var _tiCurrentLeadId = null;
    var _tiLeadsData = <?php
        $all = [];
        foreach ($grouped as $leads) foreach ($leads as $l) $all[$l->id] = $l;
        echo wp_json_encode($all, JSON_UNESCAPED_UNICODE);
    ?>;

    function tiDragStart(e, id) { e.dataTransfer.setData('leadId', id); }
    function tiDrop(e, status) {
        e.preventDefault();
        var id = e.dataTransfer.getData('leadId');
        fetch(ti_admin.ajax, {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action:'ti_move_lead', nonce:ti_admin.nonce, id, status})
        }).then(r=>r.json()).then(function(res){
            if(res.success) location.reload();
        });
    }
    function tiOpenLead(id) {
        _tiCurrentLeadId = id;
        var l = _tiLeadsData[id];
        if(!l) return;
        document.getElementById('tiLeadModalTitle').textContent = 'Lead: ' + l.nome;
        document.getElementById('tl-nome').textContent    = l.nome;
        document.getElementById('tl-tel').textContent     = l.telefone;
        document.getElementById('tl-email').textContent   = l.email || '—';
        document.getElementById('tl-imovel').textContent  = l.imovel_nome || '—';
        document.getElementById('tl-date').textContent    = l.created_at;
        document.getElementById('tl-status').textContent  = l.status;
        document.getElementById('tl-desc').value          = l.descricao || '';
        document.getElementById('tl-docs').checked        = l.enviou_docs == 1;
        document.getElementById('tl-indicou').value       = l.indicou || '';
        document.getElementById('tl-wa-btn').href         = 'https://wa.me/' + l.telefone.replace(/\D/g,'');
        document.getElementById('tiLeadModal').style.display = 'flex';
    }
    function tiSaveLead() {
        if(!_tiCurrentLeadId) return;
        fetch(ti_admin.ajax, {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action:'ti_update_lead', nonce:ti_admin.nonce,
                id: _tiCurrentLeadId,
                descricao: document.getElementById('tl-desc').value,
                enviou_docs: document.getElementById('tl-docs').checked ? 1 : 0,
                indicou: document.getElementById('tl-indicou').value
            })
        }).then(r=>r.json()).then(function(res){
            if(res.success) { document.getElementById('tiLeadModal').style.display='none'; location.reload(); }
            else alert('Erro ao salvar.');
        });
    }
    </script>
    <?php
}

// ── Página: Clientes / Leads ─────────────────────────────────────────────────
function ti_page_clientes(): void {
    $busca  = sanitize_text_field($_GET['busca']  ?? '');
    $status = sanitize_key($_GET['status'] ?? '');
    $leads  = ti_search_leads($busca, $status, 500);

    // URL de exportação
    $export_url = add_query_arg([
        'action' => 'ti_export_leads',
        'nonce'  => wp_create_nonce('ti_admin_nonce'),
        'status' => $status,
    ], admin_url('admin-ajax.php'));

    // Imóveis para o disparador
    $imoveis_disp = get_posts(['post_type'=>'imovel','post_status'=>'publish','posts_per_page'=>30,'orderby'=>'date','order'=>'DESC']);

    $st_labels = ['lead'=>'Lead','contato'=>'Em Contato','negociacao'=>'Negociação','fechamento'=>'Fechamento','noshow'=>'No-show'];
    $st_colors = ['lead'=>'#0D1B2A','contato'=>'#2563EB','negociacao'=>'#D97706','fechamento'=>'#059669','noshow'=>'#DC2626'];
    ?>
    <div class="wrap ti-admin-wrap">
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
        <h1 style="margin:0;">Clientes / Leads</h1>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
          <a href="<?= esc_url($export_url) ?>" class="ti-btn" style="background:#059669;text-decoration:none;">
            ⬇ Exportar CSV
          </a>
          <button class="ti-btn" onclick="document.getElementById('ti_import_box').style.display=document.getElementById('ti_import_box').style.display==='none'?'flex':'none'">
            ⬆ Importar CSV
          </button>
        </div>
      </div>

      <!-- IMPORTAR -->
      <div id="ti_import_box" style="display:none;align-items:center;gap:12px;background:#EEF2FF;padding:14px 20px;border-radius:10px;border:1px solid #C7D2FE;margin-bottom:16px;flex-wrap:wrap;">
        <span style="font-size:13px;font-weight:600;color:#3730A3;">Importar leads (CSV/Excel):</span>
        <input type="file" id="ti_csv_file" accept=".csv,.txt" style="font-size:13px;">
        <button class="ti-btn-sm" onclick="tiImportCSV()">Importar</button>
        <span id="ti_import_result" style="font-size:13px;"></span>
        <small style="color:#5046e5;">Colunas: <code>ID ; Nome ; Telefone ; E-mail ; Imóvel</code> (a primeira linha é ignorada)</small>
      </div>

      <!-- FILTROS -->
      <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
        <input type="hidden" name="page" value="ti-clientes">
        <input type="text" name="busca" value="<?= esc_attr($busca) ?>" placeholder="Buscar nome, telefone, e-mail..." style="padding:8px 14px;border:1.5px solid #E5EAF0;border-radius:8px;font-size:14px;flex:1;min-width:200px;outline:none;">
        <select name="status" style="padding:8px 14px;border:1.5px solid #E5EAF0;border-radius:8px;font-size:14px;outline:none;">
          <option value="">Todos os status</option>
          <?php foreach ($st_labels as $k => $l): ?>
          <option value="<?= $k ?>" <?= selected($status,$k,false) ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="ti-btn-sm">Buscar</button>
        <?php if ($busca || $status): ?>
        <a href="<?= admin_url('admin.php?page=ti-clientes') ?>" class="ti-btn-sm" style="background:#f0f4f8;color:#333;text-decoration:none;">Limpar</a>
        <?php endif; ?>
      </form>

      <p class="ti-hint"><?= count($leads) ?> lead(s) encontrado(s). <span id="ti_sel_count" style="font-weight:600;color:#0D1B2A;"></span></p>

      <!-- TABELA -->
      <table class="ti-table" style="width:100%;">
        <thead>
          <tr>
            <th><input type="checkbox" id="ti_sel_all" onchange="tiToggleAll(this.checked)"></th>
            <th>Nome</th><th>Telefone</th><th>E-mail</th><th>Imóvel</th>
            <th>Status</th><th>Data</th><th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($leads as $l): ?>
          <tr>
            <td><input type="checkbox" class="ti-lead-sel" value="<?= (int)$l->id ?>"
                data-nome="<?= esc_attr($l->nome) ?>"
                data-tel="<?= esc_attr($l->telefone) ?>"
                data-email="<?= esc_attr($l->email) ?>"
                onchange="tiUpdateSel()"></td>
            <td><strong><?= esc_html($l->nome) ?></strong></td>
            <td><?= esc_html($l->telefone) ?></td>
            <td><?= esc_html($l->email ?: '—') ?></td>
            <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= esc_html($l->imovel_nome ?: '—') ?></td>
            <td><span class="ti-badge" style="background:<?= $st_colors[$l->status] ?? '#666' ?>;color:white;"><?= esc_html($st_labels[$l->status] ?? $l->status) ?></span></td>
            <td style="white-space:nowrap;font-size:12px;"><?= esc_html(date_i18n('d/m/Y H:i', strtotime($l->created_at))) ?></td>
            <td style="white-space:nowrap;">
              <a href="https://wa.me/<?= esc_attr(preg_replace('/\D/','',$l->telefone)) ?>"
                 target="_blank" class="ti-btn-sm" style="background:#25D366;">💬 WA</a>
              <button class="ti-btn-sm" onclick="tiOpenLead(<?= (int)$l->id ?>)" title="Ver detalhes">✏</button>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$leads): ?><tr><td colspan="8" style="text-align:center;color:#888;padding:24px;">Nenhum lead encontrado.</td></tr><?php endif; ?>
        </tbody>
      </table>

      <!-- DISPARADOR WA ─────────────────────────────────────────────────── -->
      <div id="ti_disparador" style="display:none;margin-top:24px;">
        <div style="background:linear-gradient(135deg,#0D1B2A,#1A2F45);border-radius:16px;padding:28px;color:white;">
          <h2 style="font-size:18px;margin:0 0 6px;">📲 Disparador WhatsApp em Lote</h2>
          <p style="font-size:13px;opacity:.7;margin-bottom:20px;">
            Selecione leads acima e configure a mensagem. O sistema abrirá o WhatsApp para cada contato com intervalo aleatório de 1–3 min entre envios.
          </p>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
            <div>
              <label style="font-size:12px;opacity:.7;display:block;margin-bottom:6px;">Template da mensagem</label>
              <textarea id="ti_disp_msg" rows="5" style="width:100%;padding:12px;border-radius:8px;font-size:13px;border:none;font-family:inherit;line-height:1.6;color:#0D1B2A;">Olá {nome}! 😊 Aqui é a Tarly Pinheiro Imóveis - <?= TI_CRECI ?>.

Estou com uma oportunidade que pode te interessar:
{oferta}

Acesse nosso portfólio: {url}

Podemos conversar sobre a sua busca?</textarea>
              <small style="opacity:.6;font-size:11px;">Variáveis: <code>{nome}</code> <code>{oferta}</code> <code>{url}</code></small>
            </div>
            <div>
              <label style="font-size:12px;opacity:.7;display:block;margin-bottom:6px;">Imóvel para oferta (opcional)</label>
              <select id="ti_disp_imovel" style="width:100%;padding:10px 14px;border-radius:8px;font-size:13px;border:none;margin-bottom:10px;" onchange="tiUpdateOferta()">
                <option value="">— Sem imóvel específico —</option>
                <?php foreach ($imoveis_disp as $im):
                  $price = get_post_meta($im->ID,'_ti_valor_venda',true) ?: get_post_meta($im->ID,'_ti_valor_aluguel',true);
                ?>
                <option value="<?= esc_attr(get_permalink($im->ID)) ?>"
                        data-title="<?= esc_attr($im->post_title) ?>"
                        data-price="<?= $price ? 'R$ ' . number_format((float)$price,0,',','.') : '' ?>">
                  <?= esc_html($im->post_title) ?><?= $price ? ' — R$ ' . number_format((float)$price,0,',','.') : '' ?>
                </option>
                <?php endforeach; ?>
              </select>
              <label style="font-size:12px;opacity:.7;display:block;margin-bottom:6px;">Intervalo entre envios</label>
              <div style="display:flex;gap:8px;align-items:center;">
                <input type="number" id="ti_disp_min" value="60"  min="30"  max="600" style="width:70px;padding:8px;border-radius:6px;border:none;text-align:center;font-size:14px;">
                <span style="opacity:.7;">a</span>
                <input type="number" id="ti_disp_max" value="180" min="30"  max="600" style="width:70px;padding:8px;border-radius:6px;border:none;text-align:center;font-size:14px;">
                <span style="opacity:.7;font-size:12px;">segundos</span>
              </div>
              <p style="font-size:11px;opacity:.5;margin-top:8px;">⚠ O WA Web deve estar logado no mesmo navegador.</p>
            </div>
          </div>
          <div id="ti_disp_progress" style="display:none;background:rgba(255,255,255,.1);border-radius:10px;padding:16px;margin-bottom:16px;">
            <div style="font-size:13px;opacity:.8;margin-bottom:6px;">Próximo contato:</div>
            <div id="ti_disp_contact_name" style="font-size:18px;font-weight:700;"></div>
            <div id="ti_disp_contact_tel"  style="font-size:13px;opacity:.7;"></div>
            <div style="margin-top:12px;">
              <div style="font-size:28px;font-weight:700;font-family:monospace;" id="ti_disp_countdown">0:00</div>
              <div style="background:rgba(255,255,255,.15);border-radius:4px;height:6px;margin-top:8px;overflow:hidden;">
                <div id="ti_disp_bar" style="height:100%;background:var(--gold);transition:width 1s linear;width:100%;"></div>
              </div>
            </div>
          </div>
          <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button class="ti-btn" style="background:#25D366;font-size:15px;padding:12px 28px;" onclick="tiStartDisparador()" id="ti_disp_start_btn">
              📲 Iniciar Disparo (<span id="ti_disp_sel_num">0</span> contatos)
            </button>
            <button class="ti-btn" style="background:#DC2626;display:none;" id="ti_disp_stop_btn" onclick="tiStopDisparador()">
              ⏹ Pausar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal lead (reutiliza JS do kanban) -->
    <div id="tiLeadModal" class="ti-modal" style="display:none;">
      <div class="ti-modal-box">
        <button class="ti-modal-close" onclick="document.getElementById('tiLeadModal').style.display='none'">✕</button>
        <h2 id="tiLeadModalTitle">Lead</h2>
        <div class="ti-modal-grid">
          <div><strong>Nome:</strong> <span id="tl-nome"></span></div>
          <div><strong>Telefone:</strong> <span id="tl-tel"></span></div>
          <div><strong>E-mail:</strong> <span id="tl-email"></span></div>
          <div><strong>Imóvel:</strong> <span id="tl-imovel"></span></div>
          <div><strong>Data:</strong> <span id="tl-date"></span></div>
          <div><strong>Status:</strong> <span id="tl-status"></span></div>
        </div>
        <hr>
        <label><strong>Observações</strong>
          <textarea id="tl-desc" rows="3" style="width:100%;margin-top:6px;padding:8px;border:1px solid #ddd;border-radius:6px;"></textarea>
        </label>
        <div style="display:flex;gap:16px;align-items:center;margin:10px 0;">
          <label><input type="checkbox" id="tl-docs"> Enviou documentação</label>
          <label>Indicou alguém: <input type="text" id="tl-indicou" style="padding:6px;border:1px solid #ddd;border-radius:6px;margin-left:6px;width:180px;"></label>
        </div>
        <div style="display:flex;gap:8px;">
          <button class="ti-btn" onclick="tiSaveLead()">💾 Salvar</button>
          <a id="tl-wa-btn" href="#" target="_blank" class="ti-btn ti-btn-green" style="text-decoration:none;">💬 WhatsApp</a>
        </div>
      </div>
    </div>

    <script>
    // ── Dados dos leads para modal ────────────────────────────────────────────
    var _tiLeadsData = <?php
        $all = []; foreach($leads as $l) $all[(int)$l->id] = $l;
        echo wp_json_encode($all, JSON_UNESCAPED_UNICODE);
    ?>;
    var _tiCurrentLeadId = null;

    // ── Seleção ───────────────────────────────────────────────────────────────
    function tiToggleAll(v) {
        document.querySelectorAll('.ti-lead-sel').forEach(function(c){ c.checked = v; });
        tiUpdateSel();
    }
    function tiUpdateSel() {
        var checked = document.querySelectorAll('.ti-lead-sel:checked').length;
        var c = document.getElementById('ti_sel_count');
        if (c) c.textContent = checked > 0 ? checked + ' selecionado(s)' : '';
        document.getElementById('ti_disparador').style.display = checked > 0 ? 'block' : 'none';
        var n = document.getElementById('ti_disp_sel_num');
        if (n) n.textContent = checked;
    }

    // ── Modal de lead ─────────────────────────────────────────────────────────
    function tiOpenLead(id) {
        _tiCurrentLeadId = id;
        var l = _tiLeadsData[id];
        if (!l) return;
        document.getElementById('tiLeadModalTitle').textContent = 'Lead: ' + l.nome;
        document.getElementById('tl-nome').textContent   = l.nome;
        document.getElementById('tl-tel').textContent    = l.telefone;
        document.getElementById('tl-email').textContent  = l.email || '—';
        document.getElementById('tl-imovel').textContent = l.imovel_nome || '—';
        document.getElementById('tl-date').textContent   = l.created_at;
        document.getElementById('tl-status').textContent = l.status;
        document.getElementById('tl-desc').value         = l.descricao || '';
        document.getElementById('tl-docs').checked       = l.enviou_docs == 1;
        document.getElementById('tl-indicou').value      = l.indicou || '';
        document.getElementById('tl-wa-btn').href        = 'https://wa.me/' + l.telefone.replace(/\D/g,'');
        document.getElementById('tiLeadModal').style.display = 'flex';
    }
    function tiSaveLead() {
        if (!_tiCurrentLeadId) return;
        fetch(ti_admin.ajax, {
            method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body: new URLSearchParams({ action:'ti_update_lead', nonce:ti_admin.nonce,
                id:_tiCurrentLeadId,
                descricao: document.getElementById('tl-desc').value,
                enviou_docs: document.getElementById('tl-docs').checked ? 1 : 0,
                indicou: document.getElementById('tl-indicou').value })
        }).then(r=>r.json()).then(function(res){
            if(res.success) document.getElementById('tiLeadModal').style.display='none';
            else alert('Erro ao salvar.');
        });
    }
    document.addEventListener('keydown',function(e){ if(e.key==='Escape') document.getElementById('tiLeadModal').style.display='none'; });

    // ── Importar CSV ──────────────────────────────────────────────────────────
    function tiImportCSV() {
        var file = document.getElementById('ti_csv_file').files[0];
        if (!file) { alert('Selecione um arquivo CSV.'); return; }
        var form = new FormData();
        form.append('action','ti_import_leads'); form.append('nonce',ti_admin.nonce);
        form.append('csv_file', file);
        var res = document.getElementById('ti_import_result');
        res.textContent = 'Importando…';
        fetch(ti_admin.ajax, { method:'POST', body: form })
        .then(r=>r.json()).then(function(data){
            if(data.success){ res.style.color='#059669'; res.textContent='✓ '+data.data.imported+' leads importados, '+data.data.skipped+' ignorados.'; setTimeout(function(){location.reload();},1500); }
            else { res.style.color='#DC2626'; res.textContent='Erro: '+(data.data||'desconhecido'); }
        });
    }

    // ── Atualizar {oferta} no template ────────────────────────────────────────
    function tiUpdateOferta() {
        var sel = document.getElementById('ti_disp_imovel');
        var opt = sel.options[sel.selectedIndex];
        var tpl = document.getElementById('ti_disp_msg');
        if (!tpl._originalTpl) tpl._originalTpl = tpl.value;
        if (opt && opt.value) {
            var title = opt.getAttribute('data-title');
            var price = opt.getAttribute('data-price');
            tpl.value = tpl._originalTpl.replace(/{oferta}/g, '*' + title + (price ? ' — ' + price : '') + '*\n' + opt.value);
        } else {
            tpl.value = tpl._originalTpl;
        }
    }

    // ── Disparador WA ─────────────────────────────────────────────────────────
    var _CAMP_KEY = 'ti_wa_campaign_v2';
    var _dispTimer = null;

    function tiGetSelectedContacts() {
        var contacts = [];
        document.querySelectorAll('.ti-lead-sel:checked').forEach(function(c){
            contacts.push({ id:c.value, nome:c.getAttribute('data-nome'), tel:c.getAttribute('data-tel') });
        });
        return contacts;
    }

    function tiStartDisparador() {
        var contacts = tiGetSelectedContacts();
        if (!contacts.length) { alert('Selecione pelo menos um lead.'); return; }
        var msgTpl = document.getElementById('ti_disp_msg').value;
        var minSec = parseInt(document.getElementById('ti_disp_min').value) || 60;
        var maxSec = parseInt(document.getElementById('ti_disp_max').value) || 180;
        var siteUrl = window.location.origin;
        var campaign = { contacts:contacts, idx:0, msgTpl:msgTpl, minSec:minSec, maxSec:maxSec, siteUrl:siteUrl };
        sessionStorage.setItem(_CAMP_KEY, JSON.stringify(campaign));
        tiRunNext();
    }

    function tiRunNext() {
        var camp = JSON.parse(sessionStorage.getItem(_CAMP_KEY) || 'null');
        if (!camp || camp.idx >= camp.contacts.length) {
            sessionStorage.removeItem(_CAMP_KEY);
            document.getElementById('ti_disp_progress').style.display = 'none';
            document.getElementById('ti_disp_start_btn').style.display = '';
            document.getElementById('ti_disp_stop_btn').style.display = 'none';
            if (camp) alert('✅ Disparo finalizado! ' + camp.contacts.length + ' mensagens enviadas.');
            return;
        }
        var c = camp.contacts[camp.idx];
        var delay = Math.floor(Math.random() * (camp.maxSec - camp.minSec + 1)) + camp.minSec;
        document.getElementById('ti_disp_contact_name').textContent = (camp.idx + 1) + '/' + camp.contacts.length + ' — ' + c.nome;
        document.getElementById('ti_disp_contact_tel').textContent  = c.tel;
        document.getElementById('ti_disp_progress').style.display   = 'block';
        document.getElementById('ti_disp_start_btn').style.display  = 'none';
        document.getElementById('ti_disp_stop_btn').style.display   = '';

        var remaining = delay;
        var bar = document.getElementById('ti_disp_bar');
        if (bar) bar.style.width = '100%';

        clearInterval(_dispTimer);
        _dispTimer = setInterval(function() {
            remaining--;
            var m = Math.floor(remaining / 60);
            var s = remaining % 60;
            var cd = document.getElementById('ti_disp_countdown');
            if (cd) cd.textContent = m + ':' + (s < 10 ? '0' : '') + s;
            if (bar) bar.style.width = ((remaining / delay) * 100) + '%';
            if (remaining <= 0) {
                clearInterval(_dispTimer);
                tiSendCurrent(camp, c);
            }
        }, 1000);
    }

    function tiSendCurrent(camp, c) {
        var siteUrl = '<?= esc_js(home_url('/')) ?>';
        var msg = camp.msgTpl
            .replace(/{nome}/g, c.nome)
            .replace(/{url}/g, siteUrl);
        var phone = c.tel.replace(/\D/g, '');
        if (phone.length <= 11) phone = '55' + phone;
        // Avançar índice antes de navegar
        camp.idx++;
        sessionStorage.setItem(_CAMP_KEY, JSON.stringify(camp));
        // Redirecionar para WA na mesma aba (não cria nova janela = sem bloqueio)
        window.location.href = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(msg);
    }

    function tiStopDisparador() {
        clearInterval(_dispTimer);
        sessionStorage.removeItem(_CAMP_KEY);
        document.getElementById('ti_disp_progress').style.display = 'none';
        document.getElementById('ti_disp_start_btn').style.display = '';
        document.getElementById('ti_disp_stop_btn').style.display = 'none';
    }

    // Retomar campanha ao voltar do WA
    (function() {
        var camp = JSON.parse(sessionStorage.getItem(_CAMP_KEY) || 'null');
        if (camp && camp.idx > 0) {
            // Mostrar disparador e retomar
            tiUpdateSel();
            document.getElementById('ti_disparador').style.display = 'block';
            // Marcar todos como selecionados para UI (não precisa ser exato)
            document.getElementById('ti_disp_sel_num').textContent = camp.contacts.length;
            // Aguardar 2s para feedback visual antes de retomar
            setTimeout(tiRunNext, 2000);
        }
    })();
    </script>
    <?php
}

// ── Página: Redes Sociais ────────────────────────────────────────────────────
function ti_page_social(): void {
    $posts  = ti_social_list('', 50);
    $redes  = ['instagram' => 'Instagram', 'facebook' => 'Facebook', 'youtube' => 'YouTube', 'tiktok' => 'TikTok'];
    $edit   = null;
    if ( ! empty($_GET['edit']) ) $edit = ti_social_get((int)$_GET['edit']);
    ?>
    <div class="wrap ti-admin-wrap">
      <h1>Redes Sociais</h1>
      <p class="ti-hint">Gerencie as postagens das redes sociais exibidas no site. Todas incluídas no sitemap XML para SEO.</p>

      <div style="display:grid;grid-template-columns:1.3fr 1fr;gap:32px;align-items:start;">

        <!-- LISTA DE POSTS -->
        <div>
          <h2 style="font-size:16px;margin-bottom:12px;">Posts cadastrados</h2>
          <?php if ($posts): ?>
          <table class="ti-table" style="width:100%;">
            <thead>
              <tr><th>Imagem</th><th>Rede</th><th>Legenda</th><th>Alt text</th><th>Tags</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($posts as $p):
                $img_url = $p->image_id ? wp_get_attachment_image_url($p->image_id, 'thumbnail') : '';
              ?>
              <tr>
                <td>
                  <?php if ($img_url): ?>
                  <img src="<?= esc_url($img_url) ?>" style="width:60px;height:44px;object-fit:cover;border-radius:6px;">
                  <?php else: ?>
                  <span style="color:#999">Sem imagem</span>
                  <?php endif; ?>
                </td>
                <td><?= esc_html(ucfirst($p->rede)) ?></td>
                <td style="max-width:200px;"><?= esc_html(mb_substr($p->legenda, 0, 60)) . (mb_strlen($p->legenda) > 60 ? '…' : '') ?></td>
                <td><?= esc_html(mb_substr($p->alt_text, 0, 40)) ?></td>
                <td><?= esc_html(mb_substr($p->tags, 0, 40)) ?></td>
                <td style="white-space:nowrap;">
                  <a href="<?= esc_url(add_query_arg(['page'=>'ti-social','edit'=>$p->id], admin_url('admin.php'))) ?>" class="ti-btn-sm">Editar</a>
                  <button class="ti-btn-sm ti-btn-red" onclick="tiSocialDel(<?= (int)$p->id ?>)">Excluir</button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php else: ?>
          <p style="color:#888;padding:16px 0;">Nenhum post cadastrado ainda.</p>
          <?php endif; ?>
        </div>

        <!-- FORMULÁRIO -->
        <div>
          <h2 style="font-size:16px;margin-bottom:12px;"><?= $edit ? 'Editar post' : 'Adicionar novo post' ?></h2>
          <div class="ti-form-card">
            <input type="hidden" id="ti_soc_id" value="<?= $edit ? (int)$edit->id : 0 ?>">

            <div class="ti-form-row">
              <label>Rede Social</label>
              <select id="ti_soc_rede">
                <?php foreach ($redes as $k => $l): ?>
                <option value="<?= esc_attr($k) ?>"<?= ($edit && $edit->rede === $k) ? ' selected' : '' ?>><?= esc_html($l) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="ti-form-row">
              <label>Link do post (URL) <small style="color:#888;">— Instagram, TikTok, YouTube, Facebook, Vimeo…</small></label>
              <div style="display:flex;gap:8px;align-items:center;">
                <input type="url" id="ti_soc_link" placeholder="https://www.instagram.com/p/..." value="<?= $edit ? esc_attr($edit->link) : '' ?>" style="flex:1;">
                <button type="button" class="ti-btn-sm" onclick="tiSocialPreviewEmbed()" title="Gerar preview do embed">▶ Preview</button>
              </div>
              <div id="ti_soc_embed_preview" style="margin-top:10px;display:<?= ($edit && !empty($edit->embed_html)) ? 'block' : 'none' ?>;background:#f8fafc;padding:12px;border-radius:8px;border:1px solid #E5EAF0;max-height:400px;overflow:auto;">
                <?= $edit ? $edit->embed_html : '' ?>
              </div>
              <small style="color:#888;">Cole a URL do post público. O embed HTML é gerado e armazenado automaticamente ao salvar.</small>
            </div>

            <div class="ti-form-row">
              <label>Imagem</label>
              <div style="display:flex;gap:10px;align-items:center;">
                <div id="ti_soc_preview" style="width:80px;height:60px;background:#f0f4f8;border-radius:6px;overflow:hidden;border:1px solid #ddd;">
                  <?php if ($edit && $edit->image_id):
                    $prev = wp_get_attachment_image_url($edit->image_id, 'thumbnail'); ?>
                  <img id="ti_soc_prev_img" src="<?= esc_url($prev) ?>" style="width:100%;height:100%;object-fit:cover;">
                  <?php else: ?>
                  <img id="ti_soc_prev_img" src="" style="width:100%;height:100%;object-fit:cover;display:none;">
                  <?php endif; ?>
                </div>
                <input type="hidden" id="ti_soc_image_id" value="<?= $edit ? (int)$edit->image_id : 0 ?>">
                <button type="button" class="ti-btn-sm" onclick="tiSocialPickImage()">Escolher imagem</button>
                <?php if ($edit && $edit->image_id): ?>
                <button type="button" class="ti-btn-sm ti-btn-red" onclick="tiSocialRemoveImg()">Remover</button>
                <?php endif; ?>
              </div>
            </div>

            <div class="ti-form-row">
              <label>Alt text (SEO) <small style="color:#888">— descreva a imagem para os buscadores</small></label>
              <input type="text" id="ti_soc_alt" placeholder="Ex: Apartamento 3 quartos no Setor Bueno, Goiânia" value="<?= $edit ? esc_attr($edit->alt_text) : '' ?>">
            </div>

            <div class="ti-form-row">
              <label>Legenda / Caption</label>
              <textarea id="ti_soc_legenda" rows="4" placeholder="Texto exibido abaixo da imagem no site..."><?= $edit ? esc_textarea($edit->legenda) : '' ?></textarea>
            </div>

            <div class="ti-form-row">
              <label>Tags <small style="color:#888">— separadas por vírgula, usadas no sitemap</small></label>
              <input type="text" id="ti_soc_tags" placeholder="Ex: goiania, imóveis, apartamento" value="<?= $edit ? esc_attr($edit->tags) : '' ?>">
            </div>

            <div style="display:flex;gap:10px;margin-top:4px;">
              <button class="ti-btn" onclick="tiSocialSave()">💾 <?= $edit ? 'Salvar alterações' : 'Adicionar post' ?></button>
              <?php if ($edit): ?>
              <a href="<?= esc_url(admin_url('admin.php?page=ti-social')) ?>" class="ti-btn" style="background:#f0f4f8;color:#333;text-decoration:none;">Cancelar</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script>
    // Uploader de imagem WP Media
    var _tiSocMediaFrame = null;
    function tiSocialPickImage() {
        if (_tiSocMediaFrame) { _tiSocMediaFrame.open(); return; }
        _tiSocMediaFrame = wp.media({ title: 'Escolher imagem', button: { text: 'Usar esta imagem' }, multiple: false });
        _tiSocMediaFrame.on('select', function() {
            var att = _tiSocMediaFrame.state().get('selection').first().toJSON();
            document.getElementById('ti_soc_image_id').value = att.id;
            var img = document.getElementById('ti_soc_prev_img');
            img.src = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
            img.style.display = 'block';
        });
        _tiSocMediaFrame.open();
    }
    function tiSocialRemoveImg() {
        document.getElementById('ti_soc_image_id').value = 0;
        var img = document.getElementById('ti_soc_prev_img');
        img.src = ''; img.style.display = 'none';
    }

    // Salvar post
    function tiSocialSave() {
        var data = {
            action:   'ti_social_save',
            nonce:    ti_admin.nonce,
            id:       document.getElementById('ti_soc_id').value,
            rede:     document.getElementById('ti_soc_rede').value,
            link:     document.getElementById('ti_soc_link').value,
            image_id: document.getElementById('ti_soc_image_id').value,
            alt_text: document.getElementById('ti_soc_alt').value,
            legenda:  document.getElementById('ti_soc_legenda').value,
            tags:     document.getElementById('ti_soc_tags').value,
        };
        fetch(ti_admin.ajax, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams(data)
        }).then(r => r.json()).then(function(res) {
            if (res.success) { location.href = '<?= esc_js(admin_url("admin.php?page=ti-social")) ?>'; }
            else alert('Erro ao salvar: ' + (res.data || ''));
        });
    }

    // Preview embed oEmbed
    function tiSocialPreviewEmbed() {
        var url = document.getElementById('ti_soc_link').value.trim();
        if (!url) { alert('Cole a URL do post primeiro.'); return; }
        var prev = document.getElementById('ti_soc_embed_preview');
        prev.innerHTML = '<p style="color:#888;font-size:13px;padding:8px;">Gerando preview…</p>';
        prev.style.display = 'block';
        fetch(ti_admin.ajax, {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body: new URLSearchParams({ action:'ti_oembed_preview', nonce:ti_admin.nonce, url:url })
        }).then(r=>r.json()).then(function(res){
            if (res.success && res.data.html) {
                prev.innerHTML = res.data.html;
                // Recarregar scripts de embed (Instagram, TikTok)
                prev.querySelectorAll('script').forEach(function(s){
                    var ns = document.createElement('script');
                    if (s.src) ns.src = s.src; else ns.textContent = s.textContent;
                    ns.async = true;
                    document.body.appendChild(ns);
                });
            } else {
                prev.innerHTML = '<p style="color:#DC2626;font-size:13px;padding:8px;">Não foi possível gerar embed. Verifique se a URL é pública e se a plataforma é suportada.</p>';
            }
        }).catch(function(){
            prev.innerHTML = '<p style="color:#DC2626;font-size:13px;padding:8px;">Erro de conexão ao gerar preview.</p>';
        });
    }

    // Excluir post
    function tiSocialDel(id) {
        if (!confirm('Excluir este post?')) return;
        fetch(ti_admin.ajax, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({ action: 'ti_social_delete', nonce: ti_admin.nonce, id: id })
        }).then(r => r.json()).then(function(res) {
            if (res.success) location.reload();
        });
    }
    </script>
    <?php
}
