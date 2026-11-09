/* Club 5PM · páginas internas: menu no celular, filtro de marcas e visor de fotos. Sem dependências. */
(() => {
  "use strict";
  document.documentElement.classList.add("js");

  // Menu (celular)
  const botao = document.querySelector(".pg-menu-botao");
  const menu = document.getElementById("pg-menu");
  if (botao && menu) {
    const fecha = () => { menu.classList.remove("aberto"); botao.setAttribute("aria-expanded", "false"); botao.querySelector(".pg-menu-botao__texto").textContent = "Menu"; };
    botao.addEventListener("click", () => {
      const abrir = !menu.classList.contains("aberto");
      menu.classList.toggle("aberto", abrir);
      botao.setAttribute("aria-expanded", String(abrir));
      botao.querySelector(".pg-menu-botao__texto").textContent = abrir ? "Fechar" : "Menu";
    });
    menu.addEventListener("click", (e) => { if (e.target.closest("a")) fecha(); });
    document.addEventListener("keydown", (e) => { if (e.key === "Escape") fecha(); });
  }

  // Filtro por segmento (diretório de parceiras)
  const filtros = document.querySelector("[data-filtros]");
  const lista = document.querySelector("[data-lista-marcas]");
  if (filtros && lista) {
    const status = document.querySelector("[data-filtro-status]");
    filtros.addEventListener("click", (e) => {
      const b = e.target.closest("[data-filtro]");
      if (!b) return;
      const cat = b.dataset.filtro;
      filtros.querySelectorAll("[data-filtro]").forEach((x) => x.setAttribute("aria-pressed", String(x === b)));
      let n = 0;
      lista.querySelectorAll("li[data-categoria]").forEach((li) => {
        const mostra = !cat || li.dataset.categoria === cat;
        li.hidden = !mostra;
        if (mostra) n++;
      });
      if (status) status.textContent = `${n} ${n === 1 ? "marca" : "marcas"}${cat ? " em " + cat : ""}`;
    });
    // Link para uma marca escondida pelo filtro: volta para "Todas" antes de rolar
    const revela = () => {
      const alvo = location.hash.length > 1 && document.getElementById(decodeURIComponent(location.hash.slice(1)));
      const li = alvo && alvo.closest("li[data-categoria]");
      if (li && li.hidden) { filtros.querySelector('[data-filtro=""]').click(); alvo.scrollIntoView(); }
    };
    window.addEventListener("hashchange", revela);
    revela();
  }

  // Copiar o link de uma marca (para mandar à própria marca)
  const aviso = (txt) => {
    let t = document.querySelector(".aviso-toast");
    if (!t) { t = document.createElement("p"); t.className = "aviso-toast"; t.setAttribute("role", "status"); document.body.appendChild(t); }
    t.textContent = txt; t.classList.add("mostra");
    clearTimeout(t._t); t._t = setTimeout(() => t.classList.remove("mostra"), 2600);
  };
  document.addEventListener("click", async (e) => {
    const b = e.target.closest("[data-copiar]");
    if (!b) return;
    const link = b.dataset.copiar;
    try { await navigator.clipboard.writeText(link); aviso("Link copiado. É só colar no WhatsApp."); }
    catch { window.prompt("Copie o link da marca:", link); }
  });

  // Visor de fotos
  const galeria = document.querySelector("[data-galeria]");
  if (galeria && "HTMLDialogElement" in window) {
    const botoes = [...galeria.querySelectorAll("button[data-grande]")];
    const v = document.createElement("dialog");
    v.className = "visor";
    v.setAttribute("aria-label", "Foto ampliada");
    v.innerHTML = '<div class="visor__palco"><img alt=""></div><p class="visor__legenda"></p>' +
      '<button class="visor__btn visor__fechar" type="button" aria-label="Fechar">×</button>' +
      '<button class="visor__btn visor__ant" type="button" aria-label="Foto anterior"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></button>' +
      '<button class="visor__btn visor__prox" type="button" aria-label="Próxima foto"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></button>';
    document.body.appendChild(v);
    const imgV = v.querySelector("img"), leg = v.querySelector(".visor__legenda");
    let i = 0;
    const mostra = (n) => {
      i = (n + botoes.length) % botoes.length;
      const b = botoes[i], alt = b.querySelector("img").alt;
      imgV.src = b.dataset.grande; imgV.alt = alt; leg.textContent = `${alt} · ${i + 1} de ${botoes.length}`;
    };
    botoes.forEach((b, n) => b.addEventListener("click", () => { mostra(n); v.showModal(); document.documentElement.style.overflow = "hidden"; }));
    v.addEventListener("close", () => { document.documentElement.style.overflow = ""; });
    v.querySelector(".visor__fechar").addEventListener("click", () => v.close());
    v.querySelector(".visor__ant").addEventListener("click", () => mostra(i - 1));
    v.querySelector(".visor__prox").addEventListener("click", () => mostra(i + 1));
    v.addEventListener("click", (e) => { if (e.target === v || e.target.classList.contains("visor__palco")) v.close(); });
    v.addEventListener("keydown", (e) => { if (e.key === "ArrowLeft") mostra(i - 1); if (e.key === "ArrowRight") mostra(i + 1); });
    let x0 = null;
    v.addEventListener("touchstart", (e) => { x0 = e.touches[0].clientX; }, { passive: true });
    v.addEventListener("touchend", (e) => { if (x0 === null) return; const dx = e.changedTouches[0].clientX - x0; if (Math.abs(dx) > 50) mostra(i + (dx < 0 ? 1 : -1)); x0 = null; });
  }
})();
