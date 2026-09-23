// Converte o vídeo cinematográfico do hero em sequência de frames WebP
// (desktop e mobile) e gera assets/frames/manifest.json.
// Uso: npm run frames -- caminho/para/hero.mp4 [--fps 24]
//
// Requer o binário do ffmpeg: use o ffmpeg-static (npm install-scripts approve ffmpeg-static
// && npm rebuild ffmpeg-static) ou tenha o `ffmpeg` disponível no PATH.
import { spawnSync } from 'node:child_process';
import { existsSync, mkdirSync, readdirSync, rmSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const args = process.argv.slice(2);
const input = args.find((a) => !a.startsWith('--'));
const fps = Number(args[args.indexOf('--fps') + 1]) || 24;
if (!input || !existsSync(input)) {
  console.error('Informe o vídeo: npm run frames -- caminho/para/hero.mp4');
  process.exit(1);
}

let ffmpeg = 'ffmpeg';
try {
  const mod = await import('ffmpeg-static');
  if (mod.default && existsSync(mod.default)) ffmpeg = mod.default;
} catch { /* usa o ffmpeg do PATH */ }

const outRoot = join(root, 'assets', 'frames');
const variants = [
  { name: 'desktop', width: 1920, fps, quality: 72 },
  { name: 'mobile', width: 960, fps: Math.round(fps * 0.66), quality: 68 },
];

const manifest = { range: [0.05, 0.95] };
for (const v of variants) {
  const dir = join(outRoot, v.name);
  rmSync(dir, { recursive: true, force: true });
  mkdirSync(dir, { recursive: true });
  const res = spawnSync(ffmpeg, [
    '-y', '-i', input,
    '-vf', `fps=${v.fps},scale=${v.width}:-2:flags=lanczos`,
    '-c:v', 'libwebp', '-quality', String(v.quality), '-compression_level', '5',
    join(dir, 'f_%04d.webp'),
  ], { stdio: 'inherit' });
  if (res.status !== 0) { console.error('ffmpeg falhou para', v.name); process.exit(1); }
  const count = readdirSync(dir).filter((f) => f.endsWith('.webp')).length;
  manifest[v.name] = { count, pattern: `assets/frames/${v.name}/f_%04d.webp` };
  console.log(`✓ ${v.name}: ${count} frames`);
}
writeFileSync(join(outRoot, 'manifest.json'), JSON.stringify(manifest, null, 2));
writeFileSync(
  join(root, 'assets', 'js', 'frames-config.js'),
  `// Gerado por \`npm run frames\`. Para voltar à cena 3D, defina FRAMES = null.\nexport const FRAMES = ${JSON.stringify(manifest, null, 2)};\n`,
);
const { options } = await import('./build.mjs');
await (await import('esbuild')).build(options);
console.log('✓ frames-config.js + app.min.js — o hero passa a usar os frames do vídeo automaticamente.');
