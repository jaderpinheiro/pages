// Copia as bibliotecas de runtime de node_modules para assets/vendor,
// para que o site funcione sem depender de CDN.
import { copyFileSync, mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const out = join(root, 'assets', 'vendor');

const files = [
  ['node_modules/gsap/dist/gsap.min.js', 'gsap.min.js'],
  ['node_modules/gsap/dist/ScrollTrigger.min.js', 'ScrollTrigger.min.js'],
  ['node_modules/gsap/dist/SplitText.min.js', 'SplitText.min.js'],
  ['node_modules/lenis/dist/lenis.min.js', 'lenis.min.js'],
  ['node_modules/@emailjs/browser/dist/email.min.js', 'email.min.js'],
  // O Three.js não é copiado: ele entra no pacote assets/js/app.min.js (npm run build).
];

for (const [src, dest] of files) {
  const target = join(out, dest);
  mkdirSync(dirname(target), { recursive: true });
  try {
    copyFileSync(join(root, src), target);
    console.log('✓', dest);
  } catch {
    console.log('–', dest, '(não encontrado nesta versão)');
  }
}
