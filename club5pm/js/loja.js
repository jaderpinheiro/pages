/* Club 5PM · loja: sacola, finalizar compra em 3 etapas e página do pedido.
   Produtos: vêm do plugin "Club 5PM · Loja" no WordPress (D.catalogo = /wp-json/club5pm-loja/v1/catalogo).
   Sem D.catalogo, usa os produtos embutidos na página (prévia).
   Pedidos: Google Apps Script (planilha + e-mails + Mercado Pago). Sem a URL da API, roda em modo prévia.
   O navegador nunca define o valor cobrado: o Apps Script recalcula tudo pelo mesmo catálogo do plugin. */
(() => {
  "use strict";
  const dadosEl = document.getElementById("loja-dados");
  if (!dadosEl) return;
  const D = JSON.parse(dadosEl.textContent);
  const $ = (s, el = document) => el.querySelector(s);
  const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  const brl = (v) => Number(v).toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
  const SELO = '<svg viewBox="0 0 100 100" aria-hidden="true"><circle cx="50" cy="50" r="45" fill="none" stroke="currentColor" stroke-width="3"/><path d="M50 50V18M50 50l15 26" fill="none" stroke="currentColor" stroke-width="3"/><circle cx="50" cy="50" r="4" fill="currentColor"/></svg>';
  const SACOLA = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14l-1 12H6z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M9 10V7a3 3 0 0 1 6 0v3" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';
  const CADEADO = '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M8 10V7a4 4 0 0 1 8 0v3" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>';

  const api = async (corpo) => {
    // text/plain evita o "preflight" de CORS (o Apps Script não responde OPTIONS)
    const r = await fetch(D.api, { method: "POST", body: JSON.stringify(corpo), redirect: "follow" });
    if (!r.ok) throw new Error("HTTP " + r.status);
    return r.json();
  };

  if (document.body.classList.contains("pagina-pedido")) return paginaPedido();

  // ------------------------------------------------------------------ catálogo (plugin do WP)
  const lista = $("[data-produtos]");
  const fotoHtml = (p) => p.foto && p.foto.src
    ? `<img src="${esc(p.foto.src)}"${p.foto.srcset ? ` srcset="${esc(p.foto.srcset)}" sizes="(max-width: 600px) 92vw, 380px"` : ""} width="${p.foto.w || 800}" height="${p.foto.h || 800}" alt="${esc(p.foto.alt || p.nome)}" loading="lazy" decoding="async">`
    : SELO;
  const cartao = (p) => {
    const parcela = D.parcelasMax > 1 ? `ou ${D.parcelasMax}x de ${brl(Math.round(p.preco / D.parcelasMax * 100) / 100)} no cartão` : "";
    const acao = !D.aberta ? `<button class="botao botao--linha" type="button" disabled>Em breve</button>`
      : p.esgotado ? `<button class="botao botao--linha" type="button" disabled>Esgotado</button>`
      : `<button class="botao botao--vinho" type="button" data-${p.opcoes ? "ver" : "adicionar"}="${esc(p.id)}">${SACOLA}${p.opcoes ? "Escolher " + esc(p.opcoes.nome.toLowerCase()) : "Colocar na sacola"}</button>`;
    return `<li><article class="produto" id="${esc(p.id)}">
    <button class="produto__arte" type="button" data-ver="${esc(p.id)}" aria-label="Ver detalhes: ${esc(p.nome)}">${fotoHtml(p)}${p.esgotado ? '<span class="produto__selo">Esgotado</span>' : ""}</button>
    <div class="produto__corpo"><h3>${esc(p.nome)}</h3>${p.resumo ? `<p class="produto__resumo">${esc(p.resumo)}</p>` : ""}<p class="produto__preco">${brl(p.preco)}</p><p class="produto__parcela">${parcela}</p>
    <div class="produto__acoes">${acao}</div></div></article></li>`;
  };
  async function carregaCatalogo() {
    if (D.catalogo) {
      try {
        const r = await fetch(D.catalogo, { headers: { Accept: "application/json" } });
        if (!r.ok) throw new Error("HTTP " + r.status);
        const c = await r.json();
        D.produtos = (c.produtos || []).map((p) => ({ ...p, esgotado: p.estoque === 0 }));
        D.entrega = c.entrega || [];
        D.parcelasMax = c.parcelasMax || D.parcelasMax;
        D.aberta = !!c.aberta;
      } catch (e) {
        lista.innerHTML = `<li class="produtos__aviso"><p>Não conseguimos carregar os produtos agora. Atualize a página em instantes.</p></li>`;
        lista.removeAttribute("aria-busy");
        return false;
      }
    } else {
      D.produtos = D.produtos || [];
      D.aberta = true; // prévia local
    }
    document.querySelectorAll("[data-entregas]").forEach((el) => { if (D.entrega.length) el.textContent = D.entrega.map((x) => x.nome).join(" ou ") + "."; });
    document.querySelectorAll("[data-parcelas]").forEach((el) => { el.textContent = D.parcelasMax > 1 ? ` em até ${D.parcelasMax}x` : ""; });
    lista.innerHTML = (!D.aberta ? `<li class="produtos__aviso"><p><strong>A loja abre em breve.</strong> Já dá para conhecer as peças; os pedidos começam em alguns dias.</p></li>` : "")
      + (D.produtos.length ? D.produtos.map(cartao).join("") : `<li class="produtos__aviso"><p>As peças estão chegando. Volte em breve!</p></li>`);
    lista.removeAttribute("aria-busy");
    return true;
  }
  carregaCatalogo().then((ok) => { if (ok) iniciaLoja(); });

  function iniciaLoja() {
  // ------------------------------------------------------------------ sacola
  const CHAVE = "club5pm-sacola-v1";
  const produto = (id) => D.produtos.find((p) => p.id === id);
  const lerSacola = () => { try { return JSON.parse(localStorage.getItem(CHAVE)) || []; } catch { return []; } };
  let sacola = lerSacola().filter((i) => produto(i.id) && !produto(i.id).esgotado);
  const salvar = () => { try { localStorage.setItem(CHAVE, JSON.stringify(sacola)); } catch { /* navegador sem armazenamento */ } atualizaContador(); };
  const qtdTotal = () => sacola.reduce((s, i) => s + i.qtd, 0);
  const subtotal = () => sacola.reduce((s, i) => s + produto(i.id).preco * i.qtd, 0);
  const arteDe = (id) => { const a = $(`#${CSS.escape(id)} .produto__arte`); return a ? a.querySelector("svg, img")?.outerHTML || SELO : SELO; };

  function atualizaContador() {
    document.querySelectorAll("[data-qtd]").forEach((el) => { el.textContent = qtdTotal(); });
    const b = $("[data-abrir-sacola]");
    if (b) b.setAttribute("aria-label", `Abrir a sacola, ${qtdTotal()} ${qtdTotal() === 1 ? "item" : "itens"}`);
  }
  function adiciona(id, opcao, qtd = 1) {
    const ex = sacola.find((i) => i.id === id && i.opcao === opcao);
    if (ex) ex.qtd = Math.min(ex.qtd + qtd, 10); else sacola.push({ id, opcao, qtd });
    salvar();
  }

  const aviso = $("#aviso");
  let tAviso;
  const avisa = (msg) => { if (!aviso) return; aviso.textContent = msg; aviso.classList.add("mostra"); clearTimeout(tAviso); tAviso = setTimeout(() => aviso.classList.remove("mostra"), 3200); };

  // ------------------------------------------------------------------ painéis
  const abre = (dlg) => { document.querySelectorAll("dialog[open]").forEach((d) => d !== dlg && d.close()); if (!dlg.open) dlg.showModal(); document.documentElement.style.overflow = "hidden"; };
  document.querySelectorAll("dialog.painel").forEach((d) => {
    d.addEventListener("close", () => { if (!document.querySelector("dialog[open]")) document.documentElement.style.overflow = ""; });
    d.addEventListener("click", (e) => { if (e.target === d) d.close(); if (e.target.closest("[data-fechar]")) d.close(); });
  });
  const topoPainel = (id, titulo) => `<div class="painel__topo"><h2 class="painel__titulo" id="${id}">${titulo}</h2><button class="painel__fechar" type="button" data-fechar aria-label="Fechar">×</button></div>`;

  // Produto
  const pProduto = $("#painel-produto");
  function abreProduto(id) {
    const p = produto(id);
    if (!p) return;
    pProduto.innerHTML = `${topoPainel("pp-titulo", "Detalhes")}
      <div class="painel__corpo produto-pg">
        <div class="produto__arte produto-pg__arte" style="cursor:default">${arteDe(p.id)}</div>
        <h3>${esc(p.nome)}</h3>
        <p class="produto__preco">${brl(p.preco)}</p>
        ${D.parcelasMax > 1 ? `<p class="produto__parcela">ou ${D.parcelasMax}x de ${brl(Math.round(p.preco / D.parcelasMax * 100) / 100)} no cartão</p>` : ""}
        <p>${esc(p.descricao)}</p>
        ${p.detalhes?.length ? `<ul class="produto-pg__detalhes">${p.detalhes.map((d) => `<li>${esc(d)}</li>`).join("")}</ul>` : ""}
        ${p.opcoes ? `<fieldset class="opcoes"><legend>${esc(p.opcoes.nome)}</legend><div class="opcoes__lista">${p.opcoes.valores.map((v, n) => `<input type="radio" name="opcao" id="op-${n}" value="${esc(v)}"><label for="op-${n}">${esc(v)}</label>`).join("")}</div><p class="campo__erro" data-erro-opcao></p></fieldset>` : ""}
        <p style="font-weight:500;margin-bottom:.5rem">Quantidade</p>
        <div class="quantidade"><button type="button" data-menos aria-label="Diminuir quantidade">−</button><output data-q aria-live="polite">1</output><button type="button" data-mais aria-label="Aumentar quantidade">+</button></div>
      </div>
      <div class="painel__rodape"><button class="botao botao--vinho botao--bloco" type="button" data-colocar ${p.esgotado || !D.aberta ? "disabled" : ""}>${SACOLA}${!D.aberta ? "A loja abre em breve" : p.esgotado ? "Esgotado" : "Colocar na sacola · " + brl(p.preco)}</button></div>`;
    let q = 1;
    const out = $("[data-q]", pProduto), btn = $("[data-colocar]", pProduto);
    const atual = () => { out.textContent = q; btn.innerHTML = `${SACOLA}Colocar na sacola · ${brl(p.preco * q)}`; };
    $("[data-menos]", pProduto).onclick = () => { q = Math.max(1, q - 1); atual(); };
    $("[data-mais]", pProduto).onclick = () => { q = Math.min(10, q + 1); atual(); };
    btn.onclick = () => {
      let op = null;
      if (p.opcoes) {
        op = $("input[name=opcao]:checked", pProduto)?.value;
        if (!op) { $("[data-erro-opcao]", pProduto).textContent = `Escolha o ${p.opcoes.nome.toLowerCase()} para continuar.`; $("input[name=opcao]", pProduto).focus(); return; }
      }
      adiciona(p.id, op, q);
      abreSacola(`${p.nome} foi para a sacola.`);
    };
    abre(pProduto);
  }

  // Sacola
  const pSacola = $("#painel-sacola");
  function abreSacola(msg) {
    desenhaSacola();
    abre(pSacola);
    if (msg) avisa(msg);
  }
  function desenhaSacola() {
    if (!sacola.length) {
      pSacola.innerHTML = `${topoPainel("ps-titulo", "Sua sacola")}<div class="painel__corpo"><div class="sacola-vazia">${SACOLA}<p>Sua sacola está vazia.</p><button class="botao botao--linha" type="button" data-fechar>Ver os produtos</button></div></div>`;
      return;
    }
    pSacola.innerHTML = `${topoPainel("ps-titulo", "Sua sacola")}
      <div class="painel__corpo"><ul class="sacola-itens">${sacola.map((i, n) => {
        const p = produto(i.id);
        return `<li class="sacola-item"><div class="sacola-item__arte">${arteDe(p.id)}</div><div>
          <h4>${esc(p.nome)}</h4><p class="sacola-item__op">${i.opcao ? esc(p.opcoes.nome) + ": " + esc(i.opcao) + " · " : ""}${brl(p.preco)} cada</p>
          <div class="sacola-item__linha"><div class="quantidade"><button type="button" data-n="${n}" data-d="-1" aria-label="Diminuir ${esc(p.nome)}">−</button><span>${i.qtd}</span><button type="button" data-n="${n}" data-d="1" aria-label="Aumentar ${esc(p.nome)}">+</button></div>
          <strong>${brl(p.preco * i.qtd)}</strong></div>
          <button class="sacola-item__remover" type="button" data-remover="${n}">Remover</button></div></li>`;
      }).join("")}</ul></div>
      <div class="painel__rodape">
        <div class="totais"><div class="total"><span>Subtotal</span><span>${brl(subtotal())}</span></div></div>
        <p class="campo__dica" style="margin-bottom:1rem">A entrega é escolhida no próximo passo.</p>
        <button class="botao botao--vinho botao--bloco" type="button" data-finalizar>Finalizar compra</button>
        <button class="botao botao--linha botao--bloco" type="button" data-fechar style="margin-top:.6rem">Continuar comprando</button>
      </div>`;
  }
  pSacola?.addEventListener("click", (e) => {
    const b = e.target.closest("[data-d]");
    if (b) { const i = sacola[+b.dataset.n]; i.qtd = Math.max(1, Math.min(10, i.qtd + +b.dataset.d)); salvar(); desenhaSacola(); return; }
    const r = e.target.closest("[data-remover]");
    if (r) { const [x] = sacola.splice(+r.dataset.remover, 1); salvar(); desenhaSacola(); avisa(`${produto(x.id).nome} saiu da sacola.`); return; }
    if (e.target.closest("[data-finalizar]")) abreCheckout();
  });

  // ------------------------------------------------------------------ finalizar compra
  const pCheck = $("#painel-checkout");
  const RASCUNHO = "club5pm-checkout";
  let etapa = 1;
  let form = (() => { try { return JSON.parse(sessionStorage.getItem(RASCUNHO)) || {}; } catch { return {}; } })();
  if (!form.entrega) form.entrega = D.entrega[0]?.id;
  const guardaRascunho = () => { try { sessionStorage.setItem(RASCUNHO, JSON.stringify(form)); } catch { /* sem armazenamento */ } };
  const frete = () => D.entrega.find((x) => x.id === form.entrega)?.valor || 0;

  const mascaras = {
    cpf: (v) => v.replace(/\D/g, "").slice(0, 11).replace(/(\d{3})(\d)/, "$1.$2").replace(/(\d{3})(\d)/, "$1.$2").replace(/(\d{3})(\d{1,2})$/, "$1-$2"),
    tel: (v) => { const d = v.replace(/\D/g, "").slice(0, 11); return d.length <= 10 ? d.replace(/(\d{2})(\d)/, "($1) $2").replace(/(\d{4})(\d)/, "$1-$2") : d.replace(/(\d{2})(\d)/, "($1) $2").replace(/(\d{5})(\d)/, "$1-$2"); },
    cep: (v) => v.replace(/\D/g, "").slice(0, 8).replace(/(\d{5})(\d)/, "$1-$2"),
  };
  const cpfValido = (c) => {
    c = c.replace(/\D/g, "");
    if (c.length !== 11 || /^(\d)\1+$/.test(c)) return false;
    for (let t = 9; t < 11; t++) {
      let s = 0;
      for (let i = 0; i < t; i++) s += +c[i] * (t + 1 - i);
      if (((s * 10) % 11) % 10 !== +c[t]) return false;
    }
    return true;
  };
  const REGRAS = {
    nome: [(v) => v.trim().split(/\s+/).length >= 2, "Escreva o seu nome e sobrenome."],
    cpf: [cpfValido, "Confira o CPF: os números não batem."],
    email: [(v) => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v.trim()), "Confira o e-mail. Exemplo: maria@gmail.com"],
    whatsapp: [(v) => v.replace(/\D/g, "").length >= 10, "Escreva o WhatsApp com DDD."],
    cep: [(v) => v.replace(/\D/g, "").length === 8, "O CEP tem 8 números."],
    rua: [(v) => v.trim().length > 2, "Escreva a rua ou avenida."],
    numero: [(v) => v.trim().length > 0, "Escreva o número (ou s/n)."],
    bairro: [(v) => v.trim().length > 1, "Escreva o bairro."],
    cidade: [(v) => v.trim().length > 1, "Escreva a cidade."],
    uf: [(v) => /^[A-Za-z]{2}$/.test(v.trim()), "Escolha o estado."],
  };
  const UFS = "AC AL AP AM BA CE DF ES GO MA MT MS MG PA PB PR PE PI RJ RN RS RO RR SC SP SE TO".split(" ");

  const campo = (id, rot, attrs = "", cls = "", dica = "") => `<div class="campo ${cls}"><label for="c-${id}">${rot}</label><input id="c-${id}" name="${id}" value="${esc(form[id] || "")}" ${attrs} aria-describedby="e-${id}">${dica ? `<p class="campo__dica">${dica}</p>` : ""}<p class="campo__erro" id="e-${id}"></p></div>`;

  function htmlEtapa() {
    if (etapa === 1) return `<div class="etapa"><h3>Seus dados</h3><p class="etapa__ajuda">Usamos estes dados para o pagamento e para falar com você sobre o pedido.</p>
      <div class="campos">
        ${campo("nome", "Nome completo", 'autocomplete="name" required')}
        ${campo("cpf", "CPF", 'inputmode="numeric" autocomplete="off" placeholder="000.000.000-00" required data-mascara="cpf"', "campo--3", "O Mercado Pago pede o CPF de quem paga.")}
        ${campo("whatsapp", "WhatsApp com DDD", 'type="tel" inputmode="tel" autocomplete="tel-national" placeholder="(62) 90000-0000" required data-mascara="tel"', "campo--3")}
        ${campo("email", "E-mail", 'type="email" inputmode="email" autocomplete="email" placeholder="seunome@email.com" required', "", "A confirmação do pedido chega neste e-mail.")}
        <div class="mel" aria-hidden="true"><label>Não preencha<input name="site" tabindex="-1" autocomplete="off"></label></div>
      </div></div>`;
    if (etapa === 2) return `<div class="etapa"><h3>Entrega</h3><p class="etapa__ajuda">Escolha como prefere receber e confirme o seu endereço.</p>
      <fieldset class="opcoes" style="margin-bottom:1.6rem"><legend class="sr-only">Forma de entrega</legend><div class="escolhas">${D.entrega.map((x) => `<div class="escolha"><input type="radio" name="entrega" id="en-${x.id}" value="${x.id}" ${form.entrega === x.id ? "checked" : ""}><label for="en-${x.id}"><span><strong>${esc(x.nome)}</strong><small>${esc(x.detalhe)}</small></span><span class="escolha__valor">${x.valor ? brl(x.valor) : "Grátis"}</span></label></div>`).join("")}</div></fieldset>
      <div class="campos">
        ${campo("cep", "CEP", 'inputmode="numeric" autocomplete="postal-code" placeholder="00000-000" required data-mascara="cep"', "campo--2", '<a href="https://buscacepinter.correios.com.br/app/endereco/index.php" target="_blank" rel="noopener">Não sei o meu CEP</a>')}
        <div class="campo campo--4" style="justify-content:flex-end"><p class="campo__dica" data-cep-status aria-live="polite">Digite o CEP e o endereço aparece sozinho.</p></div>
        ${campo("rua", "Rua ou avenida", 'autocomplete="address-line1" required', "campo--4")}
        ${campo("numero", "Número", 'inputmode="numeric" required', "campo--2")}
        ${campo("complemento", "Complemento <small>(opcional)</small>", 'autocomplete="address-line2" placeholder="Apto, bloco, quadra, lote"')}
        ${campo("bairro", "Bairro", 'required', "campo--3")}
        ${campo("cidade", "Cidade", 'autocomplete="address-level2" required', "campo--3")}
        <div class="campo campo--2"><label for="c-uf">Estado</label><select id="c-uf" name="uf" autocomplete="address-level1" aria-describedby="e-uf"><option value="">—</option>${UFS.map((u) => `<option ${form.uf === u ? "selected" : ""}>${u}</option>`).join("")}</select><p class="campo__erro" id="e-uf"></p></div>
        <div class="campo campo--4"><label for="c-obs">Observações <small>(opcional)</small></label><textarea id="c-obs" name="obs" placeholder="Algum recado para nós? Ex.: é presente, melhor horário para entrega">${esc(form.obs || "")}</textarea></div>
      </div></div>`;
    const ent = D.entrega.find((x) => x.id === form.entrega);
    return `<div class="etapa"><h3>Confira e pague</h3><p class="etapa__ajuda">Está tudo certo? No próximo passo você paga com segurança pelo Mercado Pago.</p>
      <div class="resumo-pedido"><h4>Seu pedido</h4><ul>${sacola.map((i) => { const p = produto(i.id); return `<li><span>${i.qtd}× ${esc(p.nome)}${i.opcao ? " (" + esc(i.opcao) + ")" : ""}</span><span>${brl(p.preco * i.qtd)}</span></li>`; }).join("")}</ul>
        <div class="totais"><div><span>Subtotal</span><span>${brl(subtotal())}</span></div><div><span>${esc(ent?.nome || "Entrega")}</span><span>${frete() ? brl(frete()) : "Grátis"}</span></div><div class="total"><span>Total</span><span>${brl(subtotal() + frete())}</span></div></div></div>
      <div class="resumo-pedido"><h4>Seus dados</h4><dl><dt>Nome</dt><dd>${esc(form.nome)}</dd><dt>CPF</dt><dd>${esc(form.cpf)}</dd><dt>E-mail</dt><dd>${esc(form.email)}</dd><dt>WhatsApp</dt><dd>${esc(form.whatsapp)}</dd><dt>Endereço</dt><dd>${esc(form.rua)}, ${esc(form.numero)}${form.complemento ? " · " + esc(form.complemento) : ""}<br>${esc(form.bairro)} · ${esc(form.cidade)} - ${esc(form.uf)} · ${esc(form.cep)}</dd></dl>
        <p style="margin:.8rem 0 0"><button class="sacola-item__remover" type="button" data-ir="1">Alterar meus dados</button> · <button class="sacola-item__remover" type="button" data-ir="2">Alterar entrega</button></p></div>
      <label class="aceite"><input type="checkbox" name="aceite" ${form.aceite ? "checked" : ""}><span>Li e concordo com a <a href="${D.dominio}/privacidade/" target="_blank" rel="noopener" style="color:var(--vinho)">política de privacidade</a>. Meus dados serão usados só para este pedido.</span></label>
      <p class="campo__erro" id="e-aceite"></p>
      <div data-erro-geral></div></div>`;
  }

  function desenhaCheckout() {
    const nomes = ["Seus dados", "Entrega", "Pagamento"];
    pCheck.innerHTML = `${topoPainel("pc-titulo", "Finalizar compra")}
      <form class="painel__corpo" novalidate data-form>
        <ol class="etapas">${nomes.map((n, i) => `<li ${i + 1 === etapa ? 'aria-current="step"' : ""} class="${i + 1 < etapa ? "feita" : ""}"><span class="sr-only">Etapa ${i + 1} de 3: </span>${n}</li>`).join("")}</ol>
        ${htmlEtapa()}
      </form>
      <div class="painel__rodape">
        ${etapa === 3 ? `<div class="totais" style="margin-bottom:.8rem"><div class="total" style="border:0;padding:0;margin:0"><span>Total</span><span>${brl(subtotal() + frete())}</span></div></div>` : ""}
        <div class="rodape-botoes">
          ${etapa > 1 ? `<button class="botao botao--linha" type="button" data-voltar>Voltar</button>` : ""}
          <button class="botao botao--vinho" type="button" data-avancar>${etapa < 3 ? "Continuar" : D.previa ? "Concluir pedido (prévia)" : "Ir para o pagamento"}</button>
        </div>
        ${etapa === 3 ? `<p class="seguro">${CADEADO}Pagamento seguro pelo Mercado Pago · Pix ou cartão${D.parcelasMax > 1 ? " em até " + D.parcelasMax + "x" : ""}</p>` : ""}
      </div>`;
    const f = $("[data-form]", pCheck);
    f.addEventListener("input", (e) => {
      const el = e.target;
      if (el.dataset.mascara) { el.value = mascaras[el.dataset.mascara](el.value); }
      if (el.name && el.name !== "site") { form[el.name] = el.type === "checkbox" ? el.checked : el.value; guardaRascunho(); }
      if (el.getAttribute("aria-invalid") === "true") valida(el);
      if (el.name === "cep" && el.value.replace(/\D/g, "").length === 8) buscaCep(el.value);
    });
    f.addEventListener("change", (e) => { if (e.target.name === "entrega" || e.target.name === "uf" || e.target.name === "aceite") { form[e.target.name] = e.target.type === "checkbox" ? e.target.checked : e.target.value; guardaRascunho(); } });
    f.addEventListener("focusout", (e) => { if (e.target.value && REGRAS[e.target.name]) valida(e.target); });
    f.addEventListener("submit", (e) => { e.preventDefault(); avancar(); });
    f.addEventListener("click", (e) => { const b = e.target.closest("[data-ir]"); if (b) { etapa = +b.dataset.ir; desenhaCheckout(); } });
    $("[data-avancar]", pCheck).onclick = avancar;
    const v = $("[data-voltar]", pCheck); if (v) v.onclick = () => { etapa--; desenhaCheckout(); $(".painel__corpo", pCheck).scrollTop = 0; };
    $(".painel__corpo", pCheck).scrollTop = 0;
  }

  function valida(el) {
    const r = REGRAS[el.name];
    if (!r) return true;
    const ok = r[0](el.value || "");
    el.setAttribute("aria-invalid", ok ? "false" : "true");
    const e = $("#e-" + el.name, pCheck);
    if (e) e.textContent = ok ? "" : r[1];
    return ok;
  }
  function validaEtapa() {
    const els = [...pCheck.querySelectorAll("[data-form] input[name], [data-form] select[name]")].filter((el) => REGRAS[el.name]);
    const ruins = els.filter((el) => !valida(el));
    if (ruins.length) { ruins[0].focus(); ruins[0].scrollIntoView({ block: "center", behavior: "smooth" }); return false; }
    if (etapa === 3 && !form.aceite) { $("#e-aceite", pCheck).textContent = "Para continuar, marque que concorda com a política de privacidade."; $("input[name=aceite]", pCheck).focus(); return false; }
    return true;
  }

  let ultimoCep = "";
  async function buscaCep(v) {
    const cep = v.replace(/\D/g, "");
    if (cep === ultimoCep) return;
    ultimoCep = cep;
    const st = $("[data-cep-status]", pCheck);
    if (st) st.innerHTML = '<span class="carregando" aria-hidden="true"></span> Buscando o endereço…';
    try {
      const r = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
      const j = await r.json();
      if (j.erro) throw new Error("cep");
      const set = (n, val) => { const el = $(`[name=${n}]`, pCheck); if (el && val) { el.value = val; form[n] = val; valida(el); } };
      set("rua", j.logradouro); set("bairro", j.bairro); set("cidade", j.localidade); set("uf", j.uf);
      guardaRascunho();
      if (st) st.textContent = "Endereço encontrado. Agora é só colocar o número.";
      $(`[name=${j.logradouro ? "numero" : "rua"}]`, pCheck)?.focus();
    } catch {
      if (st) st.textContent = "Não achamos este CEP. Pode escrever o endereço à mão.";
    }
  }

  async function avancar() {
    if (!validaEtapa()) return;
    if (etapa < 3) { etapa++; desenhaCheckout(); $("h3", pCheck)?.focus?.(); return; }
    const btn = $("[data-avancar]", pCheck);
    btn.disabled = true;
    btn.innerHTML = '<span class="carregando" aria-hidden="true"></span> Preparando o pagamento…';
    const pedido = {
      acao: "criar",
      itens: sacola.map(({ id, opcao, qtd }) => ({ id, opcao, qtd })),
      entrega: form.entrega,
      cliente: { nome: form.nome.trim(), cpf: form.cpf, email: form.email.trim(), whatsapp: form.whatsapp },
      endereco: { cep: form.cep, rua: form.rua, numero: form.numero, complemento: form.complemento || "", bairro: form.bairro, cidade: form.cidade, uf: form.uf },
      obs: form.obs || "", site: $("[name=site]", pCheck)?.value || "", aceite: !!form.aceite,
    };
    if (D.previa) {
      setTimeout(() => mostraFim({ ok: true, numero: "PRÉVIA", previa: true }), 700);
      return;
    }
    try {
      const r = await api(pedido);
      if (!r.ok) throw new Error(r.erro || "Não foi possível registrar o pedido.");
      sacola = []; salvar();
      try { sessionStorage.removeItem(RASCUNHO); localStorage.setItem("club5pm-ultimo-pedido", r.numero); } catch { /* sem armazenamento */ }
      if (r.pagamento?.url) { location.href = r.pagamento.url; return; }
      mostraFim(r);
    } catch (e) {
      btn.disabled = false;
      btn.textContent = "Tentar de novo";
      $("[data-erro-geral]", pCheck).innerHTML = `<p class="alerta" role="alert">${esc(e.message || "Algo deu errado.")} Se continuar, fale com a gente pelo <a href="https://ig.me/m/club5pm.oficial" target="_blank" rel="noopener">direct</a>.</p>`;
    }
  }

  function mostraFim(r) {
    pCheck.innerHTML = `${topoPainel("pc-titulo", "Pedido recebido")}
      <div class="painel__corpo"><div class="confirmacao">
        <div class="confirmacao__selo">${SELO}</div>
        <h3 class="subtitulo">${r.previa ? "Pedido de teste concluído" : "Obrigada pelo seu pedido!"}</h3>
        <p>${r.previa ? "Esta é uma prévia da loja: nada foi enviado nem cobrado. Quando a loja for ao ar, aqui a cliente segue para o pagamento no Mercado Pago." : `Seu pedido é o <strong>nº ${esc(r.numero)}</strong>. Enviamos o resumo para o seu e-mail.`}</p>
        ${r.pix ? `<div class="pix-caixa"><p><strong>Pague com Pix: ${brl(r.pix.valor)}</strong></p><p>Copie o código abaixo e cole no aplicativo do seu banco, em <em>Pix copia e cola</em>.</p><textarea readonly aria-label="Código Pix copia e cola">${esc(r.pix.copiaecola)}</textarea><button class="botao botao--vinho botao--bloco" type="button" data-copiar style="margin-top:.8rem">Copiar código Pix</button><p class="campo__dica" style="margin-top:.8rem">Chave Pix: ${esc(r.pix.chave)}. Assim que o pagamento cair, confirmamos por e-mail.</p></div>` : ""}
        <button class="botao botao--linha" type="button" data-fechar>Voltar para a loja</button>
      </div></div>`;
    const c = $("[data-copiar]", pCheck);
    if (c) c.onclick = async () => { const t = $("textarea", pCheck); try { await navigator.clipboard.writeText(t.value); } catch { t.select(); document.execCommand("copy"); } c.textContent = "Código copiado!"; };
  }

  function abreCheckout() {
    if (!sacola.length) return abreSacola();
    etapa = 1;
    desenhaCheckout();
    abre(pCheck);
  }

  // ------------------------------------------------------------------ gatilhos
  document.addEventListener("click", (e) => {
    const ver = e.target.closest("[data-ver]");
    if (ver) return abreProduto(ver.dataset.ver);
    const add = e.target.closest("[data-adicionar]");
    if (add) { adiciona(add.dataset.adicionar, null, 1); return abreSacola(`${produto(add.dataset.adicionar).nome} foi para a sacola.`); }
    if (e.target.closest("[data-abrir-sacola]")) abreSacola();
  });
  atualizaContador();
  if (location.hash && produto(location.hash.slice(1))) document.getElementById(location.hash.slice(1))?.scrollIntoView();
  } // fim de iniciaLoja

  // ------------------------------------------------------------------ página do pedido
  async function paginaPedido() {
    const alvo = $("#pedido-status");
    const q = new URLSearchParams(location.search);
    const numero = q.get("n") || q.get("external_reference") || (() => { try { return localStorage.getItem("club5pm-ultimo-pedido"); } catch { return null; } })();
    const desenha = (titulo, texto, extra = "") => { alvo.innerHTML = `<div class="confirmacao__selo">${SELO}</div><h1 class="subtitulo">${titulo}</h1><p class="lead" style="margin-inline:auto">${texto}</p>${extra}`; };
    const voltar = `<p style="margin-top:2rem"><a class="botao botao--linha" href="${D.dominio}/loja/">Voltar para a loja</a></p>`;
    if (!numero) return desenha("Não encontramos o pedido", "Se você acabou de comprar, confira o e-mail de confirmação ou fale com a gente pelo direct.", voltar);
    if (D.previa) return desenha("Prévia da loja", "A loja ainda está em prévia: nenhum pedido é registrado.", voltar);
    const consulta = async () => {
      try {
        const r = await api({ acao: "status", numero, pagamento: q.get("payment_id") || q.get("collection_id") || "" });
        if (!r.ok) throw new Error(r.erro);
        if (r.status === "Pago" || r.status === "Em separação" || r.status === "Enviado" || r.status === "Entregue") {
          desenha("Pagamento confirmado!", `Obrigada! O pedido <strong>nº ${esc(numero)}</strong> está confirmado e você vai receber as novidades por e-mail.`, voltar);
        } else if (r.status === "Recusado") {
          desenha("O pagamento não foi aprovado", "Nada foi cobrado. Você pode tentar de novo com outro cartão ou com Pix.", r.pagamento?.url ? `<p style="margin-top:1.6rem"><a class="botao botao--vinho" href="${esc(r.pagamento.url)}">Tentar pagar de novo</a></p>${voltar}` : voltar);
        } else if (r.status === "Cancelado") {
          desenha("Pedido cancelado", `O pedido nº ${esc(numero)} foi cancelado. Se foi um engano, fale com a gente pelo direct.`, voltar);
        } else {
          desenha("Estamos aguardando o pagamento", `Pedido <strong>nº ${esc(numero)}</strong>. Pagamentos com Pix costumam ser confirmados em poucos minutos. Assim que cair, você recebe um e-mail.`,
            `${r.pagamento?.url ? `<p style="margin-top:1.6rem"><a class="botao botao--vinho" href="${esc(r.pagamento.url)}">Ir para o pagamento</a></p>` : ""}<p style="margin-top:1rem"><button class="botao botao--linha" type="button" data-atualizar>Já paguei, atualizar</button></p>`);
          $("[data-atualizar]", alvo)?.addEventListener("click", () => { desenha("Conferindo…", '<span class="carregando" aria-hidden="true"></span>'); consulta(); });
        }
      } catch {
        desenha("Não conseguimos conferir agora", `Pedido nº ${esc(numero)}. Tente atualizar a página em instantes; a confirmação também chega por e-mail.`, voltar);
      }
    };
    consulta();
  }
})();
