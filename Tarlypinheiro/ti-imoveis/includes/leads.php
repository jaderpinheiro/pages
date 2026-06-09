<?php
defined('ABSPATH') || exit;

// ── Funções de acesso à tabela de leads ───────────────────────────────────────

function ti_save_lead(array $data): int|false {
    global $wpdb;
    $ok = $wpdb->insert(
        $wpdb->prefix . 'ti_leads',
        [
            'nome'        => sanitize_text_field($data['nome'] ?? ''),
            'telefone'    => sanitize_text_field($data['telefone'] ?? ''),
            'email'       => sanitize_email($data['email'] ?? ''),
            'imovel_id'   => (int)($data['imovel_id'] ?? 0),
            'imovel_nome' => sanitize_text_field($data['imovel_nome'] ?? ''),
            'status'      => 'lead',
        ],
        ['%s','%s','%s','%d','%s','%s']
    );
    return $ok ? $wpdb->insert_id : false;
}

function ti_get_leads(string $status = ''): array {
    global $wpdb;
    $table = $wpdb->prefix . 'ti_leads';
    if ($status) {
        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table WHERE status = %s ORDER BY created_at DESC", $status)
        ) ?: [];
    }
    return $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC") ?: [];
}

function ti_get_leads_grouped(): array {
    $statuses = ['lead','contato','negociacao','fechamento','noshow'];
    $result   = [];
    foreach ($statuses as $s) {
        $result[$s] = ti_get_leads($s);
    }
    return $result;
}

function ti_update_lead_status(int $id, string $status): bool {
    global $wpdb;
    $valid = ['lead','contato','negociacao','fechamento','noshow'];
    if ( ! in_array($status, $valid) ) return false;
    return (bool) $wpdb->update(
        $wpdb->prefix . 'ti_leads',
        ['status' => $status, 'updated_at' => current_time('mysql')],
        ['id' => $id],
        ['%s','%s'],
        ['%d']
    );
}

function ti_update_lead_info(int $id, array $data): bool {
    global $wpdb;
    $set = [];
    $fmt = [];
    if ( isset($data['descricao']) ) { $set['descricao'] = sanitize_textarea_field($data['descricao']); $fmt[] = '%s'; }
    if ( isset($data['enviou_docs']) ) { $set['enviou_docs'] = (int)$data['enviou_docs']; $fmt[] = '%d'; }
    if ( isset($data['indicou']) ) { $set['indicou'] = sanitize_text_field($data['indicou']); $fmt[] = '%s'; }
    if ( empty($set) ) return false;
    $set['updated_at'] = current_time('mysql'); $fmt[] = '%s';
    return (bool) $wpdb->update($wpdb->prefix . 'ti_leads', $set, ['id' => $id], $fmt, ['%d']);
}

function ti_get_lead(int $id): ?object {
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ti_leads WHERE id = %d", $id));
}

function ti_leads_count_by_status(): array {
    global $wpdb;
    $rows = $wpdb->get_results("SELECT status, COUNT(*) AS total FROM {$wpdb->prefix}ti_leads GROUP BY status");
    $map  = [];
    foreach ($rows as $r) $map[$r->status] = (int)$r->total;
    return $map;
}

function ti_search_leads(string $busca = '', string $status = '', int $limit = 500, int $offset = 0): array {
    global $wpdb;
    $table = $wpdb->prefix . 'ti_leads';
    $where = 'WHERE 1=1';
    $args  = [];
    if ($status) { $where .= ' AND status = %s'; $args[] = $status; }
    if ($busca)  {
        $like   = '%' . $wpdb->esc_like($busca) . '%';
        $where .= ' AND (nome LIKE %s OR telefone LIKE %s OR email LIKE %s OR imovel_nome LIKE %s)';
        $args   = array_merge($args, [$like, $like, $like, $like]);
    }
    $args[] = $limit;
    $args[] = $offset;
    $sql = "SELECT * FROM $table $where ORDER BY created_at DESC LIMIT %d OFFSET %d";
    return $wpdb->get_results($args ? $wpdb->prepare($sql, ...$args) : $sql) ?: [];
}

// Export CSV (UTF-8 BOM — compatível com Excel)
function ti_export_leads_csv(string $status = ''): void {
    $leads = ti_search_leads('', $status, 9999);
    $status_labels = ['lead'=>'Lead','contato'=>'Em Contato','negociacao'=>'Negociação','fechamento'=>'Fechamento','noshow'=>'No-show'];
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="leads-tarlypinheiro-' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM
    fputcsv($out, ['ID','Nome','Telefone','E-mail','Imóvel','Status','Docs Enviados','Indicou','Observações','Data Cadastro'], ';');
    foreach ($leads as $l) {
        fputcsv($out, [
            $l->id, $l->nome, $l->telefone, $l->email, $l->imovel_nome,
            $status_labels[$l->status] ?? $l->status,
            $l->enviou_docs ? 'Sim' : 'Não',
            $l->indicou, $l->descricao,
            date('d/m/Y H:i', strtotime($l->created_at)),
        ], ';');
    }
    fclose($out);
    exit;
}

// Import CSV — retorna ['imported'=>N, 'errors'=>[...]]
function ti_import_leads_csv(string $filepath): array {
    $results = ['imported' => 0, 'skipped' => 0, 'errors' => []];
    $handle  = @fopen($filepath, 'r');
    if ( ! $handle ) { $results['errors'][] = 'Arquivo não encontrado.'; return $results; }

    // Detectar e pular BOM
    $bom = fread($handle, 3);
    if ( $bom !== "\xEF\xBB\xBF" ) rewind($handle);

    $header = fgetcsv($handle, 0, ';') ?: fgetcsv($handle, 0, ','); // Pular cabeçalho
    if ( ! $header ) { $results['errors'][] = 'Arquivo vazio.'; fclose($handle); return $results; }

    $sep = (str_contains(implode('', (array)$header), ';')) ? ';' : ',';
    rewind($handle); if ( $bom !== "\xEF\xBB\xBF" ) {} else fread($handle, 3);
    fgetcsv($handle, 0, $sep); // pular header

    while ( ($row = fgetcsv($handle, 0, $sep)) !== false ) {
        if ( count($row) < 2 ) { $results['skipped']++; continue; }
        // Aceita col 0=ID (ignorar), 1=Nome, 2=Tel, 3=Email, 4=Imóvel
        $nome = sanitize_text_field($row[1] ?? $row[0] ?? '');
        $tel  = sanitize_text_field($row[2] ?? $row[1] ?? '');
        if ( ! $nome || ! $tel ) { $results['skipped']++; continue; }
        $id = ti_save_lead([
            'nome'        => $nome,
            'telefone'    => $tel,
            'email'       => sanitize_email($row[3] ?? ''),
            'imovel_nome' => sanitize_text_field($row[4] ?? ''),
        ]);
        if ( $id ) $results['imported']++; else $results['errors'][] = "Falha ao inserir: $nome";
    }
    fclose($handle);
    return $results;
}
