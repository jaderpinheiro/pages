// Empacota o JavaScript do site (assets/js/*.js + Three.js) em um único arquivo
// comum (IIFE, sem módulos): assets/js/app.min.js. Assim o site funciona tanto em
// servidor quanto abrindo o index.html direto do disco (file://), onde o navegador
// bloqueia <script type="module">.
// Uso: npm run build   |   node tools/build.mjs --watch
import * as esbuild from 'esbuild';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');

export const options = {
  entryPoints: [join(root, 'assets/js/main.js')],
  outfile: join(root, 'assets/js/app.min.js'),
  bundle: true,
  format: 'iife',
  target: ['es2020'],
  minify: true,
  legalComments: 'none',
  logLevel: 'info',
};

const isMain = process.argv[1] && resolve(process.argv[1]).toLowerCase() === fileURLToPath(import.meta.url).toLowerCase();
if (isMain) {
  if (process.argv.includes('--watch')) {
    const ctx = await esbuild.context(options);
    await ctx.watch();
  } else {
    await esbuild.build(options);
  }
}
