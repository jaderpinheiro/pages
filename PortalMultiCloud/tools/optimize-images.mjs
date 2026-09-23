// Gera as imagens otimizadas do site (assets/img) a partir dos originais em fontes/.
// Uso: npm run images [clientes|lideranca|marca|visuais]
import sharp from 'sharp';
import { readdirSync, mkdirSync, existsSync } from 'node:fs';
import { dirname, join, parse } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const src = (...p) => join(root, 'fontes', ...p);
const img = (...p) => join(root, 'assets', 'img', ...p);
const images = (dir) => (existsSync(dir) ? readdirSync(dir).filter((f) => /\.(png|jpe?g|webp)$/i.test(f)) : []);

// Logos de clientes → silhueta monocromática em branco quente (WebP com transparência)
async function clientes() {
  const out = img('clientes', 'web');
  mkdirSync(out, { recursive: true });
  for (const f of images(src('clientes'))) {
    const name = parse(f).name;
    let mono = await monocromatico(src('clientes', f));
    if (TRACO_FINO.has(name)) mono = await engrossar(mono);
    await sharp(mono)
      .trim({ threshold: 1 })
      .resize({ width: 360, height: 150, fit: 'inside', withoutEnlargement: true })
      .webp({ quality: 88, alphaQuality: 100 })
      .toFile(join(out, `${name}.webp`));
    console.log('✓ cliente', name);
  }
}

// Logos desenhados só com contorno fino: o traço é levemente engrossado para ter
// o mesmo peso visual dos demais na grade.
const TRACO_FINO = new Set(['globostell']);
async function engrossar(buf) {
  const { data, info } = await sharp(buf).blur(2.2).raw().toBuffer({ resolveWithObject: true });
  for (let i = 3; i < data.length; i += 4) data[i] = Math.min(255, data[i] * 3.2);
  return sharp(data, { raw: { width: info.width, height: info.height, channels: 4 } }).png().toBuffer();
}

// Converte o logo em silhueta branca-quente, preservando os vazados
// (letras brancas sobre blocos coloridos viram recorte).
async function monocromatico(file) {
  const { data, info } = await sharp(file).ensureAlpha().raw().toBuffer({ resolveWithObject: true });
  const px = info.width * info.height;
  let transparentes = 0, somaLum = 0, opacos = 0, escuros = 0;
  for (let i = 0; i < data.length; i += 4) {
    if (data[i + 3] < 250) transparentes++;
    if (data[i + 3] > 128) {
      const lum = 0.2126 * data[i] + 0.7152 * data[i + 1] + 0.0722 * data[i + 2];
      somaLum += lum; opacos++;
      if (lum < 140) escuros++;
    }
  }
  const temAlpha = transparentes / px > 0.05;
  // Logo branco sobre transparente (sem conteúdo escuro); se houver conteúdo escuro,
  // é um logo sobre "placa" branca e o branco deve virar transparência.
  const logoClaro = temAlpha && somaLum / Math.max(1, opacos) > 200 && escuros / Math.max(1, opacos) < 0.01;
  const out = Buffer.alloc(data.length);
  const hist = new Array(256).fill(0);
  for (let i = 0; i < data.length; i += 4) {
    const distBranco = Math.max(255 - data[i], 255 - data[i + 1], 255 - data[i + 2]);
    const a = logoClaro ? data[i + 3] : (data[i + 3] * Math.min(255, distBranco * 2.4)) / 255;
    out[i] = 243; out[i + 1] = 241; out[i + 2] = 236; out[i + 3] = Math.round(a);
    if (a > 12) hist[Math.round(a)]++;
  }
  // Normaliza a opacidade: logos desenhados com transparência parcial (ex.: Globsteel)
  // passam a ter o traço mais forte em 100%, como os demais.
  const total = hist.reduce((s, v) => s + v, 0);
  let acc = 0, p95 = 255;
  for (let v = 0; v < 256; v++) { acc += hist[v]; if (acc >= total * 0.95) { p95 = Math.max(v, 1); break; } }
  const k = Math.min(4, 255 / p95);
  if (k > 1.02) for (let i = 3; i < out.length; i += 4) out[i] = Math.min(255, Math.round(out[i] * k));
  return sharp(out, { raw: { width: info.width, height: info.height, channels: 4 } }).png().toBuffer();
}

async function lideranca() {
  const out = img('lideranca', 'web');
  mkdirSync(out, { recursive: true });
  for (const f of images(src('lideranca'))) {
    const name = parse(f).name;
    await sharp(src('lideranca', f)).resize({ width: 720 }).webp({ quality: 82 }).toFile(join(out, `${name}.webp`));
    console.log('✓ liderança', name);
  }
}

async function marca() {
  const logo = src('marca', 'MultiCloud.png');
  const meta = await sharp(logo).metadata();
  mkdirSync(img('brand'), { recursive: true });
  // Símbolo (nuvem): ocupa ~20,5% da largura do logo horizontal.
  const w = Math.round(meta.width * 0.205);
  const simbolo = await sharp(logo).extract({ left: 0, top: 0, width: w, height: meta.height }).png().toBuffer();
  const s = await sharp(simbolo).trim().png().toFile(img('brand', 'multicloud-simbolo.png'));
  // Letreiro (sem o símbolo) para a abertura; as frações permitem encaixar cada parte
  // exatamente sobre o logo completo da navegação (LOGO_PARTS em assets/js/loader.js).
  const letreiro = await sharp(logo).extract({ left: w, top: 0, width: meta.width - w, height: meta.height }).png().toBuffer();
  const l = await sharp(letreiro).trim().png().toFile(img('brand', 'multicloud-letreiro.png'));
  await sharp(logo).resize({ width: 480 }).png({ compressionLevel: 9 }).toFile(img('brand', 'multicloud-logo.png'));
  const frac = (left, top, width, height) => ({
    x: +(left / meta.width).toFixed(4), y: +(top / meta.height).toFixed(4),
    w: +(width / meta.width).toFixed(4), h: +(height / meta.height).toFixed(4),
  });
  console.log('✓ marca — símbolo', JSON.stringify(frac(-s.trimOffsetLeft, -s.trimOffsetTop, s.width, s.height)));
  console.log('✓ marca — letreiro', JSON.stringify(frac(w - l.trimOffsetLeft, -l.trimOffsetTop, l.width, l.height)));
}

// Imagens conceituais (Higgsfield) → WebP responsivo em 960/1600/2400
async function visuais() {
  for (const f of images(src('visuais'))) {
    const name = parse(f).name;
    for (const width of [960, 1600, 2400]) {
      await sharp(src('visuais', f))
        .resize({ width, withoutEnlargement: true })
        .webp({ quality: width > 2000 ? 78 : 80 })
        .toFile(img('visuais', `${name}-${width}.webp`));
    }
    console.log('✓ visual', name);
  }
}

const only = process.argv[2];
if (!only || only === 'clientes') await clientes();
if (!only || only === 'lideranca') await lideranca();
if (!only || only === 'marca') await marca();
if (!only || only === 'visuais') await visuais();
