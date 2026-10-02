// Empacota o JavaScript do site em arquivos comuns (IIFE, sem módulos), para o site
// funcionar tanto em servidor quanto abrindo o index.html direto do disco (file://),
// onde o navegador bloqueia <script type="module">.
//   assets/js/app.min.js    site (assets/js/*.js), carregado pelo index.html
//   assets/js/scene.min.js  cena 3D (Three.js), buscada pelo app.min.js em paralelo à abertura
// Uso: npm run build   |   node tools/build.mjs --watch
import * as esbuild from 'esbuild';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');

const common = {
  bundle: true,
  format: 'iife',
  target: ['es2020'],
  minify: true,
  legalComments: 'none',
  logLevel: 'info',
};

export const builds = [
  { ...common, entryPoints: [join(root, 'assets/js/main.js')], outfile: join(root, 'assets/js/app.min.js') },
  { ...common, entryPoints: [join(root, 'assets/js/scene-entry.js')], outfile: join(root, 'assets/js/scene.min.js') },
];

const isMain = process.argv[1] && resolve(process.argv[1]).toLowerCase() === fileURLToPath(import.meta.url).toLowerCase();
if (isMain) {
  if (process.argv.includes('--watch')) {
    for (const options of builds) await (await esbuild.context(options)).watch();
  } else {
    for (const options of builds) await esbuild.build(options);
  }
}
