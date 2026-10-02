/* Club 5PM · site
   JavaScript puro + GSAP/ScrollTrigger/Lenis locais (assets/vendor).
   Todo o conteúdo existe no HTML: se algo aqui falhar, o site continua legível. */
(() => {
  const root = document.documentElement;
  const semMovimento = matchMedia("(prefers-reduced-motion: reduce)").matches;
  const temGsap = typeof window.gsap !== "undefined" && typeof window.ScrollTrigger !== "undefined";

  /* ---------- Utilidades que funcionam sem GSAP ---------- */
  const topo = document.getElementById("topo");
  const menu = document.getElementById("menu");
  const menuBotao = document.querySelector(".menu-botao");
  const ctaFixo = document.querySelector(".cta-fixo");
  let lenis = null;

  const fecharMenu = () => {
    menu.classList.remove("is-aberto");
    menuBotao.setAttribute("aria-expanded", "false");
    menuBotao.querySelector(".menu-botao__texto").textContent = "Menu";
    document.body.style.overflow = "";
    lenis && lenis.start();
  };
  menuBotao.addEventListener("click", () => {
    const abrir = menuBotao.getAttribute("aria-expanded") !== "true";
    if (!abrir) return fecharMenu();
    menu.classList.add("is-aberto");
    menuBotao.setAttribute("aria-expanded", "true");
    menuBotao.querySelector(".menu-botao__texto").textContent = "Fechar";
    document.body.style.overflow = "hidden";
    lenis && lenis.stop();
    menu.querySelector("a").focus();
  });
  document.addEventListener("keydown", (e) => { if (e.key === "Escape" && menu.classList.contains("is-aberto")) { fecharMenu(); menuBotao.focus(); } });

  // Âncoras internas: rolagem suave (Lenis quando disponível) e fechamento do menu
  document.querySelectorAll('a[href^="#"]').forEach((a) => {
    a.addEventListener("click", (e) => {
      const alvo = document.querySelector(a.getAttribute("href"));
      if (!alvo) return;
      fecharMenu();
      if (lenis) { e.preventDefault(); lenis.scrollTo(alvo, { offset: a.getAttribute("href") === "#inicio" ? 0 : -60, duration: 1.4 }); }
    });
  });

  // Topo sólido após rolar + CTA fixo no mobile (some no hero e no rodapé)
  const hero = document.querySelector(".hero");
  const rodape = document.getElementById("rodape");
  const atualizarTopo = () => {
    const y = window.scrollY;
    topo.classList.toggle("is-solido", y > 40);
    const fimHero = hero.offsetTop + hero.offsetHeight * 0.7;
    const noRodape = y + innerHeight > rodape.offsetTop + 120;
    ctaFixo.classList.toggle("is-visivel", y > fimHero && !noRodape);
  };
  addEventListener("scroll", atualizarTopo, { passive: true });
  atualizarTopo();

  /* ---------- Relógio ao vivo: quanto falta para as cinco da tarde (horário de Goiânia/Brasília) ---------- */
  const relogio = document.getElementById("relogio-vivo");
  const horaLocal = () => {
    const p = new Intl.DateTimeFormat("pt-BR", { timeZone: "America/Sao_Paulo", hour: "2-digit", minute: "2-digit", hourCycle: "h23" }).formatToParts(new Date());
    return { h: +p.find((x) => x.type === "hour").value, m: +p.find((x) => x.type === "minute").value };
  };
  const atualizarRelogio = () => {
    const { h, m } = horaLocal();
    const agora = h * 60 + m, alvo = 17 * 60;
    let texto;
    if (agora >= alvo && agora < alvo + 60) texto = "São cinco da tarde em Goiânia. A hora do Club.";
    else {
      const falta = (alvo - agora + 1440) % 1440;
      const hh = Math.floor(falta / 60), mm = falta % 60;
      const partes = [hh ? `${hh}h` : "", mm ? `${mm}min` : ""].filter(Boolean).join(" ");
      texto = `Faltam ${partes} para as cinco da tarde em Goiânia.`;
    }
    relogio.textContent = texto;
    relogio.hidden = false;
  };
  atualizarRelogio();
  setInterval(atualizarRelogio, 30000);

  /* ---------- Sem GSAP ou com movimento reduzido: mostra tudo e encerra ---------- */
  if (!temGsap || semMovimento) {
    root.classList.remove("js");
    document.querySelectorAll(".linha-tempo").forEach((l) => l.style.setProperty("--progresso", 1));
    if (!semMovimento) iniciarPoeira();
    return;
  }

  const { gsap, ScrollTrigger } = window;
  gsap.registerPlugin(ScrollTrigger);

  /* ---------- Lenis → requestAnimationFrame (ticker do GSAP) → ScrollTrigger ---------- */
  if (typeof window.Lenis !== "undefined") {
    lenis = new window.Lenis({ lerp: 0.11, wheelMultiplier: 0.95, smoothWheel: true });
    lenis.on("scroll", ScrollTrigger.update);
    gsap.ticker.add((t) => lenis.raf(t * 1000));
    gsap.ticker.lagSmoothing(0);
  }

  /* ---------- Hero: entrada e parallax ---------- */
  const heroImg = document.querySelector(".hero__fundo img");
  gsap.timeline({ defaults: { ease: "power3.out" } })
    .from(heroImg, { scale: 1.18, duration: 2.8, ease: "power2.out" }, 0)
    .from(".hero__rotulo", { opacity: 0, y: 16, duration: 1 }, 0.3)
    .from(".hero__titulo .linha > span", { yPercent: 105, duration: 1.3, stagger: 0.12 }, 0.45)
    .from(".hero__texto, .hero__acoes, .relogio-vivo", { opacity: 0, y: 24, duration: 1, stagger: 0.12 }, 1.1)
    .from(".topo", { opacity: 0, duration: 1.2 }, 0.6);

  gsap.to(heroImg, { yPercent: 14, scale: 1.08, ease: "none", scrollTrigger: { trigger: hero, start: "top top", end: "bottom top", scrub: true } });
  gsap.to(".hero__conteudo", { yPercent: -18, opacity: 0.1, ease: "none", scrollTrigger: { trigger: hero, start: "40% top", end: "bottom top", scrub: true } });

  /* ---------- Manifesto: as palavras acendem com a rolagem ---------- */
  document.querySelectorAll("[data-palavras]").forEach((el) => {
    el.setAttribute("aria-label", el.textContent.trim());
    el.innerHTML = el.textContent.trim().split(/\s+/).map((w) => `<span class="palavra" aria-hidden="true">${w}</span>`).join(" ");
    gsap.to(el.querySelectorAll(".palavra"), {
      opacity: 1, stagger: 0.12, ease: "none",
      scrollTrigger: { trigger: el, start: "top 80%", end: "bottom 45%", scrub: true },
    });
  });

  /* ---------- Contadores (só números reais) ---------- */
  document.querySelectorAll("[data-contar]").forEach((el) => {
    const fim = +el.dataset.contar, obj = { v: 0 };
    gsap.to(obj, { v: fim, duration: 1.6, ease: "power2.out", snap: { v: 1 },
      onUpdate: () => { el.textContent = obj.v; },
      scrollTrigger: { trigger: el, start: "top 88%", once: true } });
  });

  /* ---------- Revelações editoriais ---------- */
  const revelaveis = ".titulo-secao, .encontros__intro, .manifesto__texto p, .numeros li, .passo, .marco, .citacoes li, .patrocinio, .faq details, .convite__titulo, .convite p, .convite__acoes, .rodape__grade > *, .vozes__nota, .fundadoras__intro, .fundadora";
  gsap.utils.toArray(revelaveis).forEach((el) => el.classList.add("revelar"));
  ScrollTrigger.batch(".revelar", {
    start: "top 88%", once: true,
    onEnter: (lote) => gsap.to(lote, { opacity: 1, y: 0, duration: 1.1, ease: "power3.out", stagger: 0.1, overwrite: true }),
  });

  /* ---------- Fundadoras: o retrato assenta dentro do arco (a escala vai no <img>; o hover do CSS fica no <picture>) ---------- */
  gsap.from(".fundadora__foto img", { scale: 1.2, duration: 1.9, ease: "power3.out", stagger: 0.14,
    scrollTrigger: { trigger: ".fundadoras__lista", start: "top 82%", once: true } });

  /* ---------- Como funciona: parallax dentro da moldura em arco ---------- */
  gsap.fromTo(".como__moldura img", { yPercent: -12 }, { yPercent: 0, ease: "none",
    scrollTrigger: { trigger: ".como", start: "top bottom", end: "bottom top", scrub: true } });

  /* ---------- Linha do tempo: o fio dourado se desenha ---------- */
  const linha = document.querySelector(".linha-tempo");
  linha.style.setProperty("--progresso", 0);
  ScrollTrigger.create({ trigger: linha, start: "top 70%", end: "bottom 60%", scrub: true,
    onUpdate: (s) => linha.style.setProperty("--progresso", s.progress.toFixed(3)) });

  /* ---------- Selo do convite: o traço se desenha (sem girar a marca) ---------- */
  gsap.from(".convite__selo", { opacity: 0, scale: 0.85, duration: 1.4, ease: "power3.out", scrollTrigger: { trigger: ".convite", start: "top 75%", once: true } });

  /* ---------- Menu: seção atual ---------- */
  document.querySelectorAll(".menu ul a").forEach((a) => {
    const sec = document.querySelector(a.getAttribute("href"));
    if (!sec) return;
    ScrollTrigger.create({ trigger: sec, start: "top 50%", end: "bottom 50%",
      onToggle: (s) => s.isActive ? a.setAttribute("aria-current", "true") : a.removeAttribute("aria-current") });
  });

  /* ---------- Rodapé: as cortinas se abrem e revelam a marca ---------- */
  const cortina = document.querySelector(".cortina");
  const tl = gsap.timeline({ scrollTrigger: { trigger: cortina, start: "top 85%", end: "center center", scrub: 1.2 } });
  tl.fromTo(".cortina__lado--esq", { xPercent: 0 }, { xPercent: -46, ease: "power2.inOut" }, 0)
    .fromTo(".cortina__lado--dir", { xPercent: 0 }, { xPercent: 46, ease: "power2.inOut" }, 0)
    .fromTo(".cortina__revelacao", { opacity: 0, scale: 0.92 }, { opacity: 1, scale: 1, ease: "power2.out" }, 0.25)
    .fromTo(".cortina__relogio .traco", { strokeDasharray: 1, strokeDashoffset: 1 }, { strokeDashoffset: 0, stagger: 0.12, ease: "none" }, 0.3)
    .fromTo(".cortina__relogio .ponto", { scale: 0, transformOrigin: "50% 50%" }, { scale: 1, ease: "back.out(3)" }, 0.75)
    .fromTo(".cortina__frase", { opacity: 0, y: 20 }, { opacity: 1, y: 0 }, 0.7);

  iniciarPoeira();

  // Recalcula depois que as fontes carregam (alturas mudam)
  document.fonts && document.fonts.ready.then(() => ScrollTrigger.refresh());

  /* ---------- Poeira dourada na luz das cinco (canvas, só quando visível) ---------- */
  function iniciarPoeira() {
    const canvas = document.querySelector(".cortina__poeira");
    if (!canvas || semMovimento) return;
    const ctx = canvas.getContext("2d");
    if (!ctx) return;
    let w, h, dpr, particulas = [], rodando = false, raf;

    const criar = () => {
      dpr = Math.min(devicePixelRatio || 1, 2);
      w = canvas.clientWidth; h = canvas.clientHeight;
      canvas.width = w * dpr; canvas.height = h * dpr;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      const n = Math.round(Math.min(140, (w * h) / 11000));
      particulas = Array.from({ length: n }, () => novo(true));
    };
    const novo = (inicio) => ({
      x: w * (0.3 + Math.random() * 0.4) + (Math.random() - 0.5) * w * 0.25,
      y: inicio ? Math.random() * h : h + 10,
      r: Math.random() * 1.6 + 0.3,
      vy: -(Math.random() * 0.35 + 0.08),
      vx: (Math.random() - 0.5) * 0.18,
      a: Math.random() * 0.6 + 0.2,
      f: Math.random() * Math.PI * 2,
    });
    const quadro = () => {
      ctx.clearRect(0, 0, w, h);
      for (const p of particulas) {
        p.f += 0.02; p.x += p.vx + Math.sin(p.f) * 0.15; p.y += p.vy;
        if (p.y < -10) Object.assign(p, novo(false));
        const brilho = p.a * (0.6 + 0.4 * Math.sin(p.f * 1.7));
        const g = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.r * 4);
        g.addColorStop(0, `rgba(243,221,174,${brilho})`);
        g.addColorStop(1, "rgba(216,166,90,0)");
        ctx.fillStyle = g;
        ctx.beginPath(); ctx.arc(p.x, p.y, p.r * 4, 0, Math.PI * 2); ctx.fill();
      }
      raf = requestAnimationFrame(quadro);
    };
    criar();
    addEventListener("resize", () => { cancelAnimationFrame(raf); criar(); if (rodando) quadro(); });
    new IntersectionObserver(([e]) => {
      rodando = e.isIntersecting;
      cancelAnimationFrame(raf);
      if (rodando) quadro();
    }).observe(canvas);
  }
})();
