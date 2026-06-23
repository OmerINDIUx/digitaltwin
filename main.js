import * as THREE from "three";
import { OrbitControls } from "three/addons/controls/OrbitControls.js";
import { GLTFLoader } from "three/addons/loaders/GLTFLoader.js";
import { DRACOLoader } from "three/addons/loaders/DRACOLoader.js";
import { EffectComposer } from "three/addons/postprocessing/EffectComposer.js";
import { RenderPass } from "three/addons/postprocessing/RenderPass.js";
import { UnrealBloomPass } from "three/addons/postprocessing/UnrealBloomPass.js";
import { OutputPass } from "three/addons/postprocessing/OutputPass.js";
import { MeshSurfaceSampler } from "three/addons/math/MeshSurfaceSampler.js";
import { Sky } from "three/addons/objects/Sky.js";
import {
  applyStandardControls,
  applyTopDownControls,
  setTopDownCameraView,
} from "./src/camera/cameraModes.js";
import {
  API_BASE_URL,
  LATITUDE,
  LONGITUDE,
  SENSOR_API_URL,
} from "./src/config/appConfig.js";
import { digitalTwinData } from "./src/data/digitalTwinData.js";

// --- CONFIGURACIÓN GLOBAL ---
// NOTA: Si usas un subdominio separado (ej: api.tudominio.com),
// cambia API_BASE_URL por: 'https://api.tudominio.com'

let model;
let rain;
let clouds;
let currentWeatherType = "normal";
let weatherSyncEnabled = true; // Permite alternar la sincronización real
const timer = new THREE.Timer();
const lightSensorState = {
  mode: null,
  lastStableState: null,
  consecutiveCount: 0,
  pollTimer: null,
};


// Posiciones de Control de Cámara
let floatingLabel;
const cameraTargetPos = new THREE.Vector3();
const controlsTargetPos = new THREE.Vector3();
const overviewCameraPos = new THREE.Vector3(525, -67.5, 525);
const overviewCameraTarget = new THREE.Vector3(0, -30, 0);
let isCameraMoving = false;
let explodeFactor = 0; // Control global de la vista explosionada
let isPanoActive = false; // Control del giro panorámico
let panoAngle = 0;
const feedLimit = 5;

// --- ETIQUETAS ESPACIALES ---
const spatialLabels = [];
const labelsContainer = document.getElementById("labels-container");

// Valores reales de la DB (se actualizan al abrir el Dashboard)
let dbCounts = { gym: null, pool: null, canchas: null };
let lastActiveReservations = []; // <--- DATOS PARA COLOREADO DE PERSONAS
let currentSelectedRole = null; // Para refrescar la card abierta durante el historial
let liveSyncTimer = null;

const peopleInstances = {
  gym: null,
  pool: null,
  canchas: null,
};
let lastZoneStatuses = {}; // <--- PERSISTENCIA GLOBAL DE ESTADOS
const peopleGeometry = new THREE.CapsuleGeometry(4.0, 10.0, 4, 8); // Personas visibles desde la vista 3D
const peopleMaterial = new THREE.MeshBasicMaterial({
  color: 0xffffff,
  transparent: true,
  opacity: 1.0,
  toneMapped: false,
});

// Estados para movimiento de personas
const peopleStates = {
  gym: [],
  pool: [],
  canchas: [],
};

// Detección de Arrastre para evitar Auto-Reset al Navegar
let clickStartTime = 0;
let clickStartX = 0;
let clickStartY = 0;

function onMouseDown(e) {
  clickStartTime = Date.now();
  clickStartX = e.clientX;
  clickStartY = e.clientY;
}

const container = document.getElementById("container");
const txtHour = document.getElementById("txt-hour");

// --- INTERACTIVIDAD DIGITAL TWIN ---
const raycaster = new THREE.Raycaster();
const mouse = new THREE.Vector2();
let lastTime = performance.now(); // Sistema de tiempo alternativo para evitar warnings
const infoCard = document.getElementById("info-card");
const cardTitle = document.getElementById("card-title");
const currentPeople = document.getElementById("current-people");
const expectedPeople = document.getElementById("expected-people");
const areaStatus = document.getElementById("area-status");
const closeCardBtn = document.getElementById("close-card");

// Variables para el giro de cámara manual (Showroom)
let yaw = 0;
let pitch = 0;
const mouseSensitivity = 0.002;

// --- VIAJE EN EL TIEMPO ---
let isHistoryMode = false;
let historyTimeValue = 0; // Offset en minutos desde el momento actual
const liveDataBackup = JSON.parse(JSON.stringify(digitalTwinData)); // Respaldo para volver a vivo

// Escena y Renderizador
const renderer = new THREE.WebGLRenderer({
  antialias: true,
  alpha: false, // Cambiado a false para que el skybox se vea correctamente y no se mezcle con el fondo CSS
  powerPreference: "high-performance",
});
renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2)); // Optimizado
renderer.setSize(window.innerWidth, window.innerHeight);
renderer.toneMapping = THREE.ACESFilmicToneMapping;
renderer.toneMappingExposure = 0.8; // Restauramos exposición original
renderer.outputColorSpace = THREE.SRGBColorSpace;
container.appendChild(renderer.domElement);

const scene = new THREE.Scene();
// scene.background = new THREE.Color(0xf6f7ff); // Eliminado para usar Cielo procedural
// scene.fog = new THREE.FogExp2(0xf1f5f9, 0.0008); // Eliminamos la neblina

const camera = new THREE.PerspectiveCamera(
  45,
  window.innerWidth / window.innerHeight,
  0.1,
  100000, // <--- Incrementado a 100,000 para que jamás recorte nuestro cielo gigante
);

const controls = new OrbitControls(camera, renderer.domElement);
controls.enableDamping = true;
controls.dampingFactor = 0.1; // Más ágil (anterior 0.05)
controls.rotateSpeed = 1.2; // Rotación más rápida
controls.zoomSpeed = 1.5; // Zoom más potente
controls.maxDistance = 5000; // Más espacio para alejarse
controls.minDistance = 30; // Poder entrar hasta el detalle
// Restringimos la cámara para que nunca baje del horizonte y se vea el abismo
controls.maxPolarAngle = Math.PI / 2.05;

// Iluminación
const ambientLight = new THREE.AmbientLight(0xffffff, 0.4);
scene.add(ambientLight);

const dirLight = new THREE.DirectionalLight(0xfff5e6, 0.7);
dirLight.position.set(100, 200, 50);
scene.add(dirLight);

const fillLight = new THREE.DirectionalLight(0xaabbff, 0.3);
fillLight.position.set(-100, 50, -50);
scene.add(fillLight);

const nightAreaLight = new THREE.HemisphereLight(0xc7ddff, 0x4b5f48, 0);
scene.add(nightAreaLight);

const nightSecurityLight = new THREE.DirectionalLight(0xcfe3ff, 0);
nightSecurityLight.position.set(-450, 700, 360);
scene.add(nightSecurityLight);

const nightLightingGroup = new THREE.Group();
nightLightingGroup.name = "night-lighting-system";
nightLightingGroup.visible = false;
scene.add(nightLightingGroup);
const nightLights = [];
let nightLightLevel = 0;

function createNightLight(position, options = {}) {
  const color = options.color ?? 0xffd88a;
  const pointIntensity = options.intensity ?? 0.9;
  const distance = options.distance ?? 260;
  const height = options.height ?? 36;

  const root = new THREE.Group();
  root.position.set(position.x, position.y ?? 0, position.z);

  const pole = new THREE.Mesh(
    new THREE.CylinderGeometry(1.6, 2.4, height, 10),
    new THREE.MeshStandardMaterial({
      color: 0x1f2937,
      roughness: 0.55,
      metalness: 0.25,
    }),
  );
  pole.position.y = height * 0.5;
  root.add(pole);

  const lampMaterial = new THREE.MeshStandardMaterial({
    color,
    emissive: color,
    emissiveIntensity: 0,
    transparent: true,
    opacity: 0.9,
  });
  const lamp = new THREE.Mesh(new THREE.SphereGeometry(5.5, 18, 18), lampMaterial);
  lamp.position.y = height + 4;
  root.add(lamp);

  const haloMaterial = new THREE.MeshBasicMaterial({
    color,
    transparent: true,
    opacity: 0,
    depthWrite: false,
    blending: THREE.AdditiveBlending,
    side: THREE.DoubleSide,
  });
  const halo = new THREE.Mesh(new THREE.CircleGeometry(options.haloSize ?? 52, 32), haloMaterial);
  halo.position.y = height + 4;
  halo.rotation.x = -Math.PI / 2;
  root.add(halo);

  const groundGlow = new THREE.Mesh(
    new THREE.CircleGeometry(options.groundSize ?? 95, 32),
    new THREE.MeshBasicMaterial({
      color,
      transparent: true,
      opacity: 0,
      depthWrite: false,
      blending: THREE.AdditiveBlending,
      side: THREE.DoubleSide,
    }),
  );
  groundGlow.position.y = 0.18;
  groundGlow.rotation.x = -Math.PI / 2;
  root.add(groundGlow);

  const pointLight = new THREE.PointLight(color, 0, distance, 1.9);
  pointLight.position.y = height + 8;
  root.add(pointLight);

  nightLightingGroup.add(root);
  nightLights.push({
    lampMaterial,
    haloMaterial,
    groundMaterial: groundGlow.material,
    pointLight,
    pointIntensity,
  });
}

function initNightLighting() {
  if (nightLights.length > 0) return;

  [
    { x: -300, z: 245, intensity: 1.15, distance: 310, groundSize: 120 },
    { x: -150, z: 230, intensity: 0.95, distance: 260 },
    { x: 70, z: 60, intensity: 0.85, distance: 250 },
    { x: 250, z: -150, intensity: 0.9, distance: 280 },
    { x: 420, z: -250, intensity: 0.85, distance: 250 },
    { x: -380, z: -130, intensity: 0.8, distance: 240 },
    { x: -40, z: -250, intensity: 0.75, distance: 235 },
    { x: 500, z: 120, intensity: 0.75, distance: 240 },
  ].forEach((config) => createNightLight(config, config));
}

function setNightLighting(level) {
  nightLightLevel += (level - nightLightLevel) * 0.08;
  const activeLevel = nightLightLevel < 0.01 ? 0 : nightLightLevel;

  nightAreaLight.intensity = 0.62 * activeLevel;
  nightSecurityLight.intensity = 0.85 * activeLevel;

  nightLightingGroup.visible = activeLevel > 0;
  nightLights.forEach((light, index) => {
    const shimmer = 0.94 + Math.sin(performance.now() * 0.002 + index * 1.7) * 0.06;
    light.pointLight.intensity = light.pointIntensity * 1.45 * activeLevel * shimmer;
    light.lampMaterial.emissiveIntensity = 1.8 * activeLevel * shimmer;
    light.haloMaterial.opacity = 0.42 * activeLevel;
    light.groundMaterial.opacity = 0.24 * activeLevel;
  });
}

// --- REJILLA DIGITAL (Digital Twin Look) ---
const gridHelper = new THREE.GridHelper(5000, 100, 0x3b82f6, 0x1e293b);
gridHelper.position.y = -1; // Ligeramente bajo el modelo
gridHelper.material.transparent = true;
gridHelper.material.opacity = 0.2;
scene.add(gridHelper);

// --- ANILLO DE SELECCIÓN (ELIMINADO SEGÚN SOLICITUD DEL USUARIO) ---

// --- ETIQUETA FLOTANTE (HTML Overlay) ---
floatingLabel = document.createElement("div");
floatingLabel.className = "floating-label hidden";
floatingLabel.innerHTML = `
            <div class="label-box">
                <span id="label-name" class="label-title">Cargando...</span>
            </div>
            <div class="slider-container">
                <!-- min=-1440 (ayer) | 0 = AHORA | max=+1440 (mañana) -->
                <input type="range" id="history-slider" min="-1440" max="1440" value="0" step="15" class="cyber-slider">
            </div>
  `;
document.body.appendChild(floatingLabel);

// --- CIELO PROCEDURAL 360 ---
const sky = new Sky();
// Esfera super gigante para que el espacio jamás se acabe usando el estandar de Three.js (escalar en vez de cambiar geometría)
sky.scale.setScalar(90000);
sky.position.y = 0; // Centramos todo

sky.material.onBeforeCompile = (shader) => {
  shader.fragmentShader = shader.fragmentShader.replace(
    "vec3 direction = normalize( vWorldPosition - cameraPosition );",
    "vec3 direction = normalize( vWorldPosition - cameraPosition );\n" +
      "// Forzar el azul profundo descendiendo hacia el horizonte (evita neblina gris)\n" +
      "direction.y = 0.1 + abs(direction.y) * 0.9;\n" +
      "direction = normalize(direction);",
  );
};
scene.add(sky);

// --- SISTEMA DE NUBES CINEMÁTICAS ---
const cloudGeometry = new THREE.SphereGeometry(75000, 64, 64);
const cloudMaterial = new THREE.ShaderMaterial({
  transparent: true,
  side: THREE.BackSide,
  uniforms: {
    uTime: { value: 0 },
    uSunPos: { value: new THREE.Vector3() },
    uCloudColor: { value: new THREE.Color(0xffffff) },
    uOpacity: { value: 0.6 },
  },
  vertexShader: `
    varying vec3 vWorldPosition;
    void main() {
      vec4 worldPosition = modelMatrix * vec4(position, 1.0);
      vWorldPosition = worldPosition.xyz;
      gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
    }
  `,
  fragmentShader: `
    uniform float uTime;
    uniform vec3 uSunPos;
    uniform vec3 uCloudColor;
    uniform float uOpacity;
    varying vec3 vWorldPosition;

    float hash(vec2 p) {
      return fract(sin(dot(p, vec2(127.1, 311.7))) * 43758.5453123);
    }

    float noise(vec2 p) {
      vec2 i = floor(p);
      vec2 f = fract(p);
      vec2 u = f * f * (3.0 - 2.0 * f);
      return mix(mix(hash(i + vec2(0.0, 0.0)), hash(i + vec2(1.0, 0.0)), u.x),
                 mix(hash(i + vec2(0.0, 1.0)), hash(i + vec2(1.0, 1.0)), u.x), u.y);
    }

    float fbm(vec2 p) {
      float v = 0.0;
      float a = 0.5;
      for (int i = 0; i < 4; i++) {
        v += a * noise(p);
        p *= 2.2;
        a *= 0.5;
      }
      return v;
    }

    void main() {
      vec3 direction = normalize(vWorldPosition);
      // Bajamos el gradiente de aparición para que las nubes se vean "en el horizonte" y no solo en el techo
      float horizonFade = smoothstep(-0.1, 0.1, direction.y);
      if (horizonFade <= 0.0) discard;

      vec2 skyUV = direction.xz / (direction.y + 0.4) * 2.5; 
      skyUV += uTime * 0.015;

      float n = fbm(skyUV);
      // Nubes más pobladas y con más presencia
      float cloudMask = smoothstep(0.35, 0.6, n);
      
      float sunLight = max(0.0, dot(direction, normalize(uSunPos)));
      vec3 finalColor = mix(uCloudColor * 0.9, uCloudColor * 1.15, pow(sunLight, 3.0));
      
      gl_FragColor = vec4(finalColor, cloudMask * horizonFade * uOpacity);
    }
  `,
});
clouds = new THREE.Mesh(cloudGeometry, cloudMaterial);
scene.add(clouds);

const sun = new THREE.Vector3();
const skyUniforms = sky.material.uniforms;
skyUniforms["turbidity"].value = 2.5; // Aire más claro
skyUniforms["rayleigh"].value = 1.2; // Cielo azul brillante y natural
skyUniforms["mieCoefficient"].value = 0.005;
skyUniforms["mieDirectionalG"].value = 0.8;

const pmremGenerator = new THREE.PMREMGenerator(renderer);

function updateSun() {
  const elevation = 15; // Sol bajo en el horizonte para verlo bien desde el nivel de piso
  const azimuth = 45; // Giramos el sol para que esté frente a la cámara inicial (que está en X+, Z+)

  const phi = THREE.MathUtils.degToRad(90 - elevation);
  const theta = THREE.MathUtils.degToRad(azimuth);

  sun.setFromSphericalCoords(1, phi, theta);
  sky.material.uniforms["sunPosition"].value.copy(sun);
  clouds.material.uniforms.uSunPos.value.copy(sun);

  // Mantenemos la luz direccional en una posición alta y lejana para iluminar la maqueta colosal
  dirLight.position.set(1000, 2000, 500);

  if (scene.environment) {
    scene.environment.dispose();
  }
  scene.environment = null;
}
updateSun();

// Helpers eliminados para un look más limpio (daylight)

const renderScene = new RenderPass(scene, camera);
// Eliminamos el Composer si no hay efectos activos para ganar FPS directos
// const composer = new EffectComposer(renderer);
// composer.addPass(renderScene);
// const outputPass = new OutputPass();
// composer.addPass(outputPass);

// scene.background = new THREE.Color('red');

// Carga del GLTF
const loader = new GLTFLoader();
const dracoLoader = new DRACOLoader();
dracoLoader.setDecoderPath(
  "https://www.gstatic.com/draco/versioned/decoders/1.5.7/",
);
loader.setDRACOLoader(dracoLoader);

const loadGLTF = (url) =>
  new Promise((resolve, reject) =>
    loader.load(url, resolve, undefined, reject),
  );

const initModels = async () => {
  try {
    const [mainGltf, tree1, tree2, tree3] = await Promise.all([
      loadGLTF("public/japonutopia_texturas.glb"),
      loadGLTF("public/tree_detailed_dark.glb"),
      loadGLTF("public/tree_fat_darkh.glb"),
      loadGLTF("public/tree_pineGroundA.glb"),
    ]);

    // Extraer mágicamente el material (hojas verdes) de los árboles
    let treeGrassMaterial = null;
    const checkMat = (m) => {
      if (treeGrassMaterial) return;
      // Si tiene más verde que rojo (con margen), asumimos que es el pasto/hojas
      if (m.color && m.color.g > m.color.r * 1.1) {
        treeGrassMaterial = m.clone();
      }
    };

    tree1.scene.traverse((c) => {
      if (c.isMesh) {
        if (Array.isArray(c.material)) c.material.forEach(checkMat);
        else checkMat(c.material);
      }
    });

    // Fallback: tomar el primer material que aparezca en el árbol si todo falla
    if (!treeGrassMaterial) {
      tree1.scene.traverse((c) => {
        if (c.isMesh && !treeGrassMaterial) {
          treeGrassMaterial = Array.isArray(c.material)
            ? c.material[0].clone()
            : c.material.clone();
        }
      });
    }

    model = mainGltf.scene;

    // 1. Escalar primero (para que la caja de colisión sea del tamaño real final)
    const targetSize = 1500;
    const initialBox = new THREE.Box3().setFromObject(model);
    const initialSize = initialBox.getSize(new THREE.Vector3());
    const maxDim = Math.max(initialSize.x, initialSize.y, initialSize.z);
    const scaleFactor = targetSize / (maxDim || 1);
    model.scale.set(scaleFactor, scaleFactor, scaleFactor);

    // 2. Calcular la nueva caja de colisión ya escalada
    model.updateMatrixWorld(true);
    const box = new THREE.Box3().setFromObject(model);
    const center = box.getCenter(new THREE.Vector3());

    // 3. Alinear: Centro en X/Z, pero Base (min.y) estrictamente en 0
    model.position.x -= center.x;
    model.position.z -= center.z;
    model.position.y -= box.min.y;

    // ACTUALIZACIÓN CRÍTICA: Forzar que todas las posiciones de los hijos se recalculen tras mover el modelo
    model.updateMatrixWorld(true);

    // Bajar ligeramente el terreno y canchas para evitar interferencia (z-fighting) con TODOS los edificios
    model.traverse((child) => {
      const name = child.name ? child.name.toLowerCase() : "";
      if (!child.userData.raisedFixed) {
        if (name.includes("terreno")) {
          child.position.y -= 1.5;
          child.updateMatrix();
          child.traverse((c) => (c.userData.raisedFixed = true));
        } else if (name.includes("cancha")) {
          child.position.y -= 0.1; // Subimos las canchas (antes -1.5)
          child.updateMatrix();
          child.traverse((c) => (c.userData.raisedFixed = true));
        }
      }
    });

    // Preparar nodos arquitectónicos para la Vista Explosionada interactiva
    model.traverse((child) => {
      const name = (child.name || "").toLowerCase().trim();

      // Buscamos el nombre del padre, del abuelo, etc., hasta encontrar una categoría principal y capturar nombres intermedios
      let category = "";
      let fullPathName = ""; // Acumulamos nombres para detectar materiales
      let current = child;

      while (current) {
        const cName = (current.name || "").toLowerCase();
        fullPathName += " " + cName;

        // PRIORIDAD ABSOLUTA: Si encontramos un edificio/zona, mandamos esa y paramos (break)
        if (cName.includes("gimnasio") || cName.includes("gym")) {
          category = "gym";
          break;
        }
        if (cName.includes("alberca") || cName.includes("pool")) {
          category = "pool";
          break;
        }
        if (
          cName.includes("cancha") ||
          cName.includes("tenis") ||
          cName.includes("padel")
        ) {
          category = "canchas";
          break;
        }
        if (cName.includes("administracion") || cName.includes("admin")) {
          category = "admin";
          break;
        }

        // PRIORIDAD SECUNDARIA: Si es estructura, la anotamos pero SEGUIMOS buscando arriba
        // por si pertenece a un edificio (ej: viga dentro del gym)
        if (
          cName.includes("estructura") ||
          cName.includes("structure") ||
          cName.includes("techo") ||
          cName.includes("roof")
        ) {
          if (!category) category = "estructura";
          // NO hacemos break aquí para permitir encontrar el padre
        }

        current = current.parent;
      }

      // Si ya tiene una configuración, no hacemos nada
      if (child.userData.explodeConfigured) return;

      // --- APLICACIÓN POR CATEGORÍA ESTRICTA ---

      // 1. ESTRUCTURA (Techo y Esqueleto) -> Sube 40
      if (category === "estructura") {
        if (fullPathName.includes("lamina") || fullPathName.includes("acero")) {
          child.userData.explodeOffset = 40;
        }
      }
      // 2. GIMNASIO -> Sube 20 (Vista explosionada nivel 1)
      else if (category === "gym") {
        child.userData.explodeOffset = 20;
      }
      // 3. ALBERCA, CANCHAS, ADMINISTRACIÓN Y TERRENO -> Se quedan en 0
      else if (
        category === "pool" ||
        category === "canchas" ||
        category === "admin" ||
        category === "terreno"
      ) {
        child.userData.explodeOffset = 0;
      }

      // Si asignamos un offset (incluyendo 0), marcamos a este nodo
      // y a todos sus hijos para que no dupliquen el movimiento
      if (child.userData.explodeOffset !== undefined) {
        child.userData.originalY = child.position.y;
        child.traverse((c) => (c.userData.explodeConfigured = true));
      }
    });

    // Clasificar capas y respaldar materiales mediante herencia (cascade)
    model.traverse((child) => {
      // Obtener todos los nombres en la jerarquía hacia arriba para una detección robusta
      let fullName = "";
      let p = child;
      while (p) {
        fullName += (p.name || "").toLowerCase() + " ";
        p = p.parent;
      }

      // Asignación de roles basada en el nombre completo (jerarquía)
      // Prioridad 1: Techumbre (para permitir vista de rayos X)
      if (
        fullName.includes("techo") ||
        fullName.includes("roof") ||
        fullName.includes("techumbre") ||
        fullName.includes("lamina") ||
        fullName.includes("cubierta")
      ) {
        child.userData.role = "roof";
        child.userData.highlightColor = new THREE.Color(0x64748b);
      }
      // Prioridad 2: Zonas de interés
      else if (fullName.includes("gym") || fullName.includes("gimnasio")) {
        child.userData.role = "gym";
        child.userData.highlightColor = new THREE.Color(0x3b82f6);
      } else if (
        fullName.includes("alberca") ||
        fullName.includes("pool") ||
        fullName.includes("acuatico") ||
        fullName.includes("agua") ||
        fullName.includes("water") ||
        fullName.includes("piscina")
      ) {
        child.userData.role = "pool";
        child.userData.highlightColor = new THREE.Color(0x3b82f6);
      } else if (
        fullName.includes("cancha") ||
        fullName.includes("tenis") ||
        fullName.includes("padel") ||
        fullName.includes("basket") ||
        fullName.includes("basquet") ||
        fullName.includes("pista") ||
        fullName.includes("futbol") ||
        fullName.includes("soccer") ||
        fullName.includes("voley") ||
        fullName.includes("voleibol")
      ) {
        // FILTRO DE SEGURIDAD: Solo aceptar si la malla está pegada al suelo (evita basura flotante del GLB)
        // Usamos la posición local relativa al modelo centrado
        if (Math.abs(child.position.y) < 10) { 
           child.userData.role = "canchas";
           child.userData.highlightColor = new THREE.Color(0xfbbf24);
        }
      } else if (fullName.includes("administracion") || fullName.includes("admin")) {
        child.userData.role = "admin";
        child.userData.highlightColor = new THREE.Color(0x94a3b8);
      }
      // Prioridad 3: Estructura general (Rayos X)
      else if (
        fullName.includes("muro") ||
        fullName.includes("pared") ||
        fullName.includes("columna") ||
        fullName.includes("estructura") ||
        fullName.includes("acero") ||
        fullName.includes("viga") ||
        fullName.includes("e_") // Prefijo del nuevo modelo
      ) {
        child.userData.role = "structure";
        child.userData.highlightColor = new THREE.Color(0x94a3b8);
      } else if (fullName.includes("terreno") || fullName.includes("t_")) {
        child.userData.role = "terreno";
        child.userData.highlightColor = new THREE.Color(0x10b981);
      }

      if (child.isMesh) {
        // Localizar si esta pieza es parte del "Terreno"
        let isGrass = fullName.includes("terreno");

        let originalMat = child.material;

        // Si es el terreno y queremos usar el material del bosque (opcional), 
        // pero por ahora priorizamos las texturas del nuevo modelo.
        if (isGrass && treeGrassMaterial) {
          // Si prefieres la textura del GLB, comenta estas líneas:
          // originalMat = treeGrassMaterial.clone();
        }

        // Clonamos material si el objeto tiene un rol (Gym, Pool, Estructura, etc.)
        // para que sus cambios de opacidad/brillo sean independientes.
        let workingMat = originalMat;
        if (child.userData.role) {
          workingMat = originalMat.clone();
        }
        
        // --- EFECTO GHOST/X-RAY PARA TECHOS Y ESTRUCTURA ---
        if (
          child.userData.role === "roof" ||
          child.userData.role === "structure"
        ) {
          workingMat.transparent = true;
          workingMat.opacity = 0.35;
          workingMat.depthWrite = false; 
        } else {
          workingMat.transparent = false;
          workingMat.opacity = 1.0;
          workingMat.depthWrite = true;
        }

        // Guardamos el estado final (con o sin X-Ray) como el "original" para restauraciones
        child.userData.originalMaterial = workingMat.clone();
        child.material = workingMat;


        if (!child.userData.role) child.userData.role = "structure";

        // Ajustar colores de highlight finales según rol asignado
        if (child.userData.role === "gym")
          child.userData.highlightColor = new THREE.Color(0xf43f5e);
        else if (child.userData.role === "pool")
          child.userData.highlightColor = new THREE.Color(0x0ea5e9);
        else if (child.userData.role === "roof")
          child.userData.highlightColor = new THREE.Color(0x10b981);
        else if (child.userData.role === "canchas")
          child.userData.highlightColor = new THREE.Color(0xfbbf24);
      }
    });

    // --- CREACIÓN DE HITBOX INVISIBLE PARA LAS CANCHAS ---
    const canchaBox = new THREE.Box3();
    let bestCanchaMesh = null;
    let maxVertices = -1;

    model.traverse((c) => {
      if (c.isMesh && c.userData.role === "canchas") {
        // Encontrar la malla real (la que tiene más geometría) ignorando basura del GLB
        const vertexCount = c.geometry.attributes.position.count;
        if (vertexCount > maxVertices) {
           maxVertices = vertexCount;
           bestCanchaMesh = c;
        }
      }
    });

    if (bestCanchaMesh) {
      canchaBox.setFromObject(bestCanchaMesh);
      const size = new THREE.Vector3();
      const center = new THREE.Vector3();
      canchaBox.getSize(size);
      canchaBox.getCenter(center);

      // Creamos un cubo invisible un poco más alto para facilitar el clic
      const hitBoxGeo = new THREE.BoxGeometry(size.x, size.y + 10, size.z);
      const hitBoxMat = new THREE.MeshBasicMaterial({ 
        visible: false, // Invisible pero detectable por Raycaster
      });
      const hitBox = new THREE.Mesh(hitBoxGeo, hitBoxMat);
      hitBox.position.copy(center);
      hitBox.position.y += 5; // Centrar la altura extra
      
      // Le damos el rol para que dispare el evento
      hitBox.userData.role = "canchas";
      hitBox.userData.isHitBox = true; // Flag para ignorar en efectos visuales
      
      model.add(hitBox);
    }

    scene.add(model);

    // Generar bosque usando InstancedMesh sobre Terrenos
    model.updateMatrixWorld(true);

    // Zonas prohibidas para los árboles
    const forbiddenBoxes = [];
    model.traverse((child) => {
      const name = child.name ? child.name.toLowerCase() : "";
      if (
        !child.userData.boxAdded &&
        (name.includes("gimnasio") ||
          name.includes("gym") ||
          name.includes("alberca") ||
          name.includes("pool") ||
          name.includes("estructura") ||
          name.includes("administracion") ||
          name.includes("cancha") ||
          name.includes("techo"))
      ) {
        const box = new THREE.Box3().setFromObject(child);
        // Extender la caja hacia arriba/abajo al infinito para medir escudo en 2D (footprint XZ)
        box.min.y = -Infinity;
        box.max.y = Infinity;
        // Dar un margen de seguridad de 3.5 unidades para que las ramas no invadan
        box.min.x -= 3.5;
        box.max.x += 3.5;
        box.min.z -= 3.5;
        box.max.z += 3.5;
        forbiddenBoxes.push(box);

        // Evitar duplicar las cajas para sub-hijos
        child.traverse((c) => (c.userData.boxAdded = true));
      }
    });

    const terrainMeshes = [];
    model.traverse((child) => {
      if (child.isMesh) {
        let p = child;
        let isTerreno = false;

        while (p) {
          const name = p.name ? p.name.toLowerCase() : "";
          // Evitar atrapar cosas que digan "pasto" de las canchas, enfocarse SOLO en Terreno
          if (name.includes("terreno")) {
            isTerreno = true;
            break;
          }
          p = p.parent;
        }

        if (isTerreno) {
          terrainMeshes.push(child);
        }
      }
    });

    if (terrainMeshes.length > 0) {
      const samplers = terrainMeshes.map((tm) =>
        new MeshSurfaceSampler(tm).build(),
      );
      const trees = [tree1, tree2, tree3];
      const numTreesPerType = 300; // Total 900 árboles

      trees.forEach((treeGltf, treeIdx) => {
        const treeMeshes = [];
        treeGltf.scene.traverse((c) => {
          if (c.isMesh) treeMeshes.push(c);
        });

        const instancedMeshes = treeMeshes.map((tm) => {
          const mat = tm.material.clone();
          mat.transparent = false; // Árboles opacos por defecto
          const im = new THREE.InstancedMesh(tm.geometry, mat, numTreesPerType);

          im.userData.role = "structure";
          im.userData.originalMaterial = mat;
          im.userData.highlightColor = new THREE.Color(0x10b981);

          im.material = mat.clone();
          im.castShadow = true;
          im.receiveShadow = true;

          model.add(im);
          return im;
        });

        const dummy = new THREE.Object3D();
        const _pos = new THREE.Vector3();
        const _normal = new THREE.Vector3();

        for (let i = 0; i < numTreesPerType; i++) {
          const sIdx = Math.floor(Math.random() * samplers.length);

          let validPos = false;
          // Intentar hasta 30 veces encontrar un punto natural vivo que NO invada las cajas prohibidas
          for (let attempt = 0; attempt < 30; attempt++) {
            samplers[sIdx].sample(_pos, _normal);
            // Computar punto a nivel de mundo real
            terrainMeshes[sIdx].localToWorld(_pos);

            let inForbidden = false;
            for (let b of forbiddenBoxes) {
              if (b.containsPoint(_pos)) {
                inForbidden = true;
                break;
              }
            }

            if (!inForbidden) {
              validPos = true;
              break; // Encontramos piso vacío y seguro
            }
          }

          model.worldToLocal(_pos);
          dummy.position.copy(_pos);
          dummy.rotation.y = Math.random() * Math.PI * 2;

          if (!validPos) {
            // Si estaba demasiado atascado y no cabía ni tras 30 intentos, abortamos la semilla (lo hacemos invisible)
            dummy.scale.set(0, 0, 0);
          } else {
            // Escalar a un tamaño base de 6.0
            let s = (Math.random() * 0.5 + 0.8) * 6.0;

            // Aumentar los pinos (tree_pineGroundA = índice 2) un 70% más
            if (treeIdx === 2) {
              s *= 1.7;
            }

            dummy.scale.set(s, s, s);
          }

          dummy.updateMatrix();

          instancedMeshes.forEach((im) => {
            im.setMatrixAt(i, dummy.matrix);
          });
        }
        instancedMeshes.forEach((im) => {
          im.instanceMatrix.needsUpdate = true;
          im.computeBoundingSphere();
        });
      });
    }

    // Configurar cámara inicial en vista cenital con navegación tipo mapa.
    setTopDownCameraView(camera, controls, targetSize * 1.15);

    // Arrancar controles, ocultar loader y poblar gemelo
    initLayoutControls();
    initWeatherControls();
    initLightSensorSync();
    initPopulation();
    // NUEVO: Inicializar elementos espaciales solo cuando el modelo esté listo
    initSensors();
    
    // FORZAR ACTUALIZACIÓN: Esto asegura que los sensores recién añadidos 
    // tengan su posición de mundo lista para las etiquetas
    model.updateMatrixWorld(true); 
    
    initSpatialLabels();
    initAssets();
    initNightLighting();
    startLiveSync();

    document.getElementById("loader-overlay").classList.add("hidden");
  } catch (error) {
    console.error("❌ Loader Error:", error);
    const loaderText = document.querySelector(".loader-text");
    if (loaderText) loaderText.innerText = "Error cargando los modelos";
  }
};

initModels();

// --- LÓGICA DE INTERFAZ MÓVIL (TOGGLE SIDEBAR) ---
function initMobileToggles() {
  const btnMenu = document.getElementById("mobile-menu-toggle");
  const sidebar = document.querySelector(".sidebar");
  const btnCapas = document.querySelector('.nav-icon[title="Capas"]');

  const toggleSidebar = () => {
    sidebar.classList.toggle("mobile-visible");

    // Si abrimos el sidebar, cerramos otros paneles flotantes para no saturar
    if (sidebar.classList.contains("mobile-visible")) {
      document.getElementById("history-panel")?.classList.add("hidden");
      document.getElementById("info-card")?.classList.add("hidden");
    }
  };

  if (btnMenu) btnMenu.addEventListener("click", toggleSidebar);
  if (btnCapas) btnCapas.addEventListener("click", toggleSidebar);

  // Cerrar al hacer clic fuera del sidebar en móvil
  document.addEventListener("click", (e) => {
    if (
      window.innerWidth < 500 &&
      sidebar.classList.contains("mobile-visible") &&
      !sidebar.contains(e.target) &&
      !btnMenu.contains(e.target) &&
      !btnCapas.contains(e.target)
    ) {
      sidebar.classList.remove("mobile-visible");
    }
  });
}

document.addEventListener("DOMContentLoaded", () => {
  initMobileToggles();
});
function updateFocus(mode) {
  if (!model) return;

  model.traverse((child) => {
    if (!child.isMesh) return;

    const role = child.userData.role;
    const isSelected = role === mode;
    const isHitBox = child.userData.isHitBox;

    // Si es un hitbox, nunca le aplicamos efectos visuales
    if (isHitBox) {
      child.visible = true; // Debe estar visible para Raycaster
      return;
    }

    const originalMaterial = child.userData.originalMaterial;
    const materials = Array.isArray(child.material)
      ? child.material
      : [child.material];

    materials.forEach((mat) => {
      // SIEMPRE restaurar propiedades básicas primero
      mat.wireframe = false;

      if (mode === "all" || mode === "completo" || !mode) {
        // MODO NORMAL - Todo Visible respetando transparencias originales
        if (originalMaterial) {
          mat.color.copy(originalMaterial.color);
          mat.opacity = originalMaterial.opacity;
          mat.transparent = originalMaterial.transparent;
        } else {
          mat.opacity = 1.0;
          mat.transparent = false;
        }
        mat.emissive.setHex(0x000000);
        child.visible = true;
      } else {
        // MODO FILTRADO
        if (role === "roof" || role === "structure") {
          // X-RAY PERSISTENTE: Se mantienen visibles pero sutiles
          if (originalMaterial) {
            mat.color.copy(originalMaterial.color);
            mat.opacity = isSelected ? 1.0 : originalMaterial.opacity * 0.5;
            mat.transparent = true;
          }
          child.visible = true;
          mat.emissive.setHex(0x000000);
        } else if (isSelected) {
          // RESALTADO ACTIVO: Mantenemos la textura pero añadimos un brillo (glow)
          if (originalMaterial) mat.color.copy(originalMaterial.color);
          mat.emissive.copy(child.userData.highlightColor);
          mat.emissiveIntensity = 1.2; // Brillo neón elegante sobre la textura
          mat.opacity = 1.0;
          mat.transparent = false;
          child.visible = true;
        } else {
          // EL RESTO: Transparencia suave para contexto
          if (originalMaterial) mat.color.copy(originalMaterial.color);
          mat.emissive.setHex(0x000000);
          mat.transparent = true;
          mat.opacity = 0.3;
          child.visible = true;
        }
      }
    });
  });

  if (mode === "all" || mode === "completo" || !mode) {
    // Ya NO reseteamos la cámara al overview por defecto (libertad total de navegación)
    isCameraMoving = false;

    // Ocultar elementos de UI espacial si se desea limpiar la vista
    if (infoCard) infoCard.classList.add("hidden");
    if (floatingLabel) floatingLabel.classList.add("hidden");
  } else {
    focusCameraOnRole(mode);
  }

  // Después de actualizar el foco, refrescamos los heatmaps globales
  refreshHeatmaps();
}

// --- SISTEMA DE HEATMAPS (OCUPACIÓN VISUAL) ---
function refreshHeatmaps() {
  if (!model) return;

  const capacityLimits = { gym: 50, pool: 30, canchas: 20 };

  model.traverse((child) => {
    if (child.isMesh && child.userData.role && !child.userData.isHitBox) {
      const role = child.userData.role;
      if (capacityLimits[role]) {
        const count = digitalTwinData[role]?.current || 0;
        const limit = capacityLimits[role];
        const ratio = Math.min(1.0, count / limit);

        // Si la zona es el suelo (piso), aplicamos el color del Heatmap
        const isFloor = child.name.toLowerCase().includes("suelo") || 
                        child.name.toLowerCase().includes("pasto") ||
                        child.name.toLowerCase().includes("piso");

        if (isFloor) {
          const mat = child.material;
          // Gradiente: Verde (0.0) -> Amarillo (0.5) -> Rojo (1.0)
          const heatColor = new THREE.Color();
          if (ratio < 0.5) {
            heatColor.lerpColors(new THREE.Color(0x22c55e), new THREE.Color(0xeab308), ratio * 2);
          } else {
            heatColor.lerpColors(new THREE.Color(0xeab308), new THREE.Color(0xef4444), (ratio - 0.5) * 2);
          }

          // Aplicamos una emisión suave que represente el calor de ocupación
          mat.emissive.copy(heatColor);
          mat.emissiveIntensity = 0.3 + ratio * 0.7; // Más brillante si hay más gente
        }
      }
    }
  });
}

// --- MODO SHOWROOM (FIRST PERSON WALK) ---
let isWalkMode = false;
let moveForward = false;
let moveBackward = false;
let moveLeft = false;
let moveRight = false;
const walkSpeed = 50.0;
const velocity = new THREE.Vector3();
const direction = new THREE.Vector3();

function enterShowroom(role) {
  if (!model) return;
  applyStandardControls(camera, controls);
  
  // --- BUSCADOR DE SUELO INTELIGENTE ---
  let targetObj = null;
  let floorMesh = null;
  
  model.traverse(c => {
    if (c.isMesh && c.userData.role === role && !c.userData.isHitBox) {
      // Priorizamos mallas que parezcan suelos por su nombre
      const name = c.name.toLowerCase();
      if (name.includes('piso') || name.includes('suelo') || name.includes('pasto') || name.includes('floor')) {
          floorMesh = c;
      }
      if (!targetObj) targetObj = c; // Backup
    }
  });

  const finalTarget = floorMesh || targetObj;

  if (!finalTarget) {
    addFeedItem("⚠️ No se encontró el área para el Showroom", "warning");
    return;
  }

  addFeedItem(`🛰️ Teletransportando a zona ${role.toUpperCase()}...`, "info");
  
  const box = new THREE.Box3().setFromObject(finalTarget);
  const floorLevel = box.min.y;
  const center = new THREE.Vector3();
  box.getCenter(center);
  
  // Posición de inicio despejada
  const startPos = { 
    x: center.x + 2, 
    y: floorLevel + 1.7, 
    z: center.z + 2 
  };

  // Reset total de flags antes de mover
  moveForward = moveBackward = moveLeft = moveRight = false;
  velocity.set(0,0,0);

  window.gsap.to(camera.position, {
    x: startPos.x, y: startPos.y, z: startPos.z,
    duration: 1.5,
    ease: "power3.inOut",
    onStart: () => {
        controls.enabled = false;
    },
    onComplete: () => {
        isWalkMode = true;
        
        // Reset absoluto de rotación para evitar mirar al piso
        pitch = 0;
        yaw = Math.atan2(center.x - startPos.x, center.z - startPos.z);
        
        camera.rotation.order = 'YXZ';
        camera.rotation.set(pitch, yaw, 0);
        
        document.getElementById('btn-exit-walk').classList.remove('hidden');
        document.querySelector('.zone-navigation-dock').classList.add('hidden');
        
        renderer.domElement.requestPointerLock();
        addFeedItem("🎮 MODO SHOWROOM: Mouse para mirar, WASD para caminar", "success");
    }
  });
}

function exitShowroom() {
  isWalkMode = false;
  controls.enabled = true;
  controls.minDistance = 100;
  controls.maxDistance = 2000;
  controls.enablePan = true;
  document.getElementById('btn-exit-walk').classList.add('hidden');
  document.querySelector('.zone-navigation-dock').classList.remove('hidden');
  updateFocus('all');
}

// Event Listeners para WASD
window.addEventListener('keydown', (e) => {
    switch (e.code) {
        case 'KeyW': moveForward = true; break;
        case 'KeyS': moveBackward = true; break;
        case 'KeyA': moveLeft = true; break;
        case 'KeyD': moveRight = true; break;
        case 'Escape': if(isWalkMode) exitShowroom(); break;
    }
});
window.addEventListener('keyup', (e) => {
    switch (e.code) {
        case 'KeyW': moveForward = false; break;
        case 'KeyS': moveBackward = false; break;
        case 'KeyA': moveLeft = false; break;
        case 'KeyD': moveRight = false; break;
    }
});

function updateWalkMode(delta) {
    if (!isWalkMode) return;

    const speed = 15.0; // Velocidad de caminata humana
    const camDir = new THREE.Vector3();
    camera.getWorldDirection(camDir);
    camDir.y = 0;
    camDir.normalize();

    const sideDir = new THREE.Vector3().crossVectors(camDir, camera.up);

    if (moveForward) camera.position.addScaledVector(camDir, speed * delta);
    if (moveBackward) camera.position.addScaledVector(camDir, -speed * delta);
    if (moveLeft) camera.position.addScaledVector(sideDir, -speed * delta);
    if (moveRight) camera.position.addScaledVector(sideDir, speed * delta);
}

function focusCameraOnRole(role) {
  const box = new THREE.Box3();
  let found = false;

  model.traverse((child) => {
    if (child.isMesh && child.userData.role === role) {
      box.expandByObject(child);
      found = true;
    }
  });

  if (found) {
    applyStandardControls(camera, controls);
    const center = new THREE.Vector3();
    box.getCenter(center);
    const size = new THREE.Vector3();
    box.getSize(size);

    const maxDim = Math.max(size.x, size.y, size.z);

    // --- MEJORA DE POSICIÓN PREMIUM DE CÁMARA ---
    let cameraOffset = 1.35;
    let yFactor = 0.7;
    let lookAtOffset = new THREE.Vector3(0, 5, 0);

    if (role === "canchas") {
      cameraOffset = 1.1;
      yFactor = 0.45;
    }
    if (role === "gym") {
      cameraOffset = 1.25;
      yFactor = 0.75;
    }
    if (role === "pool") {
      cameraOffset = 1.35;
      yFactor = 0.6;
    }

    // Si es un sensor, usamos una distancia fija para no colisionar con el modelo
    const finalDist = role.includes("sensor")
      ? 85
      : Math.max(120, maxDim * cameraOffset);
    cameraTargetPos
      .copy(center)
      .add(new THREE.Vector3(finalDist, finalDist * yFactor, finalDist));
    controlsTargetPos.copy(center).add(lookAtOffset);

    // --- ANILLO DE SELECCIÓN ELIMINADO ---

    // Actualizar Etiqueta Flotante
    if (floatingLabel) {
      floatingLabel.dataset.target3d = JSON.stringify(center);
      const nameTag = floatingLabel.querySelector("#label-name");
      if (nameTag) {
        nameTag.innerText = digitalTwinData[role]?.title || role;
      }
      floatingLabel.classList.remove("hidden");
    }

    // Anclaje de la Info Card principal
    if (infoCard) infoCard.dataset.target3d = JSON.stringify(center);

    isCameraMoving = true;
  }
}
function initLayoutControls() {
  const btns = document.querySelectorAll(".layer-btn");

  btns.forEach((btn) => {
    btn.addEventListener("click", () => {
      btns.forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");

      const mode = btn.getAttribute("data-layer");
      updateFocus(mode);

      if (mode === "gym" || mode === "pool" || mode === "canchas") {
        showInfoCard(mode);
      } else {
        if (infoCard) infoCard.classList.add("hidden");
        if (floatingLabel) floatingLabel.classList.add("hidden");
      }
    });
  });

  // --- CONTROLES DE CÁMARA (PRESETS) ---
  const camBtns = document.querySelectorAll(".cam-preset");
  camBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      const preset = btn.dataset.preset;
      camBtns.forEach((b) => b.classList.remove("active"));

      if (preset !== "pano") isPanoActive = false;

      if (preset === "drone") {
        btn.classList.add("active");
        applyTopDownControls(camera, controls);
        cameraTargetPos.set(0, 1500, 0);
        controlsTargetPos.set(0, 0, 0);
        isCameraMoving = true;
      } else if (preset === "walk") {
        btn.classList.add("active");
        applyStandardControls(camera, controls);
        cameraTargetPos.set(400, 10, 400);
        controlsTargetPos.set(0, 10, 0);
        isCameraMoving = true;
      } else if (preset === "pano") {
        applyStandardControls(camera, controls);
        isPanoActive = !isPanoActive;
        if (isPanoActive) {
          btn.classList.add("active");
          addFeedItem("Iniciando rotación panorámica de inspección", "success");
        } else {
          btn.classList.remove("active");
        }
      }
    });
  });

  // --- LÓGICA DEL NUEVO DASHBOARD MAESTRO ---
  const btnDash = document.getElementById("btn-dashboard");
  const dashOverlay = document.getElementById("extended-dashboard");
  const closeDash = document.getElementById("close-dashboard");

  const btnNewRes = document.getElementById("btn-new-reservation");
  const btnViewPanel = document.getElementById("btn-view-panel");
  const resModal = document.getElementById("res-modal-overlay");
  const closeResModal = document.getElementById("close-res-modal");
  const resForm = document.getElementById("reservation-form");

  if (btnDash && dashOverlay) {
    btnDash.addEventListener("click", () => {
      dashOverlay.classList.remove("hidden");
      updateDashboardData();
      loadReservationsFromDB(); // <--- SINCRONIZACIÓN CON MYSQL
      addFeedItem("Abriendo Dashboard de Control Maestro", "info");
    });

    // Cerrar al hacer clic fuera o en X
    dashOverlay.addEventListener("click", (e) => {
      if (e.target === dashOverlay || e.target.closest("#close-dashboard")) {
        dashOverlay.classList.add("hidden");
      }
    });

    // --- NUEVO: EVENTOS PARA EL MODAL DE RESERVA ---
    if (btnNewRes && resModal) {
      btnNewRes.addEventListener("click", (e) => {
        e.stopPropagation();
        resModal.classList.add("active");
        addFeedItem("Abriendo formulario de reserva segura", "warning");
      });

      resModal.addEventListener("click", (e) => {
        if (e.target === resModal || e.target.closest("#close-res-modal")) {
          resModal.classList.remove("active");
        }
      });
    }

    if (btnViewPanel) {
      btnViewPanel.addEventListener("click", () => {
        window.open(`${API_BASE_URL}/panel`, "_blank");
      });
    }

    // --- ENVIAR RESERVA A LARAVEL ---
    if (resForm) {
      resForm.addEventListener("submit", (e) => {
        e.preventDefault();
        const formData = new FormData(resForm);
        const data = Object.fromEntries(formData.entries());

        addFeedItem("Enviando datos al servidor Laravel...", "info");

        fetch(`${API_BASE_URL}/api/reservations`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(data),
        })
          .then((res) => res.json())
          .then((result) => {
            if (result.status === "success") {
              addFeedItem(`¡Éxito! ${result.message}`, "success");
              resModal.classList.remove("active");
              resForm.reset();
              loadReservationsFromDB(); // Recargar historial
            } else {
              addFeedItem("Error al procesar reserva", "danger");
            }
          })
          .catch((err) => {
            console.error("Save Error:", err);
            addFeedItem("Error de conexión con el Backend Laravel", "danger");
          });
      });
    }
  }
}

// --- NUEVAS FUNCIONES DE CONTROL DE PANELES (RESTAURADAS) ---
function openPanel(panelId) {
  closeAllPanels();
  
  if (panelId === 'dashboard') {
    const dash = document.getElementById("extended-dashboard");
    if (dash) {
      dash.classList.remove("hidden");
      updateDashboardData();
      loadReservationsFromDB();
      addFeedItem("Abriendo Dashboard de Control Maestro", "info");
    }
  } else if (panelId === 'history') {
    const history = document.getElementById("history-panel");
    const btnHist = document.getElementById("btn-history");
    if (history) {
      history.classList.remove("hidden");
      if (btnHist) btnHist.classList.add("history-active");
      updateHistoryUI(parseInt(document.getElementById("history-slider")?.value || 0));
      addFeedItem("Abriendo Línea de Tiempo Histórica", "info");
    }
  }
}

function closeAllPanels() {
  const dash = document.getElementById("extended-dashboard");
  const history = document.getElementById("history-panel");
  const info = document.getElementById("info-card");
  const btnHist = document.getElementById("btn-history");

  if (dash) dash.classList.add("hidden");
  if (history) history.classList.add("hidden");
  if (info) info.classList.add("hidden");
  if (btnHist) btnHist.classList.remove("history-active");
}

function changeWeather(type) {
  currentWeatherType = type;
  if (rain) {
    rain.material.opacity = type === 'rain' ? 0.6 : 0;
  }
  // Ajustar atmósfera según el tipo
  updateAtmosphere();
  addFeedItem(`Clima cambiado a: ${type.toUpperCase()}`, "success");
}

// Exportar a window
window.openPanel = openPanel;
window.closeAllPanels = closeAllPanels;
window.changeWeather = changeWeather;
window.updateFocus = updateFocus;

// Sincroniza dbCounts con la DB en segundo plano (sin depender del panel)
function syncDBCounts() {
  if (isHistoryMode) return; // NO sincronizar si estamos en el pasado/futuro
  fetch(`${API_BASE_URL}/api/reservations/live?t=${Date.now()}`, {
    cache: "no-store",
    headers: { Accept: "application/json" },
  })
    .then((res) => res.json())
    .then((data) => {
      const totals = data.totals || { gym: 0, pool: 0, canchas: 0 };
      const statuses = data.zone_status || {};
      
      dbCounts.gym = totals.gym ?? 0;
      dbCounts.pool = totals.pool ?? 0;
      dbCounts.canchas = totals.canchas ?? 0;
      
      // Guardar solo reservaciones activas para coloreado y población en vivo
      lastActiveReservations = data.active_reservations || [];
      console.info("[LIVE SYNC]", {
        serverTime: data.server_time,
        totals,
        activeReservations: lastActiveReservations.length,
      });
      
      applyDBCountsToWorld(statuses);
    })
    .catch((err) => {
        console.error("[DB Sync Error]", err);
        addFeedItem("⚠️ Error de sincronización con la base de datos", "danger");
    });
}

// Aplica dbCounts al mundo 3D: actualiza capacidad, spawns y panel de monitoreo
function applyDBCountsToWorld(zoneStatuses = null) {
  if (zoneStatuses) lastZoneStatuses = zoneStatuses;
  const statuses = lastZoneStatuses;
  
  const capacityLimits = { gym: 50, pool: 30, canchas: 20 };
  let totalPeople = 0;
  let totalTemp = 0;
  let zoneCount = 0;

  ["gym", "pool", "canchas"].forEach((role) => {
    const count = dbCounts[role] ?? 0;
    totalPeople += count;

    // Actualizar digitalTwinData
    const data = digitalTwinData[role];
    if (data) {
      data.current = count;
      
      const isClosed = statuses[role] === 'closed';
      if (isClosed) {
        data.status = "CERRADO POR HORARIO";
        data.statusClass = "status-danger";
      } else {
        data.status = "Operativo";
        data.statusClass = "status-good";
      }

      totalTemp += parseFloat(data.temp) || 22;
      zoneCount++;

      // Refrescar UI si la zona está abierta en el panel de detalles
      if (currentSelectedRole === role) {
          const statusEl = document.getElementById("area-status");
          const statusBox = document.getElementById("status-box");
          if (statusEl) statusEl.innerText = data.status;
          if (statusBox) {
              statusBox.className = "status-box-premium " + (data.statusClass || "status-good");
          }
      }
    }
  });

  if (!model) return; // <--- El resto requiere el modelo 3D cargado

  ["gym", "pool", "canchas"].forEach((role) => {
    const isClosed = statuses[role] === 'closed';
    const count = dbCounts[role] ?? 0;

    // --- EFECTO VISUAL DE ÁREA CERRADA (ROJO HOLOGRÁFICO) ---
    model.traverse((child) => {
        if (child.isMesh && child.userData.role === role && !child.userData.isHitBox) {
            const materials = Array.isArray(child.material) ? child.material : [child.material];
            materials.forEach(mat => {
                if (isClosed) {
                    mat.emissive.setHex(0xff0000);
                    mat.emissiveIntensity = 2.0 + Math.sin(Date.now() * 0.008) * 1.0; // Pulso más fuerte y rápido
                    mat.transparent = true;
                    mat.opacity = 0.45; // Más fantasmal
                } else {
                    // Restaurar material original si se abre
                    if (!child.userData.isSelectedInFocus) {
                        mat.emissive.setHex(0x000000);
                        mat.opacity = child.userData.originalMaterial ? child.userData.originalMaterial.opacity : 1.0;
                    }
                }
            });
        }
    });

    // Spawn personas en el modelo 3D proporcional al aforo (0 si está cerrado)
    const limit = capacityLimits[role] || 50;
    const isClosedRole = statuses[role] === 'closed';
    const scaled = isClosedRole ? 0 : Math.min(count, limit);
    
    // Filtrar reservaciones específicas de este rol para el coloreado
    const roleRes = (lastActiveReservations || []).filter(r => r.zone === role);
    
    spawnPeopleInRole(role, scaled, roleRes);
  });

  // Refrescar heatmaps visuales en los suelos (solo si no están cerrados)
  refreshHeatmaps();
  
  // Actualizar etiquetas espaciales
  updateSpatialLabelsStatus(statuses);

  // --- Actualizar panel Monitoreo (barra CAPACIDAD y TOTAL) ---
  const capacityEl = document.getElementById("txt-capacity");
  const mobCapEl = document.getElementById("txt-capacity-mob");
  const totalCapacity = 50 + 30 + 20; // gym+pool+canchas
  const capPct = Math.min(100, Math.floor((totalPeople / totalCapacity) * 100));
  const capColor =
    capPct < 50
      ? "var(--success-color)"
      : capPct < 80
        ? "var(--warning-color)"
        : "var(--danger-color)";

  if (capacityEl) {
    capacityEl.innerText = `${capPct}%`;
    capacityEl.style.color = capColor;
  }
  if (mobCapEl) {
    mobCapEl.innerText = `${capPct}%`;
    mobCapEl.style.color = capColor;
  }

  // Temperatura promedio
  const tempAvgEl = document.getElementById("txt-temp-avg");
  const mobTemp = document.getElementById("txt-temp-avg-mob");
  if (tempAvgEl && zoneCount > 0) {
    const avg = (totalTemp / zoneCount).toFixed(1);
    tempAvgEl.innerText = `${avg}°C`;
    if (mobTemp) mobTemp.innerText = `${avg}°C`;
  }

  // Total personas visible
  const totalEl = document.getElementById("txt-total-people");
  if (totalEl) totalEl.innerText = totalPeople;

  // Desglose por zona en el panel Monitoreo
  const gymEl = document.getElementById("txt-pop-gym");
  const poolEl = document.getElementById("txt-pop-pool");
  const canchasEl = document.getElementById("txt-pop-canchas");
  if (gymEl) gymEl.innerText = dbCounts.gym ?? 0;
  if (poolEl) poolEl.innerText = dbCounts.pool ?? 0;
  if (canchasEl) canchasEl.innerText = dbCounts.canchas ?? 0;

  updateDashboardData();
}

function startLiveSync() {
  syncDBCounts();
  if (liveSyncTimer) clearInterval(liveSyncTimer);
  liveSyncTimer = setInterval(syncDBCounts, 10000);
}

// Sincronizar al cargar y cada 10 segundos
document.addEventListener("DOMContentLoaded", () => {
  startLiveSync();
});

function loadReservationsFromDB() {
  const historyList = document.getElementById("res-history-list");
  syncDBCounts(); // siempre re-sincroniza al abrir el panel
  if (!historyList) return;

  let counts = { gym: 0, pool: 0, canchas: 0 };
  const now = new Date();

  fetch(`${API_BASE_URL}/api/reservations/live`)
    .then((res) => res.json())
    .then((data) => {
      // El endpoint /live devuelve { date, reservations, totals, grand_total }
      const reservations = Array.isArray(data) ? data : data.reservations || [];
      const activeReservations = data.active_reservations || [];
      const totals = data.totals || null;
      lastActiveReservations = activeReservations;

      if (reservations.length > 0) {
        historyList.innerHTML = "";
        reservations.forEach((res) => {
          const resDate = new Date(res.reservation_date);
          const item = document.createElement("div");
          item.className = "mini-item";
          const dateStr = resDate.toLocaleString([], {
            month: "short",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit",
          });
          item.innerHTML = `<strong>${res.zone.toUpperCase()}</strong> · ${res.name || "Invitado"} · ${dateStr}`;
          historyList.appendChild(item);
        });

        // Usar totales pre-calculados de la DB (toda la jornada de hoy)
        if (totals) {
          counts = totals;
        } else {
          // Fallback: calcular manualmente con ventana de ±3 horas
          reservations.forEach((res) => {
            const diffMins =
              Math.abs(now - new Date(res.reservation_date)) / 60000;
            if (diffMins <= 180 && counts[res.zone] !== undefined) {
              counts[res.zone] += parseInt(res.guests) || 1;
            }
          });
        }

        // Guardar en dbCounts (fuente de verdad) — el motor 3D lo lee en cada tick
        if (totals) {
          dbCounts.gym = totals.gym ?? 0;
          dbCounts.pool = totals.pool ?? 0;
          dbCounts.canchas = totals.canchas ?? 0;
        } else {
          // Fallback: calcular con ventana ±3h
          reservations.forEach((res) => {
            const diffMins =
              Math.abs(now - new Date(res.reservation_date)) / 60000;
            if (diffMins <= 180 && dbCounts[res.zone] !== undefined) {
              if (dbCounts[res.zone] === null) dbCounts[res.zone] = 0;
              dbCounts[res.zone] += parseInt(res.guests) || 1;
            }
          });
        }

        const total =
          (dbCounts.gym || 0) + (dbCounts.pool || 0) + (dbCounts.canchas || 0);
        addFeedItem(
          `🏟️ DB SYNC — Hoy: ${total} pax reservados (GYM:${dbCounts.gym} POOL:${dbCounts.pool} CANCHAS:${dbCounts.canchas})`,
          "info",
        );
        applyDBCountsToWorld(data.zone_status || null); // ← actualiza capacidad + spawns 3D + estados
        updateDashboardData();
      } else {
        historyList.innerHTML =
          '<div class="mini-item">Sin historial en base de datos.</div>';
      }
    })
    .catch((err) => {
      console.error("DB Error:", err);
      historyList.innerHTML =
        '<div class="mini-item" style="color:#ef4444">Error al conectar con la DB de Laragon.</div>';
    });
}

// --- NUEVA LÓGICA DE DASHBOARD "INCREÍBLE" ---
function updateDashboardData() {
  let total = 0;
  let totalTemp = 0;
  let count = 0;

  Object.keys(digitalTwinData).forEach((key) => {
    const val = parseInt(digitalTwinData[key].current) || 0;
    total += val;
    const temp = parseFloat(digitalTwinData[key].temp) || 0;
    totalTemp += temp;
    count++;

    const bar = document.getElementById(`bar-${key}`);
    const valTxt = document.getElementById(`val-${key}`);
    const limits = { gym: 50, pool: 30, canchas: 20 };
    const limit = limits[key] || 100;
    if (bar) bar.style.width = `${Math.min((val / limit) * 100, 100)}%`;
    if (valTxt) valTxt.innerText = `${val} / ${limit} Pax`;
  });

  const avgTemp = (totalTemp / count).toFixed(1);
  const capacity = Math.min(100, Math.floor((total / 100) * 100));

  // Animación de conteo simple (Dashboard)
  animateValue("dash-total-people", 0, total, 1000);
  const tempEl = document.getElementById("dash-avg-temp");
  if (tempEl) tempEl.innerText = `${avgTemp}°C`;

  // --- SINCRONIZACIÓN GLOBAL DE PANELES (SIDEBAR + HEADER) ---
  const capSide = document.getElementById("txt-capacity");
  const capMob = document.getElementById("txt-capacity-mob");
  const tempSide = document.getElementById("txt-temp-avg");
  const tempMob = document.getElementById("txt-temp-avg-mob");

  const capColor =
    capacity < 50
      ? "var(--success-color)"
      : capacity < 80
        ? "var(--warning-color)"
        : "var(--danger-color)";

  if (capSide) {
    capSide.innerText = `${capacity}%`;
    capSide.style.color = capColor;
  }
  if (capMob) {
    capMob.innerText = `${capacity}%`;
    capMob.style.color = capColor;
  }
  if (tempSide) tempSide.innerText = `${avgTemp}°C`;
  if (tempMob) tempMob.innerText = `${avgTemp}°C`;

  updateClock();
}

function updateClock() {
  const timeEl = document.getElementById("dash-time-val");
  const dateEl = document.getElementById("dash-date-val");
  const txtHour = document.getElementById("txt-hour");
  const mobHour = document.getElementById("txt-hour-mob");

  const now = new Date();
  const timeStr = now.toLocaleTimeString([], {
    hour12: false,
    hour: "2-digit",
    minute: "2-digit",
  });

  if (timeEl) timeEl.innerText = now.toLocaleTimeString([], { hour12: false });
  if (dateEl)
    dateEl.innerText = now.toLocaleDateString([], {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
    });

  // Sync Global Header/Sidebar Hora
  if (txtHour) txtHour.innerText = `${timeStr} (CDMX)`;
  if (mobHour) mobHour.innerText = timeStr;
}

function animateValue(id, start, end, duration) {
  const obj = document.getElementById(id);
  if (!obj) return;
  let startTimestamp = null;
  const step = (timestamp) => {
    if (!startTimestamp) startTimestamp = timestamp;
    const progress = Math.min((timestamp - startTimestamp) / duration, 1);
    obj.innerText = Math.floor(progress * (end - start) + start);
    if (progress < 1) {
      window.requestAnimationFrame(step);
    }
  };
  window.requestAnimationFrame(step);
}

// Simulador de Terminal IA

// Simulador de Terminal IA
function initDashboardEffects() {
  const terminal = document.getElementById("ai-terminal");
  if (!terminal) return;

  const logs = [
    "Sincronizando malla del Digital Twin...",
    "Analizando patrones térmicos en Alberca...",
    "Optimización de flujo energético completada.",
    "Detección de ocupación anómala: Ninguna.",
    "Sincronizando con MSSQL Server... OK",
    "IA: Sugiriendo ajuste en iluminación Campo 1.",
    "Estado del sistema: ESTABLE (99.8%)",
    "Analizando logs de reserva recientes...",
    "Digital Twin v2.4 operativo y listo.",
  ];

  setInterval(() => {
    if (
      document.getElementById("extended-dashboard").classList.contains("hidden")
    )
      return;

    const p = document.createElement("p");
    p.className = "log-line";
    p.innerText = `> ${logs[Math.floor(Math.random() * logs.length)]}`;
    terminal.appendChild(p);

    if (terminal.children.length > 20)
      terminal.removeChild(terminal.firstChild);
    terminal.scrollTop = terminal.scrollHeight;
  }, 3000);

  // Reloj en tiempo real
  setInterval(updateClock, 1000);
}

// Llamar a los efectos al inicio
initDashboardEffects();

let isModelExploded = false;
const btnExplode = document.getElementById("btn-explode");
if (btnExplode) {
  btnExplode.addEventListener("click", () => {
    isModelExploded = !isModelExploded;
    if (isModelExploded) {
      btnExplode.classList.add("active");
      btnExplode.querySelector(".layer-desc").innerText =
        "Restaurar arquitectura a posición original";

      // Al explotar, forzamos que nada sea transparente para apreciar bien los interiores
      const allBtn = document.querySelector('.layer-btn[data-layer="all"]');
      if (allBtn) allBtn.click();
    } else {
      btnExplode.classList.remove("active");
      btnExplode.querySelector(".layer-desc").innerText =
        "Elevar arquitectura para explorar interiores";
    }
  });
}

function updateAtmosphere() {
  const now = new Date();
  let hour, minute, decimalHour;

  if (lightSensorState.mode === "night") {
    hour = 22;
    minute = now.getMinutes();
    decimalHour = hour + minute / 60;
  } else if (lightSensorState.mode === "day") {
    hour = 12;
    minute = now.getMinutes();
    decimalHour = hour + minute / 60;
  } else if (isHistoryMode) {
    const targetDate = new Date(Date.now() + historyTimeValue * 60000);
    hour = targetDate.getHours();
    minute = targetDate.getMinutes();
    decimalHour = hour + minute / 60;
  } else {
    hour = now.getHours();
    minute = now.getMinutes();
    decimalHour = hour + minute / 60;
  }

  if (txtHour) {
    const timeStr = `${hour.toString().padStart(2, "0")}:${minute.toString().padStart(2, "0")}`;
    const sensorSuffix = lightSensorState.mode === "night" ? "SENSOR NOCHE" : "SENSOR DÍA";
    const txt = lightSensorState.mode
      ? `${timeStr} (${sensorSuffix})`
      : isHistoryMode
        ? `${timeStr} (HS)`
        : `${timeStr}`;
    txtHour.innerText = lightSensorState.mode
      ? `${timeStr} (${sensorSuffix})`
      : isHistoryMode
        ? `${timeStr} (HS)`
        : `${timeStr} (CDMX)`;

    // Sync móvil en header
    const mobHour = document.getElementById("txt-hour-mob");
    if (mobHour) mobHour.innerText = txt;
  }

  // Mapear hora al Sol (Simulación realista)
  // 6:00 (Amanecer, Elev 0), 12:00 (Cenit, Elev 90), 18:00 (Ocaso, Elev 0)
  let elevation = Math.sin(((decimalHour - 6) * Math.PI) / 12) * 90;

  // Si es modo SOLEADO (Caluroso), forzamos que el sol esté siempre alto (mínimo 15 grados)
  // para que el cielo siempre se vea azul profundo y no naranja de atardecer.
  if (currentWeatherType === "sunny" && lightSensorState.mode !== "night") {
    elevation = Math.max(15, elevation);
  }

  const azimuth = 180; // Orientación constante

  const phi = THREE.MathUtils.degToRad(90 - elevation);
  const theta = THREE.MathUtils.degToRad(azimuth);
  sun.setFromSphericalCoords(1, phi, theta);

  sky.material.uniforms["sunPosition"].value.copy(sun);
  clouds.material.uniforms.uSunPos.value.copy(sun);

  // Lógica de iluminación Día/Noche
  const isNight = elevation < -5;
  const lightIntensity = Math.max(0, Math.min(1, (elevation + 10) / 20));
  const nightLightTarget = 1 - Math.max(0, Math.min(1, (elevation + 4) / 18));

  // Parámetros de Clima que respetan la hora del día
  if (currentWeatherType === "sunny") {
    sky.material.uniforms["turbidity"].value = 0.1;
    sky.material.uniforms["rayleigh"].value = 4.0; // Azul cobalto intenso estable
    sky.material.uniforms["mieCoefficient"].value = 0.005;
    clouds.visible = false;
    clouds.material.uniforms.uOpacity.value = 0;

    // Caluroso: Tinte amarillento en luces si es de día
    if (!isNight) {
      dirLight.color.setHex(0xfff4d6);
      ambientLight.color.setHex(0xfffce8);
    }
  } else if (currentWeatherType === "rainy") {
    clouds.visible = true;
    sky.material.uniforms["mieCoefficient"].value = 0.05;
    sky.material.uniforms["turbidity"].value = 8.0;
    // Lluvia brillante: Subimos rayleigh para que el cielo sea un gris luminoso
    sky.material.uniforms["rayleigh"].value = isNight ? 0.01 : 2.5;
    clouds.material.uniforms.uCloudColor.value.setHex(0xaaaaaa);
    clouds.material.uniforms.uOpacity.value = Math.max(
      0.4,
      lightIntensity * 0.85,
    );

    // Lluvia gris: Tinte metálico/neutro
    dirLight.color.setHex(0xe9ecef);
    ambientLight.color.setHex(0xf8f9fa);
  } else {
    clouds.visible = true;
    sky.material.uniforms["mieCoefficient"].value = 0.005;
    sky.material.uniforms["turbidity"].value = 2.5;
    sky.material.uniforms["rayleigh"].value = isNight ? 0.02 : 1.2;
    clouds.material.uniforms.uCloudColor.value.setHex(0xffffff);
    clouds.material.uniforms.uOpacity.value = lightIntensity * 0.55;

    // Normal: Blanco puro
    dirLight.color.setHex(0xffffff);
    ambientLight.color.setHex(0xffffff);
  }

  // Ajustar luces de la escena
  dirLight.intensity = lightIntensity * 0.7;
  ambientLight.intensity = Math.max(0.2 + nightLightTarget * 0.18, lightIntensity * 0.4);
  fillLight.intensity = 0.3 + nightLightTarget * 0.35;
  setNightLighting(nightLightTarget);
  dirLight.position.set(
    sun.x * 2000,
    Math.max(200, sun.y * 2000),
    sun.z * 2000,
  );
}

function normalizeLightState(value) {
  const state = String(value || "")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .trim()
    .toUpperCase();

  if (state.includes("OSCURO")) return "night";
  if (state.includes("CLARO")) return "day";

  return null;
}

function getStableLightState(readings) {
  const states = (Array.isArray(readings) ? readings : [])
    .map((reading) => normalizeLightState(reading?.light_state))
    .filter(Boolean);

  let lastStableState = null;
  let currentState = null;
  let currentCount = 0;

  states.forEach((state) => {
    if (state === currentState) {
      currentCount += 1;
    } else {
      currentState = state;
      currentCount = 1;
    }

    if (currentCount >= 3) {
      lastStableState = state;
    }
  });

  return lastStableState;
}

function applyRecentLightReadings(readings) {
  const stableState = getStableLightState(readings);

  if (!stableState || lightSensorState.mode === stableState) return;

  lightSensorState.mode = stableState;
  lightSensorState.lastStableState = stableState;
  lightSensorState.consecutiveCount = 3;
  updateAtmosphere();
  addFeedItem(
    stableState === "night"
      ? "Sensor de luz: últimas 3 lecturas oscuras, activando noche"
      : "Sensor de luz: últimas 3 lecturas claras, activando día",
    "success",
  );
}

function formatSensorNumber(value, suffix) {
  const number = Number(value);

  if (!Number.isFinite(number)) return null;

  return `${number.toFixed(1)}${suffix}`;
}

function applySensorClimate(reading) {
  if (!reading) return;

  const temperature = formatSensorNumber(reading.temperature_c, "°C");
  const humidity = formatSensorNumber(reading.humidity_percent, "%");
  const climateSummary = [temperature, humidity].filter(Boolean).join(" / ");

  if (climateSummary) {
    const tempAvg = document.getElementById("txt-temp-avg");
    const tempAvgMob = document.getElementById("txt-temp-avg-mob");
    const dashboardTemp = document.getElementById("dash-avg-temp");

    if (tempAvg) tempAvg.innerText = climateSummary;
    if (tempAvgMob) tempAvgMob.innerText = climateSummary;
    if (dashboardTemp && temperature) dashboardTemp.innerText = temperature;

    if (currentSelectedRole && digitalTwinData[currentSelectedRole]) {
      digitalTwinData[currentSelectedRole].temp = temperature;
    }
  }

  if (humidity && currentSelectedRole && digitalTwinData[currentSelectedRole]) {
    digitalTwinData[currentSelectedRole].hum = humidity;
  }

  if (currentSelectedRole && digitalTwinData[currentSelectedRole]?.isSensor) {
    const cardTemp = document.getElementById("card-temp");
    const cardHum = document.getElementById("card-hum");

    if (temperature && cardTemp) cardTemp.innerText = temperature;
    if (humidity && cardHum) cardHum.innerText = humidity;
  }
}

async function syncLightSensor() {
  try {
    const response = await fetch(`${SENSOR_API_URL}?action=poll`, {
      cache: "no-store",
    });

    const data = await response.json();
    applySensorClimate(data?.reading);

    const readings = Array.isArray(data?.recent_readings)
      ? data.recent_readings
      : [data?.reading].filter(Boolean);

    applyRecentLightReadings(readings);
  } catch (error) {
    console.warn("Light sensor sync fail", error);
  }
}

function initLightSensorSync() {
  syncLightSensor();
  lightSensorState.pollTimer = window.setInterval(syncLightSensor, 3000);
}

function initWeatherControls() {
  const container = document.getElementById("weather-dropdown");
  const trigger = container?.querySelector(".dropdown-trigger");
  const btns = document.querySelectorAll(".weather-btn");

  // 1. Inicializar sistema de lluvia una sola vez
  if (!rain) {
    const rainGeo = new THREE.BufferGeometry();
    const rainCount = 15000;
    const positions = new Float32Array(rainCount * 3);
    for (let i = 0; i < rainCount * 3; i += 3) {
      positions[i] = (Math.random() - 0.5) * 4000;
      positions[i + 1] = Math.random() * 2000;
      positions[i + 2] = (Math.random() - 0.5) * 4000;
    }
    rainGeo.setAttribute("position", new THREE.BufferAttribute(positions, 3));
    const rainMat = new THREE.PointsMaterial({
      color: 0xaaaaaa,
      size: 1.5,
      transparent: true,
      opacity: 0,
      depthWrite: false,
    });
    rain = new THREE.Points(rainGeo, rainMat);
    scene.add(rain);
  }

  // 2. Control del Dropdown
  if (trigger && container) {
    trigger.addEventListener("click", (e) => {
      e.stopPropagation();
      container.classList.toggle("active");
      // Cerrar otros al abrir este
      document.querySelector(".sidebar")?.classList.remove("mobile-visible");
      document.getElementById("history-panel")?.classList.add("hidden");
    });
  }

  // 3. Selección de Clima (Manual)
  btns.forEach((btn) => {
    btn.addEventListener("click", () => {
      btns.forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");
      currentWeatherType = btn.dataset.weather;

      // Al cambiar manual, apagamos la sincronización real automática
      weatherSyncEnabled = false;

      // Actualizar Visuales
      if (rain) {
        rain.material.opacity = currentWeatherType === "rainy" ? 0.6 : 0;
      }
      updateAtmosphere();

      if (container) container.classList.remove("active");
      addFeedItem(
        `Simulación Manuel: ${currentWeatherType.toUpperCase()}`,
        "info",
      );
    });
  });

  // 4. Regresar al Clima Real (Sincronización)
  const btnSync = document.getElementById("btn-sync-weather");
  if (btnSync) {
    btnSync.addEventListener("click", () => {
      weatherSyncEnabled = true;
      syncRealWeather(); // Ejecutar inmediatamente
      if (container) container.classList.remove("active");
      addFeedItem("Sincronizando clima real de hoy...", "success");
    });
  }

  // Cerrar al hacer clic fuera
  document.addEventListener("click", () => {
    container?.classList.remove("active");
  });

  // 5. Función Maestra: Sincronización Real (Open-Meteo)
  async function syncRealWeather() {
    if (!weatherSyncEnabled) return;
    try {
      const url = `https://api.open-meteo.com/v1/forecast?latitude=${LATITUDE}&longitude=${LONGITUDE}&current=temperature_2m,relative_humidity_2m,weather_code&timezone=auto`;
      const response = await fetch(url);
      const data = await response.json();
      if (!data.current) return;

      const code = data.current.weather_code;
      const temp = Math.round(data.current.temperature_2m);
      const humidity = data.current.relative_humidity_2m;

      // Mapear clima real a tipos locales
      let newType = "normal";
      if (code >= 51) newType = "rainy";
      else if (code === 0) newType = "sunny";

      currentWeatherType = newType;

      // Sincronizar UI de botones
      btns.forEach((b) => {
        b.classList.toggle("active", b.dataset.weather === newType);
      });

      if (rain) rain.material.opacity = newType === "rainy" ? 0.6 : 0;
      updateAtmosphere();

      const tempLabel = document.getElementById("txt-temp-avg");
      if (tempLabel) tempLabel.innerText = `${temp}°C`;
      const mobTemp = document.getElementById("txt-temp-avg-mob");
      if (mobTemp) mobTemp.innerText = `${temp}°C`;

      addFeedItem(`Clima Real Detectado: ${temp}°C`, "success");
    } catch (e) {
      console.warn("Weather Sync Fail", e);
    }
  }

  // Primera ejecución y loop
  syncRealWeather();
  setInterval(syncRealWeather, 10 * 60 * 1000);
}

// --- VIAJE EN EL TIEMPO: LÓGICA DE CONTROL ---
const btnHistory = document.getElementById("btn-history");
const historyPanel = document.getElementById("history-panel");
const historySlider = document.getElementById("history-slider");
const btnBackToLive = document.getElementById("btn-back-to-live");
const closeHistory = document.getElementById("close-history");

if (btnHistory && historyPanel) {
  btnHistory.addEventListener("click", () => {
    historyPanel.classList.toggle("hidden");
    btnHistory.classList.toggle("history-active");

    if (!historyPanel.classList.contains("hidden")) {
      // Sincronizar UI del tiempo con el valor actual del slider (0 = AHORA)
      updateHistoryUI(parseInt(historySlider.value || 0));
    }
  });

  closeHistory.addEventListener("click", () => {
    historyPanel.classList.add("hidden");
    btnHistory.classList.remove("history-active");
  });
}

if (historySlider) {
  historySlider.addEventListener("input", (e) => {
    const val = parseInt(e.target.value);
    updateHistoryMode(val);
  });
}

if (btnBackToLive) {
  btnBackToLive.addEventListener("click", () => {
    historySlider.value = 0;
    updateHistoryMode(0);
  });
}

function updateHistoryMode(minutes) {
  historyTimeValue = minutes;
  isHistoryMode = minutes !== 0; // 0 = AHORA (modo vivo)

  if (isHistoryMode) {
    document.body.classList.add("history-mode");
    btnBackToLive.classList.remove("hidden");
  } else {
    document.body.classList.remove("history-mode");
    btnBackToLive.classList.add("hidden");
    // Restaurar datos en vivo
    Object.assign(digitalTwinData, liveDataBackup);
    applyDBCountsToWorld();
    return;
  }

  updateHistoryUI(minutes);
  updateAtmosphere();
  simulateHistoryEffect(minutes);
}

function minutesToTimeStr(min) {
  // min = offset en minutos desde AHORA
  const absMin = Math.abs(min);
  const targetDate = new Date(Date.now() + min * 60000);
  const h = targetDate.getHours();
  const m = targetDate.getMinutes();
  const ampm = h >= 12 ? "PM" : "AM";
  const h12 = h % 12 || 12;
  return `${h12}:${m.toString().padStart(2, "0")} ${ampm}`;
}

function updateHistoryUI(min) {
  const timeDisplay = document.getElementById("hist-selected-time");
  const dateDisplay = document.getElementById("hist-selected-date");
  const avgOcc = document.getElementById("hist-avg-occ");

  // Hora del momento seleccionado
  const targetDate = new Date(Date.now() + min * 60000);
  const h = targetDate.getHours();
  const m = targetDate.getMinutes();
  const ampm = h >= 12 ? "PM" : "AM";
  const h12 = h % 12 || 12;
  const timeStr = `${h12}:${m.toString().padStart(2, "0")} ${ampm}`;

  if (timeDisplay) timeDisplay.innerText = timeStr;

  if (dateDisplay) {
    if (min === 0) dateDisplay.innerText = "Ahora (En vivo)";
    else if (min < 0)
      dateDisplay.innerText = `Hace ${Math.abs(Math.round(min / 60))}h — ${targetDate.toLocaleDateString("es-MX", { weekday: "short", day: "numeric", month: "short" })}`;
    else
      dateDisplay.innerText = `En ${Math.round(min / 60)}h — ${targetDate.toLocaleDateString("es-MX", { weekday: "short", day: "numeric", month: "short" })}`;
  }

  if (avgOcc) {
    let occ = "Baja";
    if (h >= 8 && h <= 12) occ = "Alta";
    else if (h > 12 && h <= 18) occ = "Media";
    else if (h > 18 && h <= 22) occ = "Alta";
    avgOcc.innerText = occ;
  }
}

// Debounce para evitar flood de requests mientras se arrastra el slider
let _historyDebounceTimer = null;

function simulateHistoryEffect(offsetMin) {
  // offsetMin = 0 → AHORA, negativo = pasado, positivo = futuro

  // Calcular la hora del momento seleccionado (para sensores)
  const targetDate = new Date(Date.now() + offsetMin * 60000);
  const hour = targetDate.getHours();
  const isClosed = hour >= 23 || hour < 6;

  // Actualizar sensores (simulación pura, no necesita DB)
  Object.keys(digitalTwinData).forEach((role) => {
    const data = digitalTwinData[role];
    if (!data.isSensor) return;
    const dayFactor = Math.sin((hour * Math.PI) / 12);
    data.bat = (90 + Math.random() * 8).toFixed(0) + "%";
    data.temp = (20 + dayFactor * 10 + Math.random() * 2).toFixed(1) + "°C";
    data.hum = (50 - dayFactor * 20 + Math.random() * 5).toFixed(0) + "%";
    data.status = hour < 6 ? "Modo Hibernación" : "Transmisión LoRaWAN";
    if (data.specialLabel === "SONIDO (dB)") {
      data.specialVal =
        (isClosed ? 30 : 70 + Math.random() * 15).toFixed(1) + " dB";
    }
  });

  // ── Fetch datos reales de la DB para el momento seleccionado (debounced 300ms) ──
  clearTimeout(_historyDebounceTimer);
  _historyDebounceTimer = setTimeout(() => {
    fetch(`${API_BASE_URL}/api/reservations/history?offset=${offsetMin}`)
      .then((r) => r.json())
      .then((data) => {
        const totals = data.totals || { gym: 0, pool: 0, canchas: 0 };
        const capacityLimits = { gym: 50, pool: 30, canchas: 20 };
        let totalPeople = 0;

        ["gym", "pool", "canchas"].forEach((role) => {
          let count = totals[role] ?? 0;

          // Si la hora está cerrada, forzar 0
          if (isClosed) count = 0;

          totalPeople += count;
          if (digitalTwinData[role]) {
            digitalTwinData[role].current = count;
            digitalTwinData[role].status = isClosed
              ? "Cerrado / Limpieza"
              : "Operativo";
            digitalTwinData[role].statusClass = isClosed
              ? "status-warning"
              : "status-good";
          }
          spawnPeopleInRole(role, Math.min(count, capacityLimits[role]));
        });

        // Actualizar Monitoreo
        const totalCap = 50 + 30 + 20;
        const capPct = Math.min(
          100,
          Math.floor((totalPeople / totalCap) * 100),
        );
        const capColor =
          capPct < 50
            ? "var(--success-color)"
            : capPct < 80
              ? "var(--warning-color)"
              : "var(--danger-color)";
        const el = document.getElementById("txt-capacity");
        const elMob = document.getElementById("txt-capacity-mob");
        if (el) {
          el.innerText = `${capPct}%`;
          el.style.color = capColor;
        }
        if (elMob) {
          elMob.innerText = `${capPct}%`;
          elMob.style.color = capColor;
        }

        const totalEl = document.getElementById("txt-total-people");
        if (totalEl) totalEl.innerText = totalPeople;
        const gymEl = document.getElementById("txt-pop-gym");
        const poolEl = document.getElementById("txt-pop-pool");
        const canchasEl = document.getElementById("txt-pop-canchas");
        if (gymEl) gymEl.innerText = totals.gym;
        if (poolEl) poolEl.innerText = totals.pool;
        if (canchasEl) canchasEl.innerText = totals.canchas;

        addFeedItem(
          `🕐 Historial ${offsetMin >= 0 ? "+" : ""}${Math.round(offsetMin / 60)}h → ${totalPeople} pax (${data.window?.from}–${data.window?.to})`,
          "info",
        );

        // Actualizar info card si está abierta (sea cual sea el método de apertura)
        if (currentSelectedRole && !infoCard.classList.contains("hidden")) {
          showInfoCard(currentSelectedRole);
        }

        if (
          !document
            .getElementById("extended-dashboard")
            .classList.contains("hidden")
        ) {
          updateDashboardData();
        }
      })
      .catch((err) => {
        console.error("History Fetch Error:", err);
        // Fallback simulado si el API no responde
        ["gym", "pool", "canchas"].forEach((role) => {
          const count = isClosed ? 0 : Math.floor(Math.random() * 20);
          if (digitalTwinData[role]) {
            digitalTwinData[role].current = count;
            digitalTwinData[role].status = "Simulación (Sin Conexión)";
          }
          spawnPeopleInRole(role, count);
        });
        if (currentSelectedRole && !infoCard.classList.contains("hidden")) {
          showInfoCard(currentSelectedRole);
        }
      });
  }, 300);
}

// Bucle de Animación
function animate() {
  requestAnimationFrame(animate);
  timer.update();

  // Lógica de Giro Panorámico
  if (isPanoActive) {
    panoAngle += 0.002;
    const radius = 1000;
    camera.position.x = Math.cos(panoAngle) * radius;
    camera.position.z = Math.sin(panoAngle) * radius;
    camera.position.y = 400;
    controls.target.set(0, 0, 0);
  }

  // Efecto 'Pulso de Vida' y MOVIMIENTO ACENTUADO
  const pulseEmissive = 2.0 + Math.sin(Date.now() * 0.005) * 1.5;
  const bobbing = Math.sin(Date.now() * 0.003) * 0.8; // Bobbing balanceo más visible

  Object.keys(peopleInstances).forEach((role) => {
    const inst = peopleInstances[role];
    const states = peopleStates[role];
    if (inst && states.length > 0) {
      const dummy = new THREE.Object3D();

      for (let i = 0; i < states.length; i++) {
        const p = states[i];
        // Movimiento más rápido (Caminata Digital)
        p.pos.x += p.dir.x * p.speed * 2.5;
        p.pos.z += p.dir.z * p.speed * 2.5;

        // Rebote en límites (con un pequeño offset interno)
        if (p.pos.x < p.bounds.min.x || p.pos.x > p.bounds.max.x) p.dir.x *= -1;
        if (p.pos.z < p.bounds.min.z || p.pos.z > p.bounds.max.z) p.dir.z *= -1;

        // SINCRONIZACIÓN CON VISTA EXPLOSIONADA:
        // Sincronizar habitantes del gym con la nueva altura de 60
        const roleExplodeOffset = role === "gym" ? 20 * explodeFactor : 0;
        dummy.position.set(
          p.pos.x,
          p.pos.y + bobbing + roleExplodeOffset,
          p.pos.z,
        );

        if (role === "pool") {
          // Orientación de nado: Se inclina para mirar hacia adelante mientras nada
          dummy.rotation.set(Math.PI / 2, Math.atan2(p.dir.x, p.dir.z), 0);
        } else {
          dummy.rotation.y = Math.atan2(p.dir.x, p.dir.z);
        }
        dummy.scale.set(p.scale, p.scale, p.scale);
        dummy.updateMatrix();
        inst.setMatrixAt(i, dummy.matrix);
      }
      inst.instanceMatrix.needsUpdate = true;
    }
  });

  const time = performance.now() * 0.001;

  // Suavizado de Cámara Pro
  if (isCameraMoving) {
    camera.position.lerp(cameraTargetPos, 0.05);
    controls.target.lerp(controlsTargetPos, 0.05);

    if (camera.position.distanceTo(cameraTargetPos) < 1.0) {
      isCameraMoving = false;
    }
  }

  // Animación de UI Espacial

  // Posicionamiento de Info Card (Fijo a la Izquierda, ya no sigue el objeto 3D)
  /* if (infoCard && !infoCard.classList.contains("hidden")) {
    const pos3dStr = infoCard.dataset.target3d;
    if (pos3dStr) {
      const center = JSON.parse(pos3dStr);
      // Punto de anclaje de la tarjeta (un poco arriba del centro del objeto)
      const vector = new THREE.Vector3(center.x, center.y + 40, center.z);
      vector.project(camera);

      const x = (vector.x * 0.5 + 0.5) * window.innerWidth;
      const y = (-(vector.y * 0.5) + 0.5) * window.innerHeight;

      // Posicionar la tarjeta a la derecha del objeto con un offset
      infoCard.style.top = `${y}px`;
      infoCard.style.left = `${x}px`;
      infoCard.style.transform = `translate(-5%, -50%) scale(0.9)`;
    }
  } */

  // Posicionamiento de Etiqueta Flotante
  if (floatingLabel && !floatingLabel.classList.contains("hidden")) {
    const pos3dStr = floatingLabel.dataset.target3d;
    if (pos3dStr) {
      const center = JSON.parse(pos3dStr);
      const vector = new THREE.Vector3(center.x, center.y + 80, center.z);
      vector.project(camera);

      const x = (vector.x * 0.5 + 0.5) * window.innerWidth;
      const y = (-(vector.y * 0.5) + 0.5) * window.innerHeight;

      floatingLabel.style.transform = `translate(-50%, -50%) translate(${x}px, ${y}px)`;
    }
  }

  // Solo actualizar OrbitControls si NO estamos caminando
  if (!isWalkMode) {
    controls.update();
  }

  updateAtmosphere(); // Sincroniza Sol, Clima y Hora CDMX

  // Pulsación suave y Vista Explosionada Sincronizada
  if (model) {
    const time = timer.getElapsed();

    // Actualizar factor de explosión global (Lerp dinámico)
    const targetExp = isModelExploded ? 1.0 : 0.0;
    explodeFactor += (targetExp - explodeFactor) * 0.08;

    model.traverse((child) => {
      // 1. Resplandor pulsante
      if (child.isMesh && child.material.emissiveIntensity > 20.0) {
        const pulse = 25.0 + Math.sin(time * 3) * 3.0;
        child.material.emissiveIntensity = pulse;
      }

      // 2. Movimiento de la Estructura (Sincronizado con explodeFactor)
      if (child.userData.explodeOffset !== undefined) {
        child.position.y =
          child.userData.originalY +
          child.userData.explodeOffset * explodeFactor;
        child.updateMatrix();
      }
    });

    // Actualizar nubes procedurales
    if (clouds) clouds.material.uniforms.uTime.value = time;

    // Actualizar lluvia si está activa
    if (rain && rain.material.opacity > 0) {
      const pos = rain.geometry.attributes.position.array;
      for (let i = 1; i < pos.length; i += 3) {
        pos[i] -= 25.0; // Caída rápida
        if (pos[i] < -100) pos[i] = 1500;
      }
      rain.geometry.attributes.position.needsUpdate = true;
    }
  }

  const currentTime = performance.now();
  const delta = (currentTime - lastTime) / 1000;
  lastTime = currentTime;
  
  updateWalkMode(delta);

  renderer.render(scene, camera); // Renderizado directo para máxima velocidad

  // --- LABORATORIO DE SIMULACIONES (WHAT-IF ANALYSIS) ---
function runSimulation(type) {
  if (!model) return;

  addFeedItem(`🧪 Iniciando Simulación: ${type.toUpperCase()}`, "info");

  if (type === "peak") {
    // Simulamos capacidad máxima en todo el complejo
    dbCounts.gym = 50;
    dbCounts.pool = 30;
    dbCounts.canchas = 20;
    applyDBCountsToWorld();
    addFeedItem("⚠️ Alerta: Capacidad máxima alcanzada en todas las áreas", "danger");
  } 
  else if (type === "maintenance-gym") {
    // Cerramos el gimnasio por mantenimiento
    dbCounts.gym = 0;
    digitalTwinData.gym.status = "CERRADO POR MANTENIMIENTO";
    digitalTwinData.gym.statusClass = "status-warning";
    
    // Cambiamos el color de la zona para indicar cierre
    model.traverse(c => {
        if (c.isMesh && c.userData.role === "gym") {
            c.material.emissive.setHex(0x333333);
            c.material.emissiveIntensity = 0.5;
        }
    });

    applyDBCountsToWorld();
    addFeedItem("🔧 Simulación: Gimnasio fuera de servicio", "warning");
  }
  else if (type === "event") {
    // Simulamos un evento de 100 personas repartidas
    dbCounts.gym = 40;
    dbCounts.pool = 40; // Sobrepasa límite
    dbCounts.canchas = 20;
    applyDBCountsToWorld();
    addFeedItem("📢 Simulación: Evento Corporativo en curso", "success");
  }
  else if (type === "reset") {
    // Volvemos a los datos reales (o 0 si no hay DB)
    location.reload(); // Forma más limpia de resetear el estado del modelo
  }
}
window.runSimulation = runSimulation;
  spatialLabels.forEach((lbl) => {
    // Calculamos posición final sumando el movimiento de la explosión si aplica
    const worldPos = lbl.basePos.clone();
    
    // Si es el gimnasio, sumamos el offset de explosión (20 unidades)
    if (lbl.role === 'gym') {
        worldPos.y += (20 * explodeFactor);
    }
    // Si fuera el techo (roof), sumaríamos 40, etc.
    
    // Aplicamos el offset de altura de la propia etiqueta
    worldPos.y += lbl.yOffset;

    const vector = worldPos.clone();
    vector.project(camera);

    // Conversión de pantalla refinada
    const x = (vector.x * 0.5 + 0.5) * window.innerWidth;
    const y = (vector.y * -0.5 + 0.5) * window.innerHeight;

    // Unificar ocultamiento: detrás de cámara o fuera de rango
    const isBehind = vector.z > 1;
    lbl.el.style.display = isBehind ? "none" : "flex";

    if (!isBehind) {
      lbl.el.style.left = `${x}px`;
      lbl.el.style.top = `${y}px`;

      const dist = camera.position.distanceTo(worldPos);
      const scale = Math.max(0.4, Math.min(1.0, 800 / dist));
      lbl.el.style.transform = `translate(-50%, -50%) scale(${scale})`;
      lbl.el.style.opacity = Math.max(0.1, Math.min(1.0, 1200 / dist));
    }
  });
}

animate();

// Responsivo
window.addEventListener("resize", () => {
  camera.aspect = window.innerWidth / window.innerHeight;
  camera.updateProjectionMatrix();
  renderer.setSize(window.innerWidth, window.innerHeight);
  // composer.setSize(window.innerWidth, window.innerHeight);
});

// INTERACTIVIDAD: HOVER Y CLICK
let hoveredRole = null;

function onMouseMove(event) {
  // 1. Giro de cámara en Modo Showroom (Manual)
  if (isWalkMode) {
    // Validar contra el elemento que tiene el foco (Vite puede inyectar el canvas)
    if (document.pointerLockElement) {
        yaw -= event.movementX * mouseSensitivity;
        pitch -= event.movementY * mouseSensitivity;
        pitch = Math.max(-Math.PI / 2.1, Math.min(Math.PI / 2.1, pitch));
        
        camera.rotation.order = 'YXZ';
        camera.rotation.set(pitch, yaw, 0);
    }
    return; 
  }

  if (!model || event.target.tagName !== "CANVAS") return;

  mouse.x = (event.clientX / window.innerWidth) * 2 - 1;
  mouse.y = -(event.clientY / window.innerHeight) * 2 + 1;

  raycaster.setFromCamera(mouse, camera);
  const intersects = raycaster.intersectObject(model, true);

  let currentHoverRole = null;
  // Añadimos sensores a los roles de interés
  const interestRoles = [
    "gym",
    "pool",
    "canchas",
    "sensor1",
    "sensor2",
    "sensor3",
  ];

  if (intersects.length > 0) {
    // BÚSQUEDA ROBUSTA: Recorremos todas las intersecciones y sus ancestros
    for (const intersect of intersects) {
      let testObj = intersect.object;
      let role = null;

      while (testObj && testObj !== model) {
        if (
          testObj.userData.role &&
          interestRoles.includes(testObj.userData.role)
        ) {
          role = testObj.userData.role;
          break;
        }
        testObj = testObj.parent;
      }

      if (role) {
        currentHoverRole = role;
        break;
      }
    }
  }

  if (currentHoverRole !== hoveredRole) {
    hoveredRole = currentHoverRole;

    // Cambiar cursor (pointer para zonas de interés)
    const containerEl = document.getElementById("container");
    if (containerEl) {
      containerEl.style.cursor = hoveredRole ? "pointer" : "default";
    }

    // Aplicar brillo de hover a todos los objetos del rol
    model.traverse((child) => {
      if (child.isMesh && child.userData.role && !child.userData.isHitBox) {
        const materials = Array.isArray(child.material)
          ? child.material
          : [child.material];
        materials.forEach((mat) => {
          if (
            child.userData.role === hoveredRole &&
            !child.userData.isSelectedInFocus
          ) {
            // Mantenemos la textura y solo añadimos emisión en el hover
            mat.emissive.copy(
              child.userData.highlightColor || new THREE.Color(0x3b82f6),
            );
            mat.emissiveIntensity = 0.5; // Brillo sutil de pre-selección
          } else if (!child.userData.isSelectedInFocus) {
            mat.emissive.setHex(0x000000);
          }
        });
      }
    });
  }
}

function onMouseClick(event) {
  // Solo procesar si fue una interacción rápida y sin movimiento (Punto a Punto) para no bloquear la navegación
  const duration = Date.now() - clickStartTime;
  const dist = Math.hypot(
    event.clientX - clickStartX,
    event.clientY - clickStartY,
  );

  // Si tardó más de 250ms o se movió más de 5px, es una navegación, NO un clic de selección
  if (duration > 250 || dist > 5) return;

  if (!model || event.target.tagName !== "CANVAS") return;

  mouse.x = (event.clientX / window.innerWidth) * 2 - 1;
  mouse.y = -(event.clientY / window.innerHeight) * 2 + 1;

  raycaster.setFromCamera(mouse, camera);

  if (model) {
    const intersects = raycaster.intersectObject(model, true);

    if (intersects.length > 0) {
      // SELECCIÓN PENETRANTE: Buscamos primero zonas de interés (Gym, Pool, Canchas)
      // Saltándonos el techo y la estructura si hay algo debajo
      let role = null;
      let selectedObject = null;

      for (const intersect of intersects) {
        let testObj = intersect.object;
        let testRole = testObj.userData.role;

        while (!testRole && testObj.parent && testObj.parent !== model) {
          testObj = testObj.parent;
          testRole = testObj.userData.role;
        }

        // Si el rol es zona de interés o SENSORES
        const allRoles = [
          "gym",
          "pool",
          "canchas",
          "sensor1",
          "sensor2",
          "sensor3",
        ];
        if (testRole && allRoles.includes(testRole)) {
          role = testRole;

          selectedObject = testObj;
          break;
        }
      }

      // Si no encontramos zona de interés (ej: clic en un área vacía), tomamos el primero
      if (!role) {
        selectedObject = intersects[0].object;
        role = selectedObject.userData.role;
        let parent = selectedObject.parent;
        while (!role && parent && parent !== model) {
          role = parent.userData.role;
          parent = parent.parent;
        }
      }

      if (role && digitalTwinData[role]) {
        // Limpiar estado previo
        model.traverse((c) => {
          if (c.isMesh) c.userData.isSelectedInFocus = false;
        });

        showInfoCard(role);
        // Marcar como seleccionado para que el hover no lo limpie
        model.traverse((c) => {
          if (c.isMesh && c.userData.role === role)
            c.userData.isSelectedInFocus = true;
        });

        updateFocus(role);

        // Sincronizar botones laterales
        const btns = document.querySelectorAll(".layer-btn");
        btns.forEach((b) => {
          b.classList.remove("active");
          if (b.dataset.layer === role) b.classList.add("active");
        });
      } else {
        // Restaurar si se toca el suelo u otra cosa: MODO DESANCLAR (Deselector)
        deselectEverything();
      }
    } else {
      // Si se hace clic en el cielo: MODO DESANCLAR (Deselector)
      deselectEverything();
    }
  }
}

function deselectEverything() {
  updateFocus("all");

  if (infoCard) infoCard.classList.add("hidden");
  if (floatingLabel) floatingLabel.classList.add("hidden");

  const btns = document.querySelectorAll(".layer-btn");
  btns.forEach((b) => {
    b.classList.remove("active");
    if (b.dataset.layer === "all") b.classList.add("active");
  });
}

// Re-activar Pointer Lock al hacer clic si estamos en Showroom
container.addEventListener('click', () => {
    if (isWalkMode && document.pointerLockElement !== container) {
        container.requestPointerLock();
    }
});

function showInfoCard(role) {
  if (!digitalTwinData[role]) return;
  currentSelectedRole = role; // Guardar referencia para refrescos automáticos
  const data = digitalTwinData[role];

  // Elementos principales
  const titleEl = document.getElementById("card-title");
  const peopleEl = document.getElementById("current-people");
  const expectedEl = document.getElementById("expected-people");
  const tempEl = document.getElementById("card-temp");
  const humEl = document.getElementById("card-hum");
  const maintEl = document.getElementById("card-maint");
  const hoursEl = document.getElementById("card-hours");
  const statusEl = document.getElementById("area-status");
  const alertBanner = document.getElementById("alert-banner");
  const statusBox = document.getElementById("status-box");

  // Nuevos campos de Sensores
  const standardMetrics = document.getElementById("standard-metrics");
  const sensorTelemetry = document.getElementById("sensor-telemetry");
  const sensorBat = document.getElementById("sensor-bat");
  const sensorSpecLabel = document.getElementById("sensor-spec-label");
  const sensorSpecVal = document.getElementById("sensor-spec-val");

  if (titleEl) titleEl.innerText = data.title;

  // Control de Visualización (Cámara vs LiDAR)
  const lidarCont = document.getElementById("lidar-container");
  const camNoise = document.getElementById("camera-noise");
  const camScan = document.getElementById("camera-scanline");
  const liveTag = document.getElementById("live-tag-text");

  // Paneles de Detalle dinámicos
  const standardTrend = document.getElementById("standard-trend");
  const sensorAdvanced = document.getElementById("sensor-advanced");
  const diagL1 = document.getElementById("diag-label-1");
  const diagV1 = document.getElementById("diag-val-1");
  const diagL2 = document.getElementById("diag-label-2");
  const diagV2 = document.getElementById("diag-val-2");
  const diagL3 = document.getElementById("diag-label-3");
  const diagBar = document.getElementById("diag-bar-fill");

  if (data.isAsset) {
    // Modo Diagnóstico de Activo
    if (standardMetrics) standardMetrics.classList.add("hidden");
    if (sensorTelemetry) sensorTelemetry.classList.add("hidden");
    if (standardTrend) standardTrend.classList.add("hidden");
    if (sensorAdvanced) sensorAdvanced.classList.remove("hidden");
    
    if (lidarCont) lidarCont.classList.remove("hidden");
    if (liveTag) liveTag.innerText = "DIAGNOSTIC MODE";
    
    if (diagL1) diagL1.innerText = "SALUD DEL SISTEMA";
    if (diagV1) diagV1.innerText = (data.health || 98) + "%";
    if (diagL2) diagL2.innerText = "VIBRACIÓN";
    if (diagV2) diagV2.innerText = data.vibration || "0.4mm/s";
    if (diagL3) diagL3.innerText = "ESTADO DE SEÑAL";
    if (diagBar) diagBar.style.width = (data.health || 98) + "%";
    
    if (tempEl) tempEl.innerText = data.temp || "38°C";
    if (humEl) humEl.innerText = (data.hours || 1200) + " hrs";
    if (maintEl) maintEl.innerText = data.nextService || "Pendiente";
    if (hoursEl) hoursEl.innerText = data.status || "OPERATIVO";

    infoCard.classList.remove("hidden");
    return;
  }

  if (data.isSensor) {
    if (standardMetrics) standardMetrics.classList.add("hidden");
    if (sensorTelemetry) sensorTelemetry.classList.remove("hidden");

    // Mostrar Diagnóstico Avanzado y ocultar Telemetría Semanal
    if (standardTrend) standardTrend.classList.add("hidden");
    if (sensorAdvanced) sensorAdvanced.classList.remove("hidden");

    // Poblar Diagnósticos
    if (diagL1) diagL1.innerText = data.diag1_label;
    if (diagV1) diagV1.innerText = data.diag1_val;
    if (diagL2) diagL2.innerText = data.diag2_label;
    if (diagV2) diagV2.innerText = data.diag2_val;
    if (diagL3) diagL3.innerText = data.diag3_label;
    if (diagBar) diagBar.style.width = (data.diag_bar || 0) + "%";

    // Activar LiDAR
    if (lidarCont) lidarCont.classList.remove("hidden");
    if (camNoise) camNoise.classList.add("hidden");
    if (camScan) camScan.classList.add("hidden");
    if (liveTag) liveTag.innerText = "LiDAR SCAN v2";

    if (sensorBat) sensorBat.innerText = data.bat;
    if (sensorSpecLabel) sensorSpecLabel.innerText = data.specialLabel;
    if (sensorSpecVal) sensorSpecVal.innerText = data.specialVal;

    // Mantener visibles campos técnicos si existen
    if (tempEl) tempEl.innerText = data.temp || "--";
    if (humEl) humEl.innerText = data.hum || "--";
    if (maintEl) maintEl.innerText = "SISTEMA OK";
    if (hoursEl) hoursEl.innerText = "24 / 7";
  } else {
    if (standardMetrics) standardMetrics.classList.remove("hidden");
    if (sensorTelemetry) sensorTelemetry.classList.add("hidden");

    // Mostrar Telemetría Semanal y ocultar Diagnóstico
    if (standardTrend) standardTrend.classList.remove("hidden");
    if (sensorAdvanced) sensorAdvanced.classList.add("hidden");

    // Restaurar Cámara normal
    if (lidarCont) lidarCont.classList.add("hidden");
    if (camNoise) camNoise.classList.remove("hidden");
    if (camScan) camScan.classList.remove("hidden");
    if (liveTag) liveTag.innerText = "LIVE FEED";

    if (peopleEl) {
      // Usar data.current como fuente única de verdad para el UI
      peopleEl.innerText =
        typeof data.current === "number"
          ? data.current
          : typeof data.current === "string"
            ? data.current.split(" ")[0]
            : "...";
    }
    if (expectedEl) {
      const limit = parseInt(data.expected) || 0;
      const cur = typeof data.current === "number" ? data.current : 0;
      const free = Math.max(0, limit - cur);
      expectedEl.innerText = `${free}/${limit}`;
    }
    if (tempEl) tempEl.innerText = data.temp;
    if (humEl) humEl.innerText = data.hum;
    if (maintEl) maintEl.innerText = data.maint;
    if (hoursEl) hoursEl.innerText = data.hours;
  }

  if (statusEl) statusEl.innerText = data.status;

  // Actualizar clase de estado en el box premium
  if (statusBox) {
    statusBox.className =
      "status-box-premium " + (data.statusClass || "status-good");
  }

  // Manejo de Alerta
  if (alertBanner) {
    if (data.statusClass === "status-danger") {
      alertBanner.classList.add("active");
    } else {
      alertBanner.classList.remove("active");
    }
  }

  // Actualizar Gráfico de Tendencia
  const bars = document.querySelectorAll(".trend-bar");
  if (bars.length > 0 && data.trend) {
    bars.forEach((bar, i) => {
      if (data.trend[i]) {
        const val = data.trend[i];
        bar.style.height = val + "%";
        if (val > 80) bar.classList.add("high");
        else bar.classList.remove("high");
      }
    });
  }

  if (infoCard) {
    infoCard.classList.remove("hidden");

    // Dibujar Gráfica de Línea
    updateLineChart(data.trend || [20, 50, 30, 80, 40, 90]);

    // Trigger para relanzar la animación del Live Feed (opcional)
    const scanLine = infoCard.querySelector(".scan-line");
    if (scanLine) {
      scanLine.style.animation = "none";
      scanLine.offsetHeight; // trigger reflow
      scanLine.style.animation = null;
    }
  }
}

function updateLineChart(data) {
  const path = document.getElementById("chart-path");
  if (!path) return;

  const width = 300;
  const height = 80;
  const stepX = width / (data.length - 1);

  let d = `M 0 ${height - (data[0] / 100) * height}`;

  for (let i = 1; i < data.length; i++) {
    const x = i * stepX;
    const y = height - (data[i] / 100) * height;
    d += ` L ${x} ${y}`;
  }

  // Cerrar el path para el gradiente
  const closedD = d + ` L ${width} ${height} L 0 ${height} Z`;
  path.setAttribute("d", closedD);
}

function addFeedItem(text, type = "info") {
  const container = document.getElementById("notification-container");
  if (!container) return;

  const toast = document.createElement("div");
  toast.className = `toast ${type}`;

  const now = new Date();
  const timeStr = now.toLocaleTimeString([], {
    hour: "2-digit",
    minute: "2-digit",
  });

  toast.innerHTML = `
        <div class="toast-header">
            <span class="toast-title">Notificación de Sistema</span>
            <span class="toast-time">${timeStr}</span>
        </div>
        <div class="toast-body">${text}</div>
    `;

  container.appendChild(toast);
  setTimeout(() => {
    toast.classList.add("hidden");
    setTimeout(() => toast.remove(), 400);
  }, 5000);
}

// Inicializar feed con algunos eventos de bienvenida
setTimeout(() => {
  addFeedItem("⚠️ Alerta: Temperatura crítica detectada en motores", "danger");
  setTimeout(() => {
    addFeedItem("✅ Mantenimiento preventivo completado", "success");
  }, 2500);
}, 1000);

window.addEventListener("mousedown", onMouseDown);
window.addEventListener("click", onMouseClick);
window.addEventListener("mousemove", onMouseMove);

if (controls) {
  // Solo la interacción HUMANA real interrumpe el movimiento automático
  // Usamos wheel y mousedown/pointerdown directos porque controls 'change' se disparaba solo
  const unlockCamera = () => {
    isCameraMoving = false;
    applyStandardControls(camera, controls);
    controls.update();
  };

  window.addEventListener("wheel", unlockCamera, { capture: true, passive: true });
  window.addEventListener("pointerdown", (e) => {
    if (e.target.tagName === "CANVAS") unlockCamera();
  }, { capture: true });
  window.addEventListener(
    "touchstart",
    (e) => {
      if (e.target.tagName === "CANVAS") unlockCamera();
    },
    { capture: true, passive: true },
  );
}
function initPopulation() {
  // Generar población inicial basada en digitalTwinData
  Object.keys(digitalTwinData).forEach((role) => {
    const countStr = digitalTwinData[role].current;
    const count = parseInt(countStr) || 0;
    spawnPeopleInRole(role, count);
  });
}

function spawnPeopleInRole(role, count, reservations = []) {
  if (!model) return;

  // Limpiar instancias previas si existen (Para que desaparezcan en horas de cierre)
  if (peopleInstances[role]) {
    scene.remove(peopleInstances[role]);
    peopleInstances[role] = null;
    peopleStates[role] = [];
  }

  if (count <= 0) return;

  // Recolectar superficies de tránsito...
  let roleMeshes = [];
  model.traverse((c) => {
    if (c.isMesh && c.userData.role === role && !c.userData.isHitBox) {
      const name = (c.name || "").toLowerCase();
      const isStructural =
        name.includes("techo") ||
        name.includes("roof") ||
        name.includes("viga") ||
        name.includes("truss") ||
        name.includes("lamina") ||
        name.includes("columna") ||
        name.includes("muro") ||
        name.includes("pared");

      if (!isStructural) {
        roleMeshes.push(c);
      }
    }
  });

  if (roleMeshes.length === 0) {
    model.traverse((c) => {
      if (c.isMesh && c.userData.role === role && !c.userData.isHitBox) roleMeshes.push(c);
    });
  }

  if (role === "pool") {
    const waterNames = ["agua","water","piscina","bowl","piscine","fluid","ocean","surface"];
    const waterOnly = roleMeshes.filter((m) => {
      const n = (m.name || "").toLowerCase();
      return waterNames.some((w) => n.includes(w));
    });
    if (waterOnly.length > 0) roleMeshes = waterOnly;
    else {
      roleMeshes.sort((a, b) => {
        const aBox = new THREE.Box3().setFromObject(a);
        const bBox = new THREE.Box3().setFromObject(b);
        const aArea = (aBox.max.x - aBox.min.x) * (aBox.max.z - aBox.min.z);
        const bArea = (bBox.max.x - bBox.min.x) * (bBox.max.z - bBox.min.z);
        return bArea - aArea;
      });
      roleMeshes = [roleMeshes[0]];
    }
  }

  if (roleMeshes.length === 0) return;

  const meshInfo = roleMeshes
    .map((mesh) => {
      const box = new THREE.Box3().setFromObject(mesh);
      const size = box.getSize(new THREE.Vector3());
      const name = (mesh.name || "").toLowerCase();
      const area = Math.max(1, size.x * size.z);
      const flatness = area / Math.max(1, size.y);
      const floorName =
        name.includes("piso") ||
        name.includes("suelo") ||
        name.includes("floor") ||
        name.includes("mat") ||
        name.includes("plataforma");
      return { mesh, box, size, area, score: flatness + (floorName ? area * 4 : 0) };
    })
    .filter((item) => item.area > 150);

  if (meshInfo.length === 0) {
    console.warn(`[LIVE SYNC] No se encontró superficie visible para ${role}`);
    return;
  }

  meshInfo.sort((a, b) => b.score - a.score);
  const spawnSurfaces = meshInfo.slice(0, Math.min(3, meshInfo.length));
  console.info(`[LIVE SYNC] Pintando ${count} personas en ${role}`, {
    surfaces: spawnSurfaces.map((s) => ({
      name: s.mesh.name,
      area: Math.round(s.area),
      y: Number(s.box.max.y.toFixed(2)),
    })),
  });

  const instMesh = new THREE.InstancedMesh(
    peopleGeometry,
    peopleMaterial.clone(),
    count,
  );
  instMesh.instanceColor = new THREE.InstancedBufferAttribute(new Float32Array(count * 3), 3);
  instMesh.userData.role = role;
  instMesh.frustumCulled = false;
  instMesh.renderOrder = 50;

  // --- LÓGICA DE COLOREADO POR ESTADO DE RESERVA ---
  // Confirmada = azul, pendiente/no confirmada = amarillo.
  let peopleColors = [];
  reservations.forEach(res => {
      const isConfirmed = res.status === "confirmed";
      const color = isConfirmed ? new THREE.Color(0x22d3ee) : new THREE.Color(0xfacc15);
      for(let g=0; g < res.guests; g++) {
          if (peopleColors.length < count) peopleColors.push(color);
      }
  });

  // Rellenar si faltan (por si hay inconsistencia)
  while(peopleColors.length < count) {
      peopleColors.push(new THREE.Color(0xfacc15)); // Default: pendiente/no confirmada
  }

  const dummy = new THREE.Object3D();
  peopleStates[role] = [];

  for (let i = 0; i < count; i++) {
    const surface = spawnSurfaces[i % spawnSurfaces.length];
    const meshBox = surface.box;
    const size = surface.size;
    const center = meshBox.getCenter(new THREE.Vector3());

    const columns = Math.ceil(Math.sqrt(count));
    const row = Math.floor(i / columns);
    const col = i % columns;
    const gridX = columns === 1 ? 0 : (col / (columns - 1) - 0.5);
    const gridZ = columns === 1 ? 0 : (row / (columns - 1) - 0.5);
    const jitterX = (Math.random() - 0.5) * Math.min(10, size.x * 0.08);
    const jitterZ = (Math.random() - 0.5) * Math.min(10, size.z * 0.08);
    const x = center.x + gridX * size.x * 0.42 + jitterX;
    const z = center.z + gridZ * size.z * 0.42 + jitterZ;
    const yOffset = role === "pool" ? 2.0 : 8.0;
    const pos = new THREE.Vector3(x, meshBox.max.y + yOffset, z);
    const dir = new THREE.Vector3(Math.random()-0.5, 0, Math.random()-0.5).normalize();
    const speed = 0.05 + Math.random() * 0.08;
    const scale = 1.15 + Math.random() * 0.45;

    peopleStates[role].push({
      pos, dir, speed, scale,
      bounds: {
        min: { x: center.x - size.x * 0.38, z: center.z - size.z * 0.38 },
        max: { x: center.x + size.x * 0.38, z: center.z + size.z * 0.38 },
      },
    });

    dummy.position.copy(pos);
    if (role === "pool") dummy.rotation.set(Math.PI / 2, Math.atan2(dir.x, dir.z), 0);
    else dummy.rotation.y = Math.atan2(dir.x, dir.z);
    dummy.scale.set(scale, scale, scale);
    dummy.updateMatrix();
    instMesh.setMatrixAt(i, dummy.matrix);
    
    // Aplicar color por estado de reserva
    instMesh.setColorAt(i, peopleColors[i]);
  }

  instMesh.instanceMatrix.needsUpdate = true;
  if (instMesh.instanceColor) instMesh.instanceColor.needsUpdate = true;
  scene.add(instMesh);
  peopleInstances[role] = instMesh;
}

// Gestión del Cierre de Tarjeta (Botón X)
document.addEventListener("click", (e) => {
  if (e.target.id === "close-card") {
    if (infoCard) infoCard.classList.add("hidden");
  }
});

// --- INICIALIZACIÓN DE SENSORES ARDUINO ---
function initSensors() {
  if (!model) return;

  // Función auxiliar para hallar el centro de un rol específico (IGNORANDO TECHOS)
  const getFloorCenter = (roleName) => {
    const box = new THREE.Box3();
    let found = false;
    model.traverse((child) => {
      if (child.isMesh && child.userData.role === roleName) {
        const name = (child.name || "").toLowerCase();
        // Ignorar techos y estructuras elevadas para hallar el nivel del suelo real
        if (
          !name.includes("techo") &&
          !name.includes("roof") &&
          !name.includes("viga")
        ) {
          box.expandByObject(child);
          found = true;
        }
      }
    });
    if (!found) return null;
    const center = new THREE.Vector3();
    box.getCenter(center);
    return center;
  };

  const sensorGeo = new THREE.BoxGeometry(4, 4, 4);
  const createSensor = (worldPos, role, color) => {
    const localPos = worldPos.clone();
    model.worldToLocal(localPos); // TRANSFORMACIÓN CRÍTICA: Mundo -> Modelo Local

    const sensorGroup = new THREE.Group();
    const mat = new THREE.MeshStandardMaterial({
      color: color,
      emissive: color,
      emissiveIntensity: 6.0,
      metalness: 0.8,
      roughness: 0.2,
    });
    const mesh = new THREE.Mesh(sensorGeo, mat);
    mesh.userData.role = role;
    mesh.userData.highlightColor = new THREE.Color(0xffffff);

    const base = new THREE.Mesh(
      new THREE.BoxGeometry(10, 2, 10),
      new THREE.MeshStandardMaterial({ color: 0x111111, metalness: 0.9 }),
    );
    base.position.y = -3;

    sensorGroup.add(mesh);
    sensorGroup.add(base);
    sensorGroup.position.copy(localPos);
    model.add(sensorGroup);
  };

  // 1. Sensor Bosque: Al alejarse mucho en el terreno (X negativo)
  const pos2 = new THREE.Vector3();
  const pos3 = new THREE.Vector3();
  let has2 = false;
  let has3 = false;

  // 1. Obtener posición del Nodo 2 (Canchas)
  const canchaBox = new THREE.Box3();
  let foundCanchas = false;
  model.traverse(c => {
    if (c.isMesh && c.userData.role === 'canchas' && !c.userData.isHitBox) {
        canchaBox.expandByObject(c);
        foundCanchas = true;
    }
  });
  if (foundCanchas) {
    canchaBox.getCenter(pos2);
    const size = new THREE.Vector3();
    canchaBox.getSize(size);
    pos2.x += (size.x / 2) + 10;
    pos2.y = 2;
    createSensor(pos2, "sensor2", 0xfbbf24);
    has2 = true;
  }

  // 2. Obtener posición del Nodo 3 (Alberca)
  const poolCenter = getFloorCenter("pool");
  if (poolCenter) {
    pos3.copy(poolCenter);
    pos3.z += 100;
    pos3.y = 2;
    createSensor(pos3, "sensor3", 0x22d3ee);
    has3 = true;
  }

  // 3. Posicionar Nodo 1 (Bosque) en el punto medio entre 2 y 3
  if (has2 && has3) {
    const midPoint = new THREE.Vector3().lerpVectors(pos2, pos3, 0.5);
    // Lo movemos un poco hacia afuera (eje Z o X) para que no quede pegado al edificio si están muy alineados
    midPoint.x -= 40; 
    midPoint.y = 2;
    createSensor(midPoint, "sensor1", 0x10b981);
  } else {
    // Fallback por si alguno falla
    createSensor(new THREE.Vector3(-550, 2, -450), "sensor1", 0x10b981);
  }
}

// --- SISTEMA DE ETIQUETAS ESPACIALES REFORZADO ---
function initSpatialLabels() {
  if (!labelsContainer || !model) return;
  labelsContainer.innerHTML = "";
  spatialLabels.length = 0; // Limpiar lista

  const targets = [
    { role: "gym", label: "🏢 Gimnasio", color: "gym", yOffset: 130 },
    { role: "pool", label: "🌊 Centro Acuático", color: "pool", yOffset: 130 },
    {
      role: "canchas",
      label: "⚽ Canchas",
      color: "canchas",
      yOffset: 160,
    },
    {
      role: "sensor1",
      label: "📡 Nodo 01 - Bosque",
      color: "sensor",
      yOffset: 45, // Más bajo para que se vea sobre el cubo
    },
    {
      role: "sensor2",
      label: "📡 Nodo 02 - Canchas",
      color: "sensor",
      yOffset: 45,
    },
    {
      role: "sensor3",
      label: "📡 Nodo 03 - Alberca",
      color: "sensor",
      yOffset: 45,
    },
  ];

  targets.forEach((t) => {
    const box = new THREE.Box3();
    let found = false;
    let bestMesh = null;
    let maxVertices = -1;

    let childWithRole = null;

    // Buscamos mallas que tengan el rol asociado
    model.traverse((child) => {
      if (child.isMesh && child.userData.role === t.role) {
        childWithRole = child; // Guardamos uno de referencia
        if (t.role === "canchas") {
           // Filtro de geometría para evitar basura flotante
           const vCount = child.geometry.attributes.position.count;
           if (vCount > maxVertices) {
              maxVertices = vCount;
              bestMesh = child;
           }
        } else {
           box.expandByObject(child);
           found = true;
        }
      }
    });

    if (t.role === "canchas" && bestMesh) {
       box.setFromObject(bestMesh);
       found = true;
    }

    if (found) {
      const center = new THREE.Vector3();
      box.getCenter(center);

      // Ajuste manual para canchas si el centro sale desviado por el GLB
      if (t.role === "canchas") {
          center.y = 5;
      }
      
      // Si es un sensor, usamos directamente su posición de mundo para mayor precisión
      if (t.role.includes('sensor') && childWithRole) {
          childWithRole.getWorldPosition(center);
      }

      const el = document.createElement("div");
      el.className = `holo-label ${t.color}`;
      el.innerHTML = `<span>${t.label}</span>`;
      el.dataset.role = t.role;

      // Etiquetas ahora son grandes en todos los tamaños (Petición usuario)
      if (window.innerWidth < 800) {
        el.style.fontSize = "12px";
        el.style.padding = "8px 16px";
      }

      el.onclick = (e) => {
        e.stopPropagation();
        showInfoCard(t.role);
        updateFocus(t.role);
      };

      labelsContainer.appendChild(el);
      
      // Guardamos la posición base calculada y el offset
      spatialLabels.push({ 
        el, 
        basePos: center.clone(), // Posición central real
        yOffset: t.yOffset,
        role: t.role
      });
    }
  });

  // Una vez creadas las etiquetas, forzamos una sincronización para aplicar estados (Abierto/Cerrado)
  syncDBCounts();
}

function updateSpatialLabelsStatus(zoneStatuses) {
  spatialLabels.forEach(labelObj => {
    const role = labelObj.el.dataset.role;
    if (role && zoneStatuses[role]) {
      const isClosed = zoneStatuses[role] === 'closed';
      if (isClosed) {
        labelObj.el.classList.add('closed');
        const span = labelObj.el.querySelector('span');
        if (span && !span.innerText.includes('🚫')) {
            span.innerText = '🚫 ' + span.innerText;
        }
      } else {
        labelObj.el.classList.remove('closed');
        const span = labelObj.el.querySelector('span');
        if (span) span.innerText = span.innerText.replace('🚫 ', '');
      }
    }
  });
}

// Inicializar sensores, etiquetas y activos se movió dentro de initModels()

function initAssets() {
  const assets = [
    { id: "asset-pump-1", pos: { x: -80, y: 5, z: 280 }, color: 0x00f2ff },
    { id: "asset-gym-1", pos: { x: 320, y: 15, z: -180 }, color: 0xff3300 },
    { id: "asset-light-1", pos: { x: -350, y: 10, z: -150 }, color: 0xffff00 }
  ];

  assets.forEach(a => {
    const geo = new THREE.SphereGeometry(4, 16, 16);
    const mat = new THREE.MeshStandardMaterial({ 
      color: a.color,
      emissive: a.color,
      emissiveIntensity: 2,
      transparent: true,
      opacity: 0.8
    });
    const mesh = new THREE.Mesh(geo, mat);
    mesh.position.set(a.pos.x, a.pos.y, a.pos.z);
    mesh.userData.role = a.id;
    mesh.userData.isAsset = true;
    
    // Animación de pulso inteligente
    const pulse = () => {
      // Simulamos un chequeo de salud (puedes conectar esto a tu DB después)
      const isCritical = a.id === "asset-pump-1"; // Ejemplo: forzamos alerta en la bomba 1
      const color = isCritical ? 0xffa500 : a.color;
      mesh.material.color.setHex(color);
      mesh.material.emissive.setHex(color);

      const speed = isCritical ? 0.015 : 0.005;
      const scaleBase = isCritical ? 1.5 : 1.2;
      const s = 1 + Math.sin(performance.now() * speed) * (scaleBase - 1);
      
      mesh.scale.set(s, s, s);
      requestAnimationFrame(pulse);
    };
    pulse();

    if (model) {
      model.add(mesh);
    } else {
      scene.add(mesh); // Backup si el grupo model no existe
    }
  });
}

// --- EXPORTACIÓN GLOBAL ---
window.updateFocus = updateFocus;
window.enterShowroom = enterShowroom;
window.exitShowroom = exitShowroom;
window.runSimulation = runSimulation;
window.syncDBCounts = syncDBCounts;
