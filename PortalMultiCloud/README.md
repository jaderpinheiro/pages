# Portal MultiCloud — experiência cinematográfica

Landing page única da MultiCloud (multicloud.com.br) em **HTML, CSS e JavaScript puros**, com rolagem narrativa,
cena 3D em tempo real (Three.js/WebGL), GSAP + ScrollTrigger e rolagem suave com Lenis.
Cores, tipografia e logo são os oficiais do site atual.

## Como rodar

O site abre de duas formas:

- **Direto do disco:** dois cliques no `index.html` (funciona sem servidor).
- **Em desenvolvimento:** `npm run dev` → http://localhost:5173 (reempacota o JavaScript a cada alteração).

```bash
npm install
npm run vendor   # copia GSAP, ScrollTrigger, SplitText, Lenis e EmailJS para assets/vendor
npm run build    # empacota assets/js/*.js + Three.js em assets/js/app.min.js
npm run dev      # servidor local + reempacotamento automático
```

**Importante:** o navegador carrega `assets/js/app.min.js`. Depois de editar qualquer arquivo em `assets/js/`,
rode `npm run build` (ou mantenha o `npm run dev` rodando). O empacotamento existe porque o navegador bloqueia
módulos JavaScript quando o `index.html` é aberto direto do disco (`file://`).

Para publicar, envie **apenas** `index.html` e a pasta `assets/`. As pastas `fontes/`, `tools/`, `_backup/`
e `node_modules/` não vão para o servidor.

## Estrutura

| Caminho | Conteúdo |
| --- | --- |
| `index.html` | Todo o conteúdo em HTML real (indexável, acessível, editável) |
| `assets/css/main.css` | Design system (tokens de cor e tipografia) e todas as seções |
| `assets/js/app.min.js` | **Gerado** por `npm run build` — é o arquivo que o navegador carrega |
| `assets/js/main.js` | Inicialização: Lenis + GSAP, abertura, palco 3D, seções |
| `assets/js/loader.js` | Abertura: o símbolo e o letreiro voam e se encaixam no logo da navegação |
| `assets/js/infra.js` | Palco do hero (foto → gêmeo 3D → vista explodida → mergulho) e remontagem no contato |
| `assets/js/infra-scene.js` | Cena Three.js do módulo de infraestrutura (texturas procedurais, sem downloads) |
| `assets/js/hero-frames.js` | Alternativa por sequência de frames (quando houver vídeo) |
| `assets/js/sections.js` | Animações de cada seção |
| `assets/js/security-canvas.js` | Camadas de proteção em Canvas 2D |
| `assets/js/contact.js` | Formulário (EmailJS) e ferramentas WebMCP |
| `assets/js/ui.js` | Navegação, menu, âncoras, trilho de progresso, cursor, WhatsApp flutuante |
| `fontes/` | Originais (logos de clientes, fotos da liderança, imagens do Higgsfield, marca) |
| `tools/` | Servidor local, otimização de imagens, vendor, extração de frames |
| `_backup/` | Cópia do site atual (HTML + marca) feita antes do trabalho |

## Narrativa

1. **Abertura** — anel, símbolo e letreiro; contador ligado ao carregamento real; a marca se encaixa no menu.
2. **Hero** — foto do módulo (Higgsfield); ao rolar, uma linha de varredura a transforma no gêmeo 3D, que se
   abre em vista explodida técnica com legendas (recursos reais: firewalls, monitoramento 24/7, backup gerenciado,
   performance, failover automático, alta disponibilidade). A câmera mergulha no interior e termina na pergunta
   "Quanto custa sua empresa ficar parada 1 hora?".
3. **Indisponibilidade** — relógio de 60 minutos; os custos por hora do site atual crescem minuto a minuto.
4. **A MultiCloud** — "23" que se preenche, texto que acende palavra a palavra, pilares, faixa do datacenter.
5. **Soluções** — trilho horizontal (desktop) com as 4 soluções reais e visuais próprios.
6. **Arquitetura** — pilha isométrica "da estratégia à operação" montada só com as soluções reais.
7. **Segurança** — anéis de proteção defletindo ameaças (os 5 recursos de Segurança).
8. **Suporte** — relógio 24h ao vivo (horário de Goiânia) e faixa que reage à rolagem.
9. **Clientes, Liderança, Jornada, FAQ, Contato** — no contato o módulo 3D se remonta, fechando o ciclo.

Rolar para trás devolve exatamente cada estado (todo o hero é função do progresso da rolagem).

## Campos editáveis

O conteúdo está direto no `index.html`. Mapa dos campos do briefing:

| Campo | Valor atual (do site oficial) | Onde |
| --- | --- | --- |
| `{{NOME_EMPRESA}}` | MultiCloud | `<title>`, metas, JSON-LD, textos |
| `{{SLOGAN}}` | A sua nuvem, sem limites. | `#heroTitle` |
| `{{POSICIONAMENTO}}` | Além da nuvem · 23 anos de expertise | eyebrow do hero |
| `{{DESCRICAO_EMPRESA}}` | "Com mais de duas décadas de expertise…" | `.hero__lead` |
| `{{HISTORIA_EMPRESA}}` | "Com 23 anos no mercado…" (desde 2003, conforme o JSON-LD atual) | `#about` |
| `{{CIDADE_ESTADO}}` | Goiânia – GO | `#about`, `#contact`, rodapé |
| `{{WHATSAPP}}` | (62) 3142-0818 → `https://wa.me/556231420818` | todos os botões de WhatsApp |
| `{{EMAIL}}` | comercial@multicloud.com.br | topo, contato, rodapé |
| `{{ENDERECO}}` | Av. Assis Chateaubriand, 1595 — 2º andar, sala 09 — St. Oeste, Goiânia – GO, 74130-012 | `#contact`, FAQ, JSON-LD |
| `{{SITE}}` | https://multicloud.com.br/ | canonical, Open Graph, JSON-LD |
| `{{INSTAGRAM}}` / `{{LINKEDIN}}` | links do rodapé do site atual | `.footer__social` |
| `{{SERVICO_01…04}}` | Nuvem Privada, Nuvem Pública, Segurança, Governança de TI | `#solutions` |
| `{{SERVICO_05}}` / `{{SERVICO_06}}` | não existem no conteúdo real — não criados | — |
| `{{DIFERENCIAL_01…03}}` | De ponta a ponta, Especialistas próximos, Inovação constante | `.pillars` |
| `{{CLIENTE_…}}` | 19 logos reais do site atual | `#clients` |
| `{{CTA_PRINCIPAL}}` | Falar com Especialista (WhatsApp) | hero, navegação |
| `{{CTA_SECUNDARIO}}` | Conheça as Soluções | hero |
| `{{ETAPA_01…04}}` | Contato, Entendimento, Arquitetura, Operação (frases do site atual) | `#jornada` |
| `{{COR_DE_DESTAQUE}}` | `#01aeff` | `--blue` em `main.css` |
| `{{DEPOIMENTO_01…03}}`, `{{CASE_01…}}` | **sem conteúdo real** — blocos prontos e desativados (comentados) | antes de `#jornada` |
| `{{CERTIFICACAO_01}}`, `{{PARCEIRO_01}}` | **sem conteúdo real** — não criados | — |

## Pendências de conteúdo (para revisar com a MultiCloud)

- **Conmais**: o arquivo `CONMAIS.png` do site atual é, na verdade, o logo da Conenge. O logo não aparece até
  existir o arquivo correto (coloque-o em `fontes/clientes/` e rode `npm run images clientes`).
- **Gopwer**: `GOPWER.png` é uma cópia do logo da OnPower; mantida só a OnPower.
- **Globostell** → pelo logo, o nome correto é **Globsteel**; **Vila Cavalcare** → **Villa Cavalcare**.
- **Redes sociais** (mantidas como no site atual, mas parecem incorretas): `https://www.linkedin.com/multicloud/`
  (LinkedIn usa `/company/...`) e `https://tiktok.com/muilticloud` (erro de digitação e sem `@`).
- **FAQ**: o site atual não tem FAQ; as 8 respostas foram escritas **somente** com informações já publicadas.
  Vale uma revisão da equipe.
- O botão "Telefone 0800" do site atual apontava para o número (62); aqui o 0800 liga para o 0800.

## Correções em relação ao site atual

- O formulário do site atual **não enviava**: uma segunda função `handleForm` sobrescrevia a do EmailJS.
  Aqui o envio usa a mesma conta, serviço e template (`office365_service` / `template_9ypsjlq`), com validação.
- EmailJS atualizado do SDK depreciado (cdn.emailjs.com) para o **SDK v4** local.
- O Google Analytics (G-SFLP29Z77V) é preservado, mas **não carrega em localhost**, para os testes não poluírem os dados.

## Vídeo cinematográfico (Higgsfield) — próximo passo

As imagens do hero e do datacenter foram geradas no Higgsfield (Cinema Studio Image 2.5). O vídeo não foi gerado:
a conta tem plano free com 5 créditos, e o Cinema Studio Video custa 12 créditos (8 s, qualidade pro) ou 5 créditos
(5 s, padrão), além da imagem de partida. Enquanto isso, o hero usa a cena 3D em tempo real, que já cumpre a
narrativa (vista explodida precisa, reversível e sem downloads de frames).

Quando houver créditos:

1. Gere o vídeo no Higgsfield a partir de `fontes/visuais/hero-infraestrutura.png` como quadro inicial (16:9, sem áudio).
2. Libere o ffmpeg: `npm install-scripts approve ffmpeg-static` e depois `npm rebuild ffmpeg-static` (ou tenha `ffmpeg` no PATH).
3. Rode o comando abaixo. Ele gera os frames WebP (desktop e mobile) e o `assets/js/frames-config.js`,
   e o hero passa a usar o vídeo automaticamente. Para voltar à cena 3D, defina `FRAMES = null` nesse arquivo.

```bash
npm run frames -- caminho/para/hero.mp4
```

## Modos e diagnóstico

- `?lite` força a versão leve (sem WebGL), a mesma usada em aparelhos modestos ou com economia de dados.
- `?nosmooth` desliga a rolagem suave; `?debug` expõe o estado em `window.__mc`.
- `prefers-reduced-motion` desliga abertura, rolagem cinematográfica, grão e parallax, e mantém todo o conteúdo.

## Imagens

`npm run images` gera, a partir de `fontes/`: logos de clientes monocromáticos (WebP com transparência),
fotos da liderança (WebP 720 px), símbolo e letreiro da marca e as imagens do Higgsfield em 960/1600/2400 px.
