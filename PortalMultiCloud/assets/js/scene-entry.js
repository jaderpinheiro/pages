// Entrada do pacote da cena 3D (assets/js/scene.min.js): Three.js + infra-scene.js.
// Fica fora do app.min.js para a abertura não esperar o download do Three.js;
// o infra.js carrega este arquivo em paralelo e lê a cena de window.__mcScene.
import { createInfraScene } from './infra-scene.js';

window.__mcScene = { createInfraScene };
