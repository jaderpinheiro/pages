// Desenvolvimento: reempacota o JavaScript a cada alteração e serve o site em localhost.
import * as esbuild from 'esbuild';
import { builds } from './build.mjs';

for (const options of builds) await (await esbuild.context(options)).watch();
await import('./serve.mjs');
