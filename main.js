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
const clock = new THREE.Clock();
const container = document.getElementById("container");
const txtHour = document.getElementById("txt-hour");

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
controls.dampingFactor = 0.05;
controls.maxDistance = 3500;
controls.minDistance = 100;
// Restringimos la cámara para que nunca baje del horizonte y se vea el abismo
controls.maxPolarAngle = Math.PI / 2.1;

// Iluminación
const ambientLight = new THREE.AmbientLight(0xffffff, 0.4);
scene.add(ambientLight);

const dirLight = new THREE.DirectionalLight(0xfff5e6, 0.7);
dirLight.position.set(100, 200, 50);
scene.add(dirLight);

const fillLight = new THREE.DirectionalLight(0xaabbff, 0.3);
fillLight.position.set(-100, 50, -50);
scene.add(fillLight);

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
      const name = child.name ? child.name.toLowerCase() : "";
      if (!child.userData.explodeConfigured) {
        // El techo sube el doble de alto que el resto de componentes (160 unidades locales)
        if (name.includes("Estructura") || name.includes("lamina")) {
          child.userData.explodeOffset = 40;
          child.userData.originalY = child.position.y;
          child.traverse((c) => (c.userData.explodeConfigured = true));
        }
        // Gimnasio y alberca suben normal (mitad de altura)
        else if (name.includes("gym") || name.includes("gimnasio")) {
          child.userData.explodeOffset = 20;
          child.userData.originalY = child.position.y;
          child.traverse((c) => (c.userData.explodeConfigured = true));
        }
      }
    });

    // Clasificar capas y respaldar materiales mediante herencia (cascade)
    model.traverse((child) => {
      // Heredar el rol del padre (útil porque los nombres suelen estar en los Grupos, no en el Mesh)
      if (child.parent && child.parent.userData.role) {
        child.userData.role = child.parent.userData.role;
      }

      // Asignar rol según el nombre del nodo actual
      const name = child.name.toLowerCase();

      // Categorías primarias (fuerzan su rol)
      if (name.includes("gym") || name.includes("gimnasio"))
        child.userData.role = "gym";
      else if (name.includes("alberca") || name.includes("pool"))
        child.userData.role = "pool";
      else if (
        name.includes("techo") ||
        name.includes("roof") ||
        name.includes("techumbre") ||
        name.includes("lamina")
      )
        child.userData.role = "roof";
      else if (name.includes("cancha")) child.userData.role = "canchas";
      // Categorías secundarias (sólo se aplican si no han heredado ya un rol principal)
      else if (
        !child.userData.role &&
        (name.includes("muro") ||
          name.includes("columna") ||
          name.includes("planta baja") ||
          name.includes("puertas") ||
          name.includes("moviliario") ||
          name.includes("case") ||
          name.includes("administracion") ||
          name.includes("estructura") ||
          name.includes("terreno"))
      ) {
        child.userData.role = "structure";
      }

      if (child.isMesh) {
        // Localizar si esta pieza es parte del "Terreno"
        let pt = child;
        let isGrass = false;
        while (pt) {
          if (pt.name && pt.name.toLowerCase().includes("terreno")) {
            isGrass = true;
            break;
          }
          pt = pt.parent;
        }

        let originalMat;
        if (isGrass && treeGrassMaterial) {
          // Asignar literalmente TODO el material del árbol (textura/color/shading)
          originalMat = treeGrassMaterial.clone();
        } else {
          originalMat =
            child.material.clone() ||
            new THREE.MeshStandardMaterial({ color: 0xeeeeee });

          // Forzar el color base limpio y mate a la arquitectura
          if (originalMat.color) {
            originalMat.color.setHex(0xeeeeee);
          }
          originalMat.roughness = 1.0; // Totalmente mate
          originalMat.metalness = 0.0; // Cero brillo metálico
        }

        // Por defecto no es transparente para evitar que parezca "fantasma" o se pierda profundidad
        originalMat.transparent = false;

        child.userData.originalMaterial = originalMat;

        // Crear el material de "trabajo"
        child.material = originalMat.clone();

        // Si el mesh no tuvo rol heredado ni por nombre, darle por defecto
        if (!child.userData.role) child.userData.role = "structure";

        // Asignar color de highlight según rol
        if (child.userData.role === "gym")
          child.userData.highlightColor = new THREE.Color(0xf43f5e); // rosa
        else if (child.userData.role === "pool")
          child.userData.highlightColor = new THREE.Color(0x0ea5e9); // azul
        else if (child.userData.role === "roof")
          child.userData.highlightColor = new THREE.Color(0x10b981); // verde
        else if (child.userData.role === "canchas")
          child.userData.highlightColor = new THREE.Color(0xfbbf24); // amarillo
        else child.userData.highlightColor = new THREE.Color(0x6366f1); // indigo (structure y otros)
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

    // Arrancar controles y ocultar loader
    initLayerControls();
    initWeatherControls();
    document.getElementById("loader-overlay").classList.add("hidden");
  } catch (error) {
    console.error("❌ Loader Error:", error);
    const loaderText = document.querySelector(".loader-text");
    if (loaderText) loaderText.innerText = "Error cargando los modelos";
  }
};

initModels();

// Lógica de Capas (Filtrado)
function initLayerControls() {
  const btns = document.querySelectorAll(".layer-btn");

  const updateFocus = (mode) => {
    if (!model) return;

    const transitionDuration = 0.5; // Segundos (para animaciones futuras, aquí lo hacemos directo)

    model.traverse((child) => {
      if (!child.isMesh) return;

      const role = child.userData.role;
      const mat = child.material;
      const originalVal = child.userData.originalMaterial;

      // Modo Mostrar Todo
      if (mode === "all") {
        mat.color.copy(originalVal.color);
        mat.emissive.setHex(0x000000);
        mat.opacity = 1.0;
        mat.wireframe = false;
        mat.transparent = false; // Opaco en modo "All"
        return;
      }

      // Modo Específico (Filtro)
      if (role === mode) {
        // Capa activa -> Brillo y color
        mat.color.copy(child.userData.highlightColor);
        mat.emissive.copy(child.userData.highlightColor);
        // Como el umbral de Bloom ahora es 20.0, el brillo interno debe ser mayor a 20 para resplandecer
        mat.emissiveIntensity = 25.0;
        mat.opacity = 1.0;
        mat.wireframe = false;
        mat.transparent = false; // Opaco cuando está resaltado
      } else {
        // Capas inactivas -> Transparentes pero manteniendo su color original
        mat.color.copy(originalVal.color);
        mat.emissive.setHex(0x000000);
        mat.opacity = 0.2;
        mat.wireframe = false;
        mat.transparent = true;
      }
    });
  };

  btns.forEach((btn) => {
    btn.addEventListener("click", () => {
      // Actualizar UI
      btns.forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");

      // Leer data-layer
      const mode = btn.getAttribute("data-layer");
      updateFocus(mode);
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
  controls.update();

  updateAtmosphere(); // Sincroniza Sol, Clima y Hora CDMX

  // Pulsación suave para las capas activas (emissive > 1) y Vista Explosionada
  if (model) {
    const time = clock.getElapsedTime();
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
