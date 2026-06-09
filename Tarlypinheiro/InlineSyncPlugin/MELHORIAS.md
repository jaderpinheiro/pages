# InlineSyncPlugin — Histórico de Melhorias

## v1.1.0 — 2026-05-30

### Contexto do problema

Após atualização do WordPress (6.6+/6.7+), deploys via REST API passaram a retornar:

```
HTTP 422: {"code":"invalid_payload","message":"Os campos slug, title e html são obrigatórios..."}
```

**Causa raiz:** O WP ficou mais estrito quanto ao header `Content-Type`. Se a aplicação cliente envia o body JSON sem `Content-Type: application/json`, o WP não parseia o body automaticamente e `get_param()` retorna `null`. O plugin detectava os campos como vazios e retornava 422. Em versões anteriores do WP, o parsing era mais tolerante.

---

### Mudanças realizadas

#### `includes/rest-deploy.php`

**1. Fallback de parsing do body JSON**

Antes: o plugin dependia exclusivamente de `get_param()`, que falha silenciosamente quando o `Content-Type` do cliente está errado.

Depois: se `get_param()` retornar vazio para qualquer campo, o plugin tenta `get_json_params()` (lê o body raw independente do Content-Type) e aplica a sanitização manualmente.

```php
// Fallback — resolve Content-Type errado ou ausente
$body = $request->get_json_params();
if ( is_array( $body ) ) {
    if ( empty( $slug ) && ! empty( $body['slug'] ) ) {
        $slug = sanitize_title( $body['slug'] );
    }
    // ... title e html idem
}
```

**2. Remoção de `required: true` nos args**

Com `required: true`, o WP valida e pode rejeitar com HTTP 400 antes do callback executar, impedindo o fallback de atuar. Removido para que o callback sempre execute e o fallback funcione.

**3. Logging diagnóstico no `error_log`**

Quando os campos ainda estiverem vazios após o fallback, o plugin registra no log do PHP:

```
[InlineSyncPlugin] Payload inválido — slug: null | title: null | html_length: 0 | content_type: ausente
```

**4. Resposta 422 enriquecida**

A resposta de erro agora inclui `received` com o status de cada campo e o Content-Type recebido — facilita o debug sem precisar acessar o servidor.

---

#### `includes/page-handler.php`

**5. Skip de deploy quando conteúdo não mudou**

Antes: qualquer chamada ao endpoint sempre escrevia no banco, mesmo com HTML idêntico.

Depois: se o hash SHA-256 do HTML **e** o título forem iguais à versão salva, a função retorna imediatamente sem gravar.

```php
if ( $old_hash === $html_hash && $existing->post_title === $title ) {
    return [ 'page_id' => ..., 'slug' => ..., 'status' => 'unchanged' ];
}
```

Benefícios:
- Elimina writes desnecessários no banco
- Evita flush de cache em conteúdo que não mudou
- Resposta mais rápida para webhooks repetidos

O campo `status` na resposta agora pode ser: `created`, `updated` ou `unchanged`.

---

### Fluxo completo do sistema

```
GitHub (repo público)
    ↓  API GitHub (arquivo index.html)
Aplicação Web (bridge)
    ↓  POST /wp-json/inline-sync/v1/deploy
    ↓  Headers: Authorization: Bearer <token>
    ↓  Headers: Content-Type: application/json   ← OBRIGATÓRIO na aplicação web
    ↓  Body JSON: { slug, title, html }
Plugin InlineSyncPlugin (WP)
    ↓  Valida token
    ↓  Lê params (get_param + fallback get_json_params)
    ↓  Verifica hash — pula se igual
    ↓  kses_remove_filters → wp_insert/update_post → kses_init_filters
    ↓  Salva _isa_html_hash, _isa_last_deploy, _isa_full_page
WP exibe página via template_redirect (HTML cru, sem tema)
```

---

### O que corrigir na aplicação web (causa raiz)

A aplicação que faz a ponte GitHub → WordPress deve garantir:

1. **Header obrigatório:**
   ```
   Content-Type: application/json
   ```

2. **Body JSON completo:**
   ```json
   {
     "slug":  "nome-da-pasta-no-github",
     "title": "Título extraído do <title> do HTML",
     "html":  "<html completo do index.html>"
   }
   ```

3. **Extração do slug:** usar o nome da pasta/repositório, não o caminho completo do arquivo (`Happydaycestaria`, não `Happydaycestaria/index.html`).

4. **Extração do title:** usar regex/DOM para pegar o conteúdo do `<title>` do HTML. Se ausente, usar o slug formatado como fallback.

---

### Compatibilidade

| Item | Requisito |
|------|-----------|
| PHP | 8.0+ (union types) |
| WordPress | 6.0+ |
| Endpoint | `POST /wp-json/inline-sync/v1/deploy` |

As mudanças são retrocompatíveis: o comportamento em WP anteriores é idêntico ao v1.0.0 quando o `Content-Type` está correto. O fallback apenas garante que funcione também quando está errado.
