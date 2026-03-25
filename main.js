import "./style.css";
import * as THREE from "three";
import { OrbitControls } from "three/examples/jsm/controls/OrbitControls.js";
import { GLTFLoader } from "three/examples/jsm/loaders/GLTFLoader.js";
import { DRACOLoader } from "three/examples/jsm/loaders/DRACOLoader.js";
import { EffectComposer } from "three/examples/jsm/postprocessing/EffectComposer.js";
import { RenderPass } from "three/examples/jsm/postprocessing/RenderPass.js";
import { UnrealBloomPass } from "three/examples/jsm/postprocessing/UnrealBloomPass.js";
import { OutputPass } from "three/examples/jsm/postprocessing/OutputPass.js";
import { MeshSurfaceSampler } from "three/examples/jsm/math/MeshSurfaceSampler.js";
import { Sky } from "three/examples/jsm/objects/Sky.js";

// --- CONFIGURACIÓN GLOBAL ---
let model;
let rain;
let clouds;
let currentWeatherType = "normal";
const timer = new THREE.Timer();
timer.update();

// Posiciones de Control de Cámara
let selectionRing;
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

// --- SISTEMA DE POBLACIÓN (Simulación de Personas) ---
const peopleInstances = {
  gym: null,
  pool: null,
  canchas: null,
};
const peopleGeometry = new THREE.CapsuleGeometry(2.5, 6.0, 4, 8); // Gigante Digital para visibilidad
const peopleMaterial = new THREE.MeshStandardMaterial({
  color: 0x22d3ee,
  emissive: 0x22d3ee,
  emissiveIntensity: 4.0, // Brillo neón intenso
  transparent: true,
  opacity: 1.0,
  metalness: 0,
  roughness: 0.5,
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
const infoCard = document.getElementById("info-card");
const cardTitle = document.getElementById("card-title");
const currentPeople = document.getElementById("current-people");
const expectedPeople = document.getElementById("expected-people");
const areaStatus = document.getElementById("area-status");
const closeCardBtn = document.getElementById("close-card");

const digitalTwinData = {
  gym: {
    title: "Gimnasio de Alto Rendimiento",
    current: "24 personas",
    expected: "85 hoy",
    status: "Operativo",
    statusClass: "status-good",
    temp: "22.5°C",
    hum: "45%",
    maint: "Próximo: 12 Abr",
    hours: "06:00 - 22:00",
    trend: [40, 60, 85, 70, 50, 30],
  },
  pool: {
    title: "Centro Acuático",
    current: "12 personas",
    expected: "40 hoy",
    status: "Limpieza en curso",
    statusClass: "status-warning",
    temp: "28.0°C",
    hum: "85%",
    maint: "En progreso",
    hours: "07:00 - 21:00",
    trend: [20, 30, 45, 90, 80, 60],
  },
  canchas: {
    title: "Área de Canchas",
    current: "36 personas",
    expected: "120 hoy",
    status: "Activo",
    statusClass: "status-good",
    temp: "31.2°C",
    hum: "40%",
    maint: "Próximo: 05 May",
    hours: "08:00 - 23:00",
    trend: [30, 50, 60, 95, 80, 70],
  },
  sensor1: {
    title: "Módulo IoT 01 - Bosque",
    isSensor: true,
    sensorType: "lidar",
    bat: "92%",
    specialLabel: "HUM. SUELO",
    specialVal: "48.2%",
    temp: "21.2°C",
    hum: "65%",
    status: "Transmisión LoRaWAN",
    statusClass: "status-good",
    diag1_label: "TIPO DE SUELO",
    diag1_val: "Arcilloso / Suelo 01",
    diag2_label: "ÍNDICE FOTOSÍNTESIS",
    diag2_val: "98.2% (Óptimo)",
    diag3_label: "RIESGO DE INCENDIO",
    diag3_val: "Bajo (5%)",
    diag_bar: 5,
  },
  sensor2: {
    title: "Módulo IoT 02 - Canchas",
    isSensor: true,
    sensorType: "lidar",
    bat: "85%",
    specialLabel: "SONIDO (dB)",
    specialVal: "72.4 dB",
    temp: "32.5°C",
    hum: "38%",
    status: "Malla Zigbee v3",
    statusClass: "status-good",
    diag1_label: "VIBRACIÓN DE IMPACTO",
    diag1_val: "Detectado (Red Activa)",
    diag2_label: "FRECUENCIA PICO",
    diag2_val: "440Hz / Peak",
    diag3_label: "NIVEL DE RUIDO",
    diag3_val: "Medio (72%)",
    diag_bar: 72,
  },
  sensor3: {
    title: "Módulo IoT 03 - Alberca",
    isSensor: true,
    sensorType: "uv",
    bat: "100%",
    specialLabel: "RAD. UV-EXT",
    specialVal: "9.2 Index",
    temp: "27.8°C",
    hum: "82%",
    status: "Canal 15 (MQTT)",
    statusClass: "status-good",
    diag1_label: "TRASLUCIDEZ AGUA",
    diag1_val: "94% (Óptimo)",
    diag2_label: "pH QUÍMICO / Cl",
    diag2_val: "pH: 7.2 | Cl: 1.5ppm",
    diag3_label: "EXPOSICIÓN UV",
    diag3_val: "Crítico (92%)",
    diag_bar: 92,
  },
};

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

// --- REJILLA DIGITAL (Digital Twin Look) ---
const gridHelper = new THREE.GridHelper(5000, 100, 0x3b82f6, 0x1e293b);
gridHelper.position.y = -1; // Ligeramente bajo el modelo
gridHelper.material.transparent = true;
gridHelper.material.opacity = 0.2;
scene.add(gridHelper);

// --- ANILLO DE SELECCIÓN HOLOGRÁFICO ---

const ringGeo = new THREE.RingGeometry(25, 28, 64);
const ringMat = new THREE.MeshBasicMaterial({
  color: 0x3b82f6,
  transparent: true,
  opacity: 0,
  side: THREE.DoubleSide,
});
selectionRing = new THREE.Mesh(ringGeo, ringMat);
selectionRing.rotation.x = -Math.PI / 2;
selectionRing.position.y = 0.5; // Justo arriba del suelo
scene.add(selectionRing);

// --- ETIQUETA FLOTANTE (HTML Overlay) ---
floatingLabel = document.createElement("div");
floatingLabel.className = "floating-label hidden";
floatingLabel.innerHTML = `
    <div class="label-content">
        <span id="label-name">Zona</span>
        <div class="label-pulse"></div>
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
// Eliminamos BloomPass incesario que causaba el efecto "incandescente"
const composer = new EffectComposer(renderer);
composer.addPass(renderScene);
const outputPass = new OutputPass();
composer.addPass(outputPass);

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
      loadGLTF("/japonutopia_capasrenovadas.glb"),
      loadGLTF("/tree_detailed_dark.glb"),
      loadGLTF("/tree_fat_darkh.glb"),
      loadGLTF("/tree_pineGroundA.glb"),
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

    // Centrar modelo
    const box = new THREE.Box3().setFromObject(model);
    const size = box.getSize(new THREE.Vector3());
    const center = box.getCenter(new THREE.Vector3());
    const maxDim = Math.max(size.x, size.y, size.z);

    model.position.sub(center);

    // Escalar modelo para que sea muchísimo más masivo visualmente
    const targetSize = 1500;
    const scaleFactor = targetSize / (maxDim || 1);
    model.scale.set(scaleFactor, scaleFactor, scaleFactor);

    // Bajar ligeramente el terreno y canchas para evitar interferencia (z-fighting) con TODOS los edificios
    model.traverse((child) => {
      const name = child.name ? child.name.toLowerCase() : "";
      if (
        !child.userData.raisedFixed &&
        (name.includes("terreno") || name.includes("cancha"))
      ) {
        // Al bajar la lona base, efectivamente todo lo demás (bloque 0, gym, alberca) se queda arriba
        child.position.y -= 1.5;
        child.updateMatrix();
        // Marcar sub-hijos para que no se bajen en cascada
        child.traverse((c) => (c.userData.raisedFixed = true));
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
        fullName.includes("voleibol") ||
        fullName.includes("voley")
      ) {
        child.userData.role = "canchas";
        child.userData.highlightColor = new THREE.Color(0xfbbf24); // Oro Pro
      }
      // Prioridad 3: Estructura general
      else if (
        fullName.includes("muro") ||
        fullName.includes("pared") ||
        fullName.includes("columna") ||
        fullName.includes("estructura") ||
        fullName.includes("acero") ||
        fullName.includes("viga") ||
        fullName.includes("administracion")
      ) {
        child.userData.role = "structure";
        child.userData.highlightColor = new THREE.Color(0x94a3b8);
      }

      if (child.isMesh) {
        // Localizar si esta pieza es parte del "Terreno"
        let isGrass = fullName.includes("terreno");

        let originalMat;
        if (isGrass && treeGrassMaterial) {
          originalMat = treeGrassMaterial.clone();
        } else {
          originalMat =
            child.material.clone() ||
            new THREE.MeshStandardMaterial({ color: 0xeeeeee });
          if (originalMat.color) {
            originalMat.color.setHex(0xeeeeee);
          }
          originalMat.roughness = 1.0;
          originalMat.metalness = 0.0;
        }

        // --- EFECTO GHOST/X-RAY PARA TECHOS Y ESTRUCTURA ---
        if (
          child.userData.role === "roof" ||
          child.userData.role === "structure"
        ) {
          originalMat.transparent = true;
          originalMat.opacity = 0.35;
        } else {
          originalMat.transparent = false;
        }

        child.userData.originalMaterial = originalMat;
        child.material = originalMat.clone();

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

    // Configurar cámara (Vista peatonal extrema al ras del suelo para que el cielo sea el protagonista)
    camera.position.set(
      targetSize * 0.35,
      targetSize * -0.045,
      targetSize * 0.35,
    );
    controls.target.set(0, targetSize * -0.02, 0);
    controls.update();

    // Arrancar controles, ocultar loader y poblar gemelo
    initLayoutControls();
    initWeatherControls();
    initPopulation();
    document.getElementById("loader-overlay").classList.add("hidden");
  } catch (error) {
    console.error("❌ Loader Error:", error);
    const loaderText = document.querySelector(".loader-text");
    if (loaderText) loaderText.innerText = "Error cargando los modelos";
  }
};

initModels();

// Lógica de Capas (Filtrado)
function updateFocus(mode) {
  if (!model) return;

  model.traverse((child) => {
    if (!child.isMesh) return;

    const role = child.userData.role;
    const isSelected = role === mode;
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
          // RESALTADO ACTIVO
          mat.color.copy(child.userData.highlightColor);
          mat.emissive.copy(child.userData.highlightColor);
          mat.emissiveIntensity = 1.5;
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
    if (selectionRing) selectionRing.material.opacity = 0;
  } else {
    focusCameraOnRole(mode);
  }
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
    const center = new THREE.Vector3();
    box.getCenter(center);
    const size = new THREE.Vector3();
    box.getSize(size);

    const maxDim = Math.max(size.x, size.y, size.z);

    // Configuración de Cámara: Retroceder más en canchas para captar todo el complejo
    const cameraOffset = role === "canchas" ? 1.8 : 1.5;
    cameraTargetPos
      .copy(center)
      .add(
        new THREE.Vector3(
          maxDim * cameraOffset,
          maxDim * (cameraOffset * 0.8),
          maxDim * cameraOffset,
        ),
      );
    controlsTargetPos.copy(center);

    // --- MEJORA VISUAL DE SELECCIÓN (EL ANILLO SE ADAPTA AL ESPACIO) ---
    if (selectionRing) {
      selectionRing.position.copy(center);
      selectionRing.position.y = 0.5; // Ras del suelo

      if (role === "canchas") {
        // Cambiar a MARCO RECTANGULAR que ocupa todo el espacio de las canchas
        if (selectionRing.geometry.type !== "PlaneGeometry") {
          selectionRing.geometry.dispose();
          selectionRing.geometry = new THREE.PlaneGeometry(1, 1);
        }
        // Escalar el plano para que coincida con el tamaño real de las canchas (con un pequeño margen del 10%)
        selectionRing.scale.set(size.x * 1.1, size.z * 1.1, 1);
        selectionRing.material.color.setHex(0xfbbf24); // Oro Intenso para deportes
      } else {
        // Círculo Clásico para edificios individuales (Gym/Pool)
        if (selectionRing.geometry.type !== "RingGeometry") {
          selectionRing.geometry.dispose();
          selectionRing.geometry = new THREE.RingGeometry(25, 28, 64);
        }
        const diskScale = (maxDim / 25) * 0.7;
        selectionRing.scale.set(diskScale, diskScale, 1);
        selectionRing.material.color.setHex(0x3b82f6); // Azul Corporativo
      }

      selectionRing.material.opacity = 0.8;
      selectionRing.material.transparent = true;
    }

    // Actualizar Etiqueta Flotante
    if (floatingLabel) {
      floatingLabel.dataset.target3d = JSON.stringify(center);
      floatingLabel.querySelector("#label-name").innerText =
        digitalTwinData[role]?.title || role;
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
        cameraTargetPos.set(0, 1500, 0);
        controlsTargetPos.set(0, 0, 0);
        isCameraMoving = true;
      } else if (preset === "walk") {
        btn.classList.add("active");
        cameraTargetPos.set(400, 10, 400);
        controlsTargetPos.set(0, 10, 0);
        isCameraMoving = true;
      } else if (preset === "pano") {
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
  }

  // Lógica del Modal de Reservas
  if (btnNewRes && resModal) {
    btnNewRes.addEventListener("click", () => {
      resModal.classList.remove("hidden");
      addFeedItem("Iniciando proceso de reserva segura", "info");
    });

    resModal.addEventListener("click", (e) => {
      if (e.target === resModal || e.target.closest("#close-res-modal")) {
        resModal.classList.add("hidden");
      }
    });
  }

  if (resForm) {
    resForm.addEventListener("submit", (e) => {
      e.preventDefault();
      const data = {
        zone: document.getElementById("res-zone").value,
        datetime: document.getElementById("res-datetime").value,
        guests: document.getElementById("res-guests").value,
      };

      const subBtn = resForm.querySelector(".prime-btn");
      const originalText = subBtn.innerText;
      subBtn.innerText = "SINCRONIZANDO CON MYSQL...";
      subBtn.disabled = true;

      // --- CONEXIÓN REAL CON LARAGON / PHP ---
      fetch("api_reservas.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
      })
        .then((res) => res.json())
        .then((response) => {
          if (response.status === "success") {
            addFeedItem(
              `¡Registro guardado en MySQL! Zona: ${data.zone.toUpperCase()}`,
              "success",
            );

            // Actualizar historial visualmente
            const historyList = document.getElementById("res-history-list");
            if (historyList) {
              const item = document.createElement("div");
              item.className = "mini-item";
              item.innerHTML = `<strong>${data.zone.toUpperCase()}</strong> - ${new Date(data.datetime).toLocaleString()}`;
              historyList.prepend(item);
            }

            subBtn.innerText = "¡RESERVA EXITOSA!";
            setTimeout(() => {
              resModal.classList.add("hidden");
              subBtn.innerText = originalText;
              subBtn.disabled = false;
              resForm.reset();
            }, 1000);
          } else {
            throw new Error(response.message || "Error al guardar");
          }
        })
        .catch((error) => {
          console.error("Error DB:", error);
          addFeedItem(
            "Fallo de conexión con Laragon: Registrando Localmente",
            "warning",
          );

          // Fallback local si el PHP no está listo
          const historyList = document.getElementById("res-history-list");
          if (historyList) {
            const item = document.createElement("div");
            item.className = "mini-item";
            item.innerHTML = `<strong>(LOCAL) ${data.zone.toUpperCase()}</strong> - ${new Date(data.datetime).toLocaleTimeString()}`;
            historyList.prepend(item);
          }

          subBtn.innerText = originalText;
          subBtn.disabled = false;
        });
    });
  }
}

function loadReservationsFromDB() {
  const historyList = document.getElementById("res-history-list");
  if (!historyList) return;

  historyList.innerHTML =
    '<div class="mini-item">Consultando Base de Datos Laragon...</div>';

  fetch("api_reservas.php")
    .then((res) => res.json())
    .then((data) => {
      if (Array.isArray(data) && data.length > 0) {
        historyList.innerHTML = "";
        data.forEach((res) => {
          const item = document.createElement("div");
          item.className = "mini-item";
          const dateStr = new Date(res.reservation_date).toLocaleString([], {
            month: "short",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit",
          });
          item.innerHTML = `<strong>${res.zone.toUpperCase()}</strong> - ${dateStr} <small>(DB)</small>`;
          historyList.appendChild(item);
        });
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
    if (bar) bar.style.width = `${Math.min(val * 1.5, 100)}%`;
    if (valTxt) valTxt.innerText = `${val} Pax`;
  });

  const avgTemp = (totalTemp / count).toFixed(1);

  // Animación de conteo simple
  animateValue("dash-total-people", 0, total, 1000);
  const tempEl = document.getElementById("dash-avg-temp");
  if (tempEl) tempEl.innerText = `${avgTemp}°C`;

  updateClock();
}

function updateClock() {
  const timeEl = document.getElementById("dash-time-val");
  const dateEl = document.getElementById("dash-date-val");
  if (!timeEl || !dateEl) return;

  const now = new Date();
  timeEl.innerText = now.toLocaleTimeString([], { hour12: false });
  dateEl.innerText = now.toLocaleDateString([], {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
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
  const hour = now.getHours();
  const minute = now.getMinutes();
  const decimalHour = hour + minute / 60;

  if (txtHour) {
    txtHour.innerText = `${hour.toString().padStart(2, "0")}:${minute.toString().padStart(2, "0")} (CDMX)`;
  }

  // Mapear hora al Sol (Simulación realista)
  // 6:00 (Amanecer, Elev 0), 12:00 (Cenit, Elev 90), 18:00 (Ocaso, Elev 0)
  let elevation = Math.sin(((decimalHour - 6) * Math.PI) / 12) * 90;

  // Si es modo SOLEADO (Caluroso), forzamos que el sol esté siempre alto (mínimo 15 grados)
  // para que el cielo siempre se vea azul profundo y no naranja de atardecer.
  if (currentWeatherType === "sunny") {
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
  ambientLight.intensity = Math.max(0.15, lightIntensity * 0.4);
  dirLight.position.set(
    sun.x * 2000,
    Math.max(200, sun.y * 2000),
    sun.z * 2000,
  );
}

function initWeatherControls() {
  const btns = document.querySelectorAll(".weather-btn");

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

  btns.forEach((btn) => {
    btn.addEventListener("click", () => {
      btns.forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");
      currentWeatherType = btn.dataset.weather;

      // Control de lluvia
      if (currentWeatherType === "rainy") {
        rain.material.opacity = 0.6;
      } else {
        rain.material.opacity = 0;
      }
    });
  });
}

// Bucle de Animación
function animate() {
  requestAnimationFrame(animate);

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
      inst.material.emissiveIntensity = pulseEmissive;
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

  // Animación del Anillo
  if (selectionRing && selectionRing.material.opacity > 0) {
    selectionRing.rotation.z += 0.01;
    const s = 1.0 + Math.sin(Date.now() * 0.005) * 0.1;
    selectionRing.scale.x = selectionRing.userData.baseScale * s;
    selectionRing.scale.y = selectionRing.userData.baseScale * s;
  }

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

  controls.update();

  updateAtmosphere(); // Sincroniza Sol, Clima y Hora CDMX

  // Pulsación suave y Vista Explosionada Sincronizada
  if (model) {
    timer.update();
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

  composer.render(); // Usar composer en lugar de renderer normal para el efecto Bloom

  // --- ACTUALIZAR ETIQUETAS ESPACIALES (3D a 2D) ---
  spatialLabels.forEach((lbl) => {
    const vector = lbl.pos.clone();
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

      const dist = camera.position.distanceTo(lbl.pos);
      const scale = Math.max(0.5, Math.min(1.0, 750 / dist));
      lbl.el.style.transform = `translate(-50%, -50%) scale(${scale})`;
      lbl.el.style.opacity = Math.max(0.1, Math.min(1.0, 950 / dist));
    }
  });
}

animate();

// Responsivo
window.addEventListener("resize", () => {
  camera.aspect = window.innerWidth / window.innerHeight;
  camera.updateProjectionMatrix();
  renderer.setSize(window.innerWidth, window.innerHeight);
  composer.setSize(window.innerWidth, window.innerHeight);
});

// INTERACTIVIDAD: HOVER Y CLICK
let hoveredRole = null;

function onMouseMove(event) {
  if (event.target.tagName !== "CANVAS") return;

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
      if (child.isMesh && child.userData.role) {
        const materials = Array.isArray(child.material)
          ? child.material
          : [child.material];
        materials.forEach((mat) => {
          if (
            child.userData.role === hoveredRole &&
            !child.userData.isSelectedInFocus
          ) {
            mat.emissive.copy(
              child.userData.highlightColor || new THREE.Color(0x3b82f6),
            );
            mat.emissiveIntensity = 1.0;
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

  if (event.target.tagName !== "CANVAS") return;

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
        // Restaurar si se toca el suelo u otra cosa: MODO DESANCLAR
        updateFocus("all");

        const btns = document.querySelectorAll(".layer-btn");
        btns.forEach((b) => {
          b.classList.remove("active");
          if (b.dataset.layer === "all") b.classList.add("active");
        });
      }
    } else {
      infoCard.classList.add("hidden");
    }
  }
}

function showInfoCard(role) {
  if (!digitalTwinData[role]) return;
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

    if (peopleEl)
      peopleEl.innerText = data.current ? data.current.split(" ")[0] : "0";
    if (expectedEl)
      expectedEl.innerText = data.expected ? data.expected.split(" ")[0] : "0";
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

function addFeedItem(text, type = "") {
  const container = document.getElementById("notification-container");
  const notifList = document.getElementById("notif-list");
  const badge = document.getElementById("notif-badge");
  const panel = document.getElementById("notif-panel");

  if (!container) return;

  // 1. Crear el Toast (Notificación momentánea)
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

  // Auto-eliminar toast
  setTimeout(() => {
    toast.classList.add("hidden");
    setTimeout(() => toast.remove(), 400);
  }, 5000);

  // 2. Agregar al Historial (Panel Desplegable)
  if (notifList) {
    const item = document.createElement("div");
    item.className = `notif-item ${type}`;
    item.innerHTML = `
            <div class="notif-item-header">
                <span>GESTIÓN DE OPERACIÓN</span>
                <span>${timeStr}</span>
            </div>
            <div class="notif-item-body">${text}</div>
        `;
    notifList.prepend(item);

    // 3. Mostrar punto rojo si el panel está cerrado
    if (badge && panel && panel.classList.contains("hidden")) {
      badge.classList.remove("hidden");
    }
  }
}

// Control del Panel de Notificaciones
const bell = document.getElementById("notif-bell");
const notifPanel = document.getElementById("notif-panel");
const notifBadge = document.getElementById("notif-badge");

if (bell && notifPanel) {
  bell.addEventListener("click", (e) => {
    e.stopPropagation();
    notifPanel.classList.toggle("hidden");

    // Al abrir, quitar el punto rojo
    if (!notifPanel.classList.contains("hidden")) {
      if (notifBadge) notifBadge.classList.add("hidden");
    }
  });

  // Cerrar al hacer clic fuera
  document.addEventListener("click", (e) => {
    if (!notifPanel.contains(e.target) && e.target !== bell) {
      notifPanel.classList.add("hidden");
    }
  });
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
  window.addEventListener("wheel", () => (isCameraMoving = false), {
    passive: true,
  });
  window.addEventListener("pointerdown", (e) => {
    // Si el usuario toca el canvas con intención, liberamos cámara
    if (e.target.tagName === "CANVAS") isCameraMoving = false;
  });
}
function initPopulation() {
  // Generar población inicial basada en digitalTwinData
  Object.keys(digitalTwinData).forEach((role) => {
    const countStr = digitalTwinData[role].current;
    const count = parseInt(countStr) || 0;
    spawnPeopleInRole(role, count);
  });
}

function spawnPeopleInRole(role, count) {
  if (!model || count <= 0) return;

  // Recolectar superficies de tránsito (Menos techumbres y estructuras elevadas)
  let roleMeshes = [];
  model.traverse((c) => {
    if (c.isMesh && c.userData.role === role) {
      const name = (c.name || "").toLowerCase();
      const isStructural =
        name.includes("techo") ||
        name.includes("roof") ||
        name.includes("viga") ||
        name.includes("truss") ||
        name.includes("lamina") ||
        name.includes("columna");

      // Si no parece un techo elevado, lo tomamos como posible suelo
      if (!isStructural) {
        roleMeshes.push(c);
      }
    }
  });

  // SISTEMA DE EMERGENCIA: Si no encontramos 'suelos' obvios, tomamos cualquier malla del rol
  if (roleMeshes.length === 0) {
    model.traverse((c) => {
      if (c.isMesh && c.userData.role === role) roleMeshes.push(c);
    });
  }

  // --- CORRECCIÓN ESPECÍFICA PARA ALBERCA: SOLO AGUA ---
  if (role === "pool") {
    const waterNames = [
      "agua",
      "water",
      "piscina",
      "bowl",
      "piscine",
      "fluid",
      "ocean",
      "surface",
    ];
    const waterOnly = roleMeshes.filter((m) => {
      const n = (m.name || "").toLowerCase();
      return waterNames.some((w) => n.includes(w));
    });
    // Si encontramos la malla del agua, ignoramos todo lo demás (muros, pasillos)
    if (waterOnly.length > 0) {
      roleMeshes = waterOnly;
    } else {
      // Si no hay mallas de agua nominativas, intentar por la de mayor superficie plana
      roleMeshes.sort((a, b) => {
        const aBox = new THREE.Box3().setFromObject(a);
        const bBox = new THREE.Box3().setFromObject(b);
        const aArea = (aBox.max.x - aBox.min.x) * (aBox.max.z - aBox.min.z);
        const bArea = (bBox.max.x - bBox.min.x) * (bBox.max.z - bBox.min.z);
        return bArea - aArea;
      });
      roleMeshes = [roleMeshes[0]]; // Tomamos la plataforma más grande del área
    }
  }

  if (roleMeshes.length === 0) return;

  // Crear/Limpiar InstancedMesh
  if (peopleInstances[role]) {
    scene.remove(peopleInstances[role]);
  }

  const instMesh = new THREE.InstancedMesh(
    peopleGeometry,
    peopleMaterial.clone(),
    count,
  );
  instMesh.userData.role = role;

  const color =
    role === "canchas" ? new THREE.Color(0xfbbf24) : new THREE.Color(0x22d3ee);
  instMesh.material.color.copy(color);
  instMesh.material.emissive.copy(color);

  const dummy = new THREE.Object3D();
  peopleStates[role] = [];

  for (let i = 0; i < count; i++) {
    // Seleccionar una pieza de suelo al azar de ese rol (Ejs: Cancha 1, Cancha 2, Piso Gym)
    const targetMesh =
      roleMeshes[Math.floor(Math.random() * roleMeshes.length)];
    const meshBox = new THREE.Box3().setFromObject(targetMesh);
    const size = meshBox.getSize(new THREE.Vector3());
    const center = meshBox.getCenter(new THREE.Vector3());

    // Generar posición con margen de seguridad del 20% para NO tocar paredes
    const rx = (Math.random() - 0.5) * (size.x * 0.8);
    const rz = (Math.random() - 0.5) * (size.z * 0.8);

    // Altura corregida: Sumergidos para pool (nado) vs sobre el piso para otros
    const yOffset = role === "pool" ? 0.3 : 4.0;
    const pos = new THREE.Vector3(
      center.x + rx,
      meshBox.min.y + yOffset,
      center.z + rz,
    );
    const dir = new THREE.Vector3(
      Math.random() - 0.5,
      0,
      Math.random() - 0.5,
    ).normalize();
    const speed = 0.05 + Math.random() * 0.08;
    const scale = 1.0 + Math.random() * 0.5;

    peopleStates[role].push({
      pos,
      dir,
      speed,
      scale,
      bounds: {
        min: { x: center.x - size.x * 0.38, z: center.z - size.z * 0.38 },
        max: { x: center.x + size.x * 0.38, z: center.z + size.z * 0.38 },
      },
    });

    dummy.position.copy(pos);
    if (role === "pool") {
      dummy.rotation.set(Math.PI / 2, Math.atan2(dir.x, dir.z), 0);
    } else {
      dummy.rotation.y = Math.atan2(dir.x, dir.z);
    }
    dummy.scale.set(scale, scale, scale);
    dummy.updateMatrix();
    instMesh.setMatrixAt(i, dummy.matrix);
  }

  instMesh.instanceMatrix.needsUpdate = true;
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
  // El terreno suele estar en Y=0 o cerca.
  createSensor(new THREE.Vector3(-300, 2, -150), "sensor1", 0x10b981);

  // 2. Sensor Canchas: Pegado a la base de las canchas
  const courtsCenter = getFloorCenter("canchas");
  if (courtsCenter) {
    courtsCenter.x += 320; // Offset lateral
    courtsCenter.y = 2; // Justo sobre el plano
    createSensor(courtsCenter, "sensor2", 0xfbbf24);
  }

  // 3. Sensor Alberca: Pegado a la base de la alberca
  const poolCenter = getFloorCenter("pool");
  if (poolCenter) {
    poolCenter.z += 100; // Offset lateral
    poolCenter.y = 2; // Justo sobre el plano
    createSensor(poolCenter, "sensor3", 0x22d3ee);
  }
}

// --- SISTEMA DE ETIQUETAS ESPACIALES REFORZADO ---
function initSpatialLabels() {
  if (!labelsContainer || !model) return;
  labelsContainer.innerHTML = "";
  spatialLabels.length = 0; // Limpiar lista

  const targets = [
    { role: "gym", label: "🏢 Gimnasio", color: "gym", yOffset: 45 },
    { role: "pool", label: "🌊 Centro Acuático", color: "pool", yOffset: 45 },
    {
      role: "canchas",
      label: "⚽ Zona Deportiva",
      color: "canchas",
      yOffset: 70,
    },
    {
      role: "sensor1",
      label: "📡 Nodo 01 - Bosque",
      color: "sensor",
      yOffset: 25,
    },
    {
      role: "sensor2",
      label: "📡 Nodo 02 - Canchas",
      color: "sensor",
      yOffset: 25,
    },
    {
      role: "sensor3",
      label: "📡 Nodo 03 - Alberca",
      color: "sensor",
      yOffset: 25,
    },
  ];

  targets.forEach((t) => {
    const box = new THREE.Box3();
    let found = false;

    // Buscamos mallas que tengan el rol asociado
    model.traverse((child) => {
      if (child.isMesh && child.userData.role === t.role) {
        box.expandByObject(child);
        found = true;
      }
    });

    if (found) {
      const center = new THREE.Vector3();
      box.getCenter(center);

      // Estabilización para áreas masivas
      if (t.role === "canchas") center.y = 8;

      center.y += t.yOffset;

      const el = document.createElement("div");
      el.className = `holo-label ${t.color}`;
      el.innerHTML = `<span>${t.label}</span>`;
      el.onclick = () => {
        showInfoCard(t.role);
        updateFocus(t.role);
      };

      labelsContainer.appendChild(el);
      spatialLabels.push({ el, pos: center });
    }
  });
}

// Inicializar sensores y etiquetas con delays escalonados
setTimeout(initSensors, 2500);
setTimeout(initSpatialLabels, 4500);
