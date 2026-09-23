// Desenvolvimento: reempacota o JavaScript a cada alteração e serve o site em localhost.
import * as esbuild from 'esbuild';
import { options } from './build.mjs';

const ctx = await esbuild.context(options);
await ctx.watch();
await import('./serve.mjs');
