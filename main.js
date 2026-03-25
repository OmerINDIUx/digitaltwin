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

// --- SISTEMA DE POBLACIÓN (Simulación de Personas) ---
const peopleInstances = {
  gym: null,
  pool: null,
  canchas: null
};
const peopleGeometry = new THREE.CapsuleGeometry(2.5, 6.0, 4, 8); // Gigante Digital para visibilidad
const peopleMaterial = new THREE.MeshStandardMaterial({
  color: 0x22d3ee, 
  emissive: 0x22d3ee,
  emissiveIntensity: 4.0, // Brillo neón intenso
  transparent: true,
  opacity: 1.0, 
  metalness: 0,
  roughness: 0.5
});

// Estados para movimiento de personas
const peopleStates = {
    gym: [],
    pool: [],
    canchas: []
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
    trend: [40, 60, 85, 70, 50, 30] 
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
    trend: [20, 30, 45, 90, 80, 60]
  },
  canchas: {
    title: "Área de Canchas",
    current: "36 personas",
    expected: "120 hoy",
    status: "Ocupación Máxima",
    statusClass: "status-danger",
    temp: "31.2°C",
    hum: "30%",
    maint: "Próximo: 05 Abr",
    hours: "08:00 - 20:00",
    trend: [10, 40, 70, 100, 90, 50]
  }

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
controls.rotateSpeed = 1.2;    // Rotación más rápida
controls.zoomSpeed = 1.5;      // Zoom más potente
controls.maxDistance = 5000;   // Más espacio para alejarse
controls.minDistance = 30;     // Poder entrar hasta el detalle
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
    side: THREE.DoubleSide
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
const bloomPass = new UnrealBloomPass(
  new THREE.Vector2(window.innerWidth, window.innerHeight),
  1.5,
  0.4,
  0.85,
);
// Subimos el umbral muchísimo (20.0) porque el Cielo Procedural genera colores muy brillantes (> 5.0) en formato HDR.
// Al subir el umbral, el Bloom ignorará el cielo (excepto el sol) y ya no sobreexpondrá la pantalla.
bloomPass.threshold = 20.0;
bloomPass.strength = 0.25;
bloomPass.radius = 0.5;

const composer = new EffectComposer(renderer);
composer.addPass(renderScene);
composer.addPass(bloomPass);
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

        if (cName.includes("gimnasio") || cName.includes("gym")) {
          category = "gym";
          break;
        }
        if (cName.includes("alberca") || cName.includes("pool")) {
          category = "pool";
          break;
        }
        if (cName.includes("estructura") || cName.includes("structure")) {
          category = "estructura";
          break;
        }
        if (cName.includes("administracion")) {
          category = "admin";
          break;
        }
        if (cName.includes("canchas")) {
          category = "canchas";
          break;
        }
        if (cName.includes("terreno")) {
          category = "terreno";
          break;
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
      // 2. GIMNASIO -> Sube 20 todo el bloque junto
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
      while(p) {
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
          originalMat = child.material.clone() || new THREE.MeshStandardMaterial({ color: 0xeeeeee });
          if (originalMat.color) {
            originalMat.color.setHex(0xeeeeee);
          }
          originalMat.roughness = 1.0;
          originalMat.metalness = 0.0;
        }

        originalMat.transparent = false;
        child.userData.originalMaterial = originalMat;
        child.material = originalMat.clone();

        if (!child.userData.role) child.userData.role = "structure";

        // Ajustar colores de highlight finales según rol asignado
        if (child.userData.role === "gym") child.userData.highlightColor = new THREE.Color(0xf43f5e);
        else if (child.userData.role === "pool") child.userData.highlightColor = new THREE.Color(0x0ea5e9);
        else if (child.userData.role === "roof") child.userData.highlightColor = new THREE.Color(0x10b981);
        else if (child.userData.role === "canchas") child.userData.highlightColor = new THREE.Color(0xfbbf24);
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
    const isSelected = (role === mode);
    const originalMaterial = child.userData.originalMaterial;
    const materials = Array.isArray(child.material) ? child.material : [child.material];

    materials.forEach((mat) => {
      // SIEMPRE restaurar propiedades básicas primero
      mat.wireframe = false;
      
      if (mode === "all" || mode === "completo" || !mode) {
        // MODO NORMAL - Todo Visible
        if (originalMaterial) mat.color.copy(originalMaterial.color);
        mat.emissive.setHex(0x000000);
        mat.opacity = 1.0;
        mat.transparent = false;
        child.visible = true;
      } else {
        // MODO FILTRADO
        if (role === 'roof') {
          // X-RAY SIMPLE: Ocultar techumbre si estamos enfocados en algo
          child.visible = false;
        } else if (isSelected) {
          // RESALTADO ACTIVO
          mat.color.copy(child.userData.highlightColor);
          mat.emissive.copy(child.userData.highlightColor);
          mat.emissiveIntensity = 1.5; // Brillo suave, no encandila
          mat.opacity = 1.0;
          mat.transparent = false;
          child.visible = true;
        } else {
          // EL RESTO: Transparencia suave para contexto
          if (originalMaterial) mat.color.copy(originalMaterial.color);
          mat.emissive.setHex(0x000000);
          mat.transparent = true;
          mat.opacity = 0.3; // Visible pero secundario
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
    cameraTargetPos.copy(center).add(new THREE.Vector3(maxDim * cameraOffset, maxDim * (cameraOffset * 0.8), maxDim * cameraOffset));
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
      floatingLabel.querySelector("#label-name").innerText = digitalTwinData[role]?.title || role;
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
      
      // Mostrar la tarjeta si es una zona de interés (Gym, Pool, Canchas)
      if (mode === "gym" || mode === "pool" || mode === "canchas") {
          showInfoCard(mode);
      } else {
          infoCard.classList.add("hidden");
          if (floatingLabel) floatingLabel.classList.add("hidden");
      }
    });

  });
}

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
  
  // Efecto 'Pulso de Vida' y MOVIMIENTO ACENTUADO
  const pulseEmissive = 2.0 + Math.sin(Date.now() * 0.005) * 1.5;
  const bobbing = Math.sin(Date.now() * 0.003) * 0.8; // Bobbing balanceo más visible
  
  Object.keys(peopleInstances).forEach(role => {
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
              
              dummy.position.set(p.pos.x, p.pos.y + bobbing, p.pos.z);
              
              if (role === 'pool') {
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

  // Posicionamiento de Info Card 3D
  if (infoCard && !infoCard.classList.contains("hidden")) {
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
  }


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

  // Pulsación suave para las capas activas (emissive > 1) y Vista Explosionada
  if (model) {
    timer.update();
    const time = timer.getElapsed();
    model.traverse((child) => {
      // 1. Resplandor pulsante
      // Solo pulsamos aquellos elementos que tengan más de 20 de intensidad (las capas activas)
      if (child.isMesh && child.material.emissiveIntensity > 20.0) {
        // Oscilar entre 22 y 28 de intensidad
        const pulse = 25.0 + Math.sin(time * 3) * 3.0;
        child.material.emissiveIntensity = pulse;
      }

      // 2. Transición suave de la altura (Vista Explosionada)
      if (child.userData.explodeOffset !== undefined) {
        const targetY = isModelExploded
          ? child.userData.originalY + child.userData.explodeOffset
          : child.userData.originalY;
        // Interpolación (Lerp) para movimiento súper fluido
        child.position.y += (targetY - child.position.y) * 0.05;
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
    if (intersects.length > 0) {
        let obj = intersects[0].object;
        let role = obj.userData.role;
        // Buscar herencia si es necesario
        let p = obj.parent;
        while(!role && p && p !== model) {
            role = p.userData.role;
            p = p.parent;
        }

        if (role && (role === 'gym' || role === 'pool' || role === 'canchas')) {
            currentHoverRole = role;
        }
    }

    if (currentHoverRole !== hoveredRole) {
        hoveredRole = currentHoverRole;
        container.style.cursor = hoveredRole ? "pointer" : "default";

        // Aplicar brillo suave de hover a todos los objetos del rol
        model.traverse(child => {
            if (child.isMesh && child.userData.role) {
                const materials = Array.isArray(child.material) ? child.material : [child.material];
                materials.forEach(mat => {
                    if (child.userData.role === hoveredRole) {
                        mat.emissive.copy(child.userData.highlightColor || new THREE.Color(0xffffff));
                        mat.emissiveIntensity = 0.4; // Brillo suave de pre-selección
                    } else {
                        // Solo resetear si no es la zona activamente enfocada por click
                        if (!child.userData.isSelectedInFocus) {
                            mat.emissive.setHex(0x000000);
                        }
                    }
                });
            }
        });
    }
}

function onMouseClick(event) {
  // Solo procesar si fue una interacción rápida y sin movimiento (Punto a Punto) para no bloquear la navegación
  const duration = Date.now() - clickStartTime;
  const dist = Math.hypot(event.clientX - clickStartX, event.clientY - clickStartY);
  
  // Si tardó más de 250ms o se movió más de 5px, es una navegación, NO un clic de selección
  if (duration > 250 || dist > 5) return;

  if (event.target.tagName !== "CANVAS") return;

  mouse.x = (event.clientX / window.innerWidth) * 2 - 1;
  mouse.y = -(event.clientY / window.innerHeight) * 2 + 1;

  raycaster.setFromCamera(mouse, camera);

  if (model) {
    const intersects = raycaster.intersectObject(model, true);

    if (intersects.length > 0) {
      // Buscar el rol del objeto golpeado (o su padre)
      let selectedObject = intersects[0].object;
      let role = selectedObject.userData.role;

      // Si no tiene rol directo, buscar en ancestros (similar a la lógica de carga)
      let parent = selectedObject.parent;
      while (!role && parent && parent !== model) {
        role = parent.userData.role;
        parent = parent.parent;
      }

      if (role && digitalTwinData[role]) {
        // Limpiar estado previo
        model.traverse(c => { if(c.isMesh) c.userData.isSelectedInFocus = false; });
        
        showInfoCard(role);
        // Marcar como seleccionado para que el hover no lo limpie
        model.traverse(c => { if(c.isMesh && c.userData.role === role) c.userData.isSelectedInFocus = true; });
        
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

  if(titleEl) titleEl.innerText = data.title;
  if(peopleEl) peopleEl.innerText = data.current.split(' ')[0]; // Solo el número para el diseño premium
  if(expectedEl) expectedEl.innerText = data.expected.split(' ')[0];
  if(tempEl) tempEl.innerText = data.temp;
  if(humEl) humEl.innerText = data.hum;
  if(maintEl) maintEl.innerText = data.maint;
  if(hoursEl) hoursEl.innerText = data.hours;
  if(statusEl) statusEl.innerText = data.status;

  // Manejo de Alerta (Solo si el estado es crítico/danger como en las canchas)
  if (alertBanner) {
      if (data.statusClass === "status-danger") {
          alertBanner.classList.add("active");
      } else {
          alertBanner.classList.remove("active");
      }
  }

  // Actualizar Gráfico de Tendencia (Animación de barras)
  const bars = document.querySelectorAll(".trend-bar");
  if (bars.length > 0 && data.trend) {
      bars.forEach((bar, i) => {
          if (data.trend[i]) {
              const val = data.trend[i];
              bar.style.height = val + "%";
              // Resaltar la barra más alta
              if (val > 80) bar.classList.add("high");
              else bar.classList.remove("high");
          }
      });
  }

  if(infoCard) infoCard.classList.remove("hidden");
}






window.addEventListener("mousedown", onMouseDown);
window.addEventListener("click", onMouseClick);
window.addEventListener("mousemove", onMouseMove);

if (controls) {
  // Solo la interacción HUMANA real interrumpe el movimiento automático
  // Usamos wheel y mousedown/pointerdown directos porque controls 'change' se disparaba solo
  window.addEventListener('wheel', () => isCameraMoving = false, { passive: true });
  window.addEventListener('pointerdown', (e) => {
      // Si el usuario toca el canvas con intención, liberamos cámara
      if (e.target.tagName === "CANVAS") isCameraMoving = false;
  });
}
function initPopulation() {
    // Generar población inicial basada en digitalTwinData
    Object.keys(digitalTwinData).forEach(role => {
        const countStr = digitalTwinData[role].current;
        const count = parseInt(countStr) || 0;
        spawnPeopleInRole(role, count);
    });
}

function spawnPeopleInRole(role, count) {
    if (!model || count <= 0) return;

    // Recolectar superficies de tránsito (Menos techumbres y estructuras elevadas)
    let roleMeshes = [];
    model.traverse(c => {
        if (c.isMesh && c.userData.role === role) {
            const name = (c.name || "").toLowerCase();
            const isStructural = name.includes("techo") || name.includes("roof") || name.includes("viga") || 
                                name.includes("truss") || name.includes("lamina") || name.includes("columna");

            // Si no parece un techo elevado, lo tomamos como posible suelo
            if (!isStructural) {
                roleMeshes.push(c);
            }
        }
    });

    // SISTEMA DE EMERGENCIA: Si no encontramos 'suelos' obvios, tomamos cualquier malla del rol
    if (roleMeshes.length === 0) {
        model.traverse(c => {
            if (c.isMesh && c.userData.role === role) roleMeshes.push(c);
        });
    }

    // --- CORRECCIÓN ESPECÍFICA PARA ALBERCA: SOLO AGUA ---
    if (role === 'pool') {
        const waterNames = ["agua", "water", "piscina", "bowl", "piscine", "fluid", "ocean", "surface"];
        const waterOnly = roleMeshes.filter(m => {
            const n = (m.name || "").toLowerCase();
            return waterNames.some(w => n.includes(w));
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

    const instMesh = new THREE.InstancedMesh(peopleGeometry, peopleMaterial.clone(), count);
    instMesh.userData.role = role;
    
    const color = role === 'canchas' ? new THREE.Color(0xfbbf24) : new THREE.Color(0x22d3ee);
    instMesh.material.color.copy(color);
    instMesh.material.emissive.copy(color);

    const dummy = new THREE.Object3D();
    peopleStates[role] = [];

    for (let i = 0; i < count; i++) {
        // Seleccionar una pieza de suelo al azar de ese rol (Ejs: Cancha 1, Cancha 2, Piso Gym)
        const targetMesh = roleMeshes[Math.floor(Math.random() * roleMeshes.length)];
        const meshBox = new THREE.Box3().setFromObject(targetMesh);
        const size = meshBox.getSize(new THREE.Vector3());
        const center = meshBox.getCenter(new THREE.Vector3());

        // Generar posición con margen de seguridad del 20% para NO tocar paredes
        const rx = (Math.random() - 0.5) * (size.x * 0.8);
        const rz = (Math.random() - 0.5) * (size.z * 0.8);
        
        // Altura corregida: Sumergidos para pool (nado) vs sobre el piso para otros
        const yOffset = role === "pool" ? 0.3 : 4.0;
        const pos = new THREE.Vector3(center.x + rx, meshBox.min.y + yOffset, center.z + rz);
        const dir = new THREE.Vector3(Math.random() - 0.5, 0, Math.random() - 0.5).normalize();
        const speed = 0.05 + Math.random() * 0.08;
        const scale = 1.0 + Math.random() * 0.5;

        peopleStates[role].push({ 
            pos, 
            dir, 
            speed, 
            scale, 
            bounds: { 
                min: { x: center.x - (size.x * 0.38), z: center.z - (size.z * 0.38) },
                max: { x: center.x + (size.x * 0.38), z: center.z + (size.z * 0.38) }
            } 
        });
        
        dummy.position.copy(pos);
        if (role === 'pool') {
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

