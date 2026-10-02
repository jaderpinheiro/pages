/* Club 5PM · cartão de visita digital
   JavaScript puro e pequeno. Sem ele o cartão continua completo: tudo é link comum. */
(() => {
  /* Compartilhar: folha nativa do celular; no computador, copia o link */
  const botao = document.querySelector("[data-compartilhar]");
  if (botao) {
    const rotulo = botao.querySelector("span");
    const original = rotulo.textContent;
    botao.hidden = false;
    botao.addEventListener("click", async () => {
      const dados = { title: document.title, text: botao.dataset.compartilhar, url: location.href };
      try {
        if (navigator.share) return await navigator.share(dados);
        await navigator.clipboard.writeText(location.href);
        rotulo.textContent = "Link copiado";
        setTimeout(() => { rotulo.textContent = original; }, 2400);
      } catch (e) { /* a pessoa fechou a folha de compartilhamento */ }
    });
  }

  /* Poeira dourada na luz das cinco: poucas partículas, pausa quando a aba não está visível */
  const canvas = document.querySelector(".poeira");
  if (!canvas || matchMedia("(prefers-reduced-motion: reduce)").matches) return;
  const ctx = canvas.getContext("2d");
  if (!ctx) return;
  let w, h, ps = [], raf;
  const novo = (inicio) => ({
    x: Math.random() * w, y: inicio ? Math.random() * h : h + 8,
    r: Math.random() * 1.4 + 0.3, vy: -(Math.random() * 0.28 + 0.06), vx: (Math.random() - 0.5) * 0.14,
    a: Math.random() * 0.5 + 0.15, f: Math.random() * 6.28,
  });
  const criar = () => {
    const dpr = Math.min(devicePixelRatio || 1, 2);
    w = innerWidth; h = innerHeight;
    canvas.width = w * dpr; canvas.height = h * dpr;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    ps = Array.from({ length: Math.round(Math.min(46, (w * h) / 26000)) }, () => novo(true));
  };
  const quadro = () => {
    ctx.clearRect(0, 0, w, h);
    for (const p of ps) {
      p.f += 0.018; p.x += p.vx + Math.sin(p.f) * 0.12; p.y += p.vy;
      if (p.y < -8) Object.assign(p, novo(false));
      ctx.globalAlpha = p.a * (0.6 + 0.4 * Math.sin(p.f * 1.6));
      ctx.fillStyle = "#F3DDAE";
      ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, 6.28); ctx.fill();
    }
    raf = requestAnimationFrame(quadro);
  };
  criar(); quadro();
  let t; addEventListener("resize", () => { clearTimeout(t); t = setTimeout(criar, 200); });
  document.addEventListener("visibilitychange", () => { cancelAnimationFrame(raf); if (!document.hidden) quadro(); });
})();
