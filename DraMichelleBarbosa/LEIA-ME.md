# Landing page — Dra. Michelle Barbosa

Site estático de página única. Sem build, sem dependências de servidor: sobe direto em
qualquer hospedagem (Hostinger, Vercel, Netlify, cPanel, S3).

```
/index.html                  página completa (HTML + CSS + JS inline)
/robots.txt                  libera Google e agentes de IA (GPTBot, ClaudeBot, PerplexityBot…)
/sitemap.xml                 sitemap com image:image
/llms.txt                    resumo estruturado para LLMs / motores de resposta
/manifest.webmanifest        PWA básico
/favicon.svg                 ícone
/assets/                     imagens .webp otimizadas + og-cover.jpg
```

Peso do primeiro carregamento: **~79 KB** (HTML + imagem do hero). As outras 12 imagens
entram por `loading="lazy"`.

---

## 1. Antes de publicar — 4 ajustes obrigatórios

### a) Domínio
Substitua `https://www.dramichellebarbosa.com.br` pelo domínio real em **3 arquivos**:
`index.html` (canonical, Open Graph e JSON-LD), `sitemap.xml` e `robots.txt`.

No Linux/Mac:
```bash
grep -rl "dramichellebarbosa.com.br" . | xargs sed -i 's|www\.dramichellebarbosa\.com\.br|SEUDOMINIO.com.br|g'
```

### b) Horário de atendimento
Está no bloco `CFG`, no fim do `index.html`. **Coloquei um valor provisório** —
confirme com a Dra. Michelle:

```js
horario: "Segunda a sexta, 08h às 18h · Sábado sob agendamento",
```

### c) Coordenadas do mapa
A latitude/longitude no JSON-LD (`-16.706 / -49.265`) é aproximada para o Jardim América.
Pegue a exata no Google Maps (clique com o botão direito no prédio → copiar coordenadas)
e troque nas metatags `geo.position` / `ICBM` e no bloco `"geo"` do JSON-LD.

### d) Google Search Console
Cadastre o domínio e envie `sitemap.xml`. Vincule também o perfil do Google Business
ao endereço do Edifício Inove — é o que mais move o ponteiro em busca local.

---

## 2. Tudo que muda sem mexer em código

Bloco `CFG` no fim do `index.html`:

```js
const CFG = {
  telefone   : "5562992024773",        // WhatsApp, só números com DDI+DDD
  telefoneFmt: "(62) 99202-4773",
  instagram  : "https://www.instagram.com/dramichelle.barbosa/",
  facebook   : "https://www.facebook.com/dramichellebarbosademelo/",
  clinica    : "Essenciale Odontologia",
  maps       : "...",
  horario    : "...",
  msgPadrao  : "Olá, Dra. Michelle! Vim pelo site e gostaria de agendar uma avaliação.",
  depoimentos: []                      // vazio = seção fica oculta
};
```

**Depoimentos:** a seção existe, está oculta e aparece sozinha quando você preencher:

```js
depoimentos: [
  { texto:"Texto do paciente, autorizado por escrito.", autor:"Nome, tratamento" },
]
```

---

## 3. Conformidade com o CFO — leia antes de publicar

A página foi escrita dentro da **Resolução CFO-196/2019**:

- Nome e **CRO-GO 9865** aparecem no cabeçalho, no rodapé, nas fotos de casos e no JSON-LD.
- **Nenhum preço, promoção, desconto ou condição de pagamento** — inclusive a pergunta
  sobre valores no FAQ responde explicando que preço só na consulta.
- Sem superlativo, sem "o melhor", sem promessa ou garantia de resultado.
- Aviso permanente na seção de resultados sobre variação individual e sobre quais
  imagens são conceituais (IA) e quais são casos reais.

**Pendência sua:** as três fotos de antes/depois exigem **TCLE assinado** por cada
paciente, arquivado no consultório. Sem o termo, remova o bloco `<section id="resultados">`
inteiro — o resto da página continua funcionando normalmente.

**Observação:** a foto original do contorno labial trazia a marca *Elleva Odontologia*.
Recortei a imagem para remover o logo, já que o site é da marca pessoal da Dra. Michelle
e o consultório indicado é o Essenciale. Se essa foto for de um caso feito na Elleva,
confirme com ela se pode ser usada aqui.

---

## 4. Imagens

**Reais (fotos suas):** retrato da Dra. Michelle, foto com o scanner iTero Element 5D
(recortada para tirar o reflexo de terceiros no espelho) e os 3 casos de antes/depois,
todos com o selo `Dra. Michelle Barbosa · CRO-GO 9865` gravado no pixel.

**Conceituais (geradas por IA no Higgsfield):** a arcada dourada do hero, o alinhador
transparente, o close de harmonização e o close de clareamento. São ilustrativas,
não retratam pacientes, e o site declara isso.

Nenhuma foto de paciente real foi enviada para serviço de IA.

---

## 5. O hero cinematográfico

O efeito de atravessar a arcada é **100% código** — canvas com partículas douradas +
transformações 3D controladas pelo scroll. Não existe vídeo para baixar, o que mantém
o LCP baixo.

- Com GSAP + ScrollTrigger + Lenis (CDN): scrub suave e smooth scroll no desktop.
- Se a CDN falhar: um driver nativo em `requestAnimationFrame` assume e o efeito
  continua funcionando. Testado com as três bibliotecas bloqueadas — zero erro de JS.
- `prefers-reduced-motion`: animações desligadas, hero vira estático.

Se quiser trocar por um vídeo real depois, é só recarregar créditos no Higgsfield.

---

## 6. SEO e SEO agêntico

- `title`, `meta description`, canonical, Open Graph e Twitter Card completos.
- JSON-LD com `Dentist` + `MedicalBusiness` + `LocalBusiness`, `Person` (com CRO como
  `identifier`), `FAQPage`, `WebSite`, `WebPage` e `speakable`.
- Seis perguntas no FAQ marcadas como `FAQPage` — é o que alimenta resposta direta no
  Google, no ChatGPT e no Perplexity.
- `llms.txt` com os dados da clínica em formato que LLM lê sem ambiguidade.
- `robots.txt` liberando explicitamente os crawlers de IA.
- Um único `<h1>`, hierarquia de headings correta, `alt` em todas as 13 imagens,
  `width`/`height` em todas (CLS zero), `lang="pt-BR"`.

---

## 7. Como testar localmente

Os caminhos das imagens são **relativos** (`assets/...`), então funciona dos dois jeitos:

- **Duplo clique no `index.html`** — abre direto no navegador, imagens carregam normal.
  Só o mapa do Google e as fontes/GSAP da CDN precisam de internet.
- **Servidor local**, mais fiel ao ambiente de produção:
  ```bash
  cd DraMichelleBarbosa
  python -m http.server 8080
  # abra http://localhost:8080
  ```

Ao subir para a hospedagem, mande o conteúdo da pasta (não a pasta em si) para a raiz
do site: `index.html` na raiz e a pasta `assets/` ao lado dele.

A pasta `image/` com as fotos originais não é usada pelo site — pode ficar lá como
arquivo de origem ou ser removida do que você envia para a hospedagem.
