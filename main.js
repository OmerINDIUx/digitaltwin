import "./style.css";
import * as THREE from "three";
import { OrbitControls } from "three/examples/jsm/controls/OrbitControls.js";
import { GLTFLoader } from "three/examples/jsm/loaders/GLTFLoader.js";
import { DRACOLoader } from "three/examples/jsm/loaders/DRACOLoader.js";
import { EffectComposer } from "three/examples/jsm/postprocessing/EffectComposer.js";
import { RenderPass } from "three/examples/jsm/postprocessing/RenderPass.js";
import { UnrealBloomPass } from "three/examples/jsm/postprocessing/UnrealBloomPass.js";

// --- CONFIGURACIÓN GLOBAL ---
let model;
const clock = new THREE.Clock();
const container = document.getElementById("container");

// Escena y Renderizador
const renderer = new THREE.WebGLRenderer({
	antialias: true,
	alpha: true,
	powerPreference: "high-performance",
});
renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2)); // Optimizado
renderer.setSize(window.innerWidth, window.innerHeight);
renderer.toneMapping = THREE.ACESFilmicToneMapping;
renderer.toneMappingExposure = 0.8;
renderer.outputColorSpace = THREE.SRGBColorSpace;
container.appendChild(renderer.domElement);

const scene = new THREE.Scene();
scene.background = new THREE.Color(0x020617); // bg-dark
scene.fog = new THREE.FogExp2(0x020617, 0.0008);

const camera = new THREE.PerspectiveCamera(
	45,
	window.innerWidth / window.innerHeight,
	0.1,
	10000,
);

const controls = new OrbitControls(camera, renderer.domElement);
controls.enableDamping = true;
controls.dampingFactor = 0.05;
controls.maxDistance = 2000;

// Iluminación
const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
scene.add(ambientLight);

const dirLight = new THREE.DirectionalLight(0xffffff, 1.2);
dirLight.position.set(100, 200, 50);
scene.add(dirLight);

const fillLight = new THREE.DirectionalLight(0xaabbff, 0.5);
fillLight.position.set(-100, 50, -50);
scene.add(fillLight);

// Cuadrícula y helpers (Opcional, para estilo tech)
const gridHelper = new THREE.GridHelper(2000, 100, 0x0ea5e9, 0x1e293b);
gridHelper.position.y = -5;
scene.add(gridHelper);

// Postprocesamiento (Bloom / Resplandor Neón)
const renderScene = new RenderPass(scene, camera);
const bloomPass = new UnrealBloomPass(
	new THREE.Vector2(window.innerWidth, window.innerHeight),
	1.5,
	0.4,
	0.85,
);
bloomPass.threshold = 0.2;
bloomPass.strength = 0.2;
bloomPass.radius = 0.5;

const composer = new EffectComposer(renderer);
composer.addPass(renderScene);
composer.addPass(bloomPass);

// Carga del GLTF
const loader = new GLTFLoader();
const dracoLoader = new DRACOLoader();
dracoLoader.setDecoderPath(
	"https://www.gstatic.com/draco/versioned/decoders/1.5.7/",
);
loader.setDRACOLoader(dracoLoader);

loader.load(
	"/japonutopia_capasrenovadas.glb",
	(gltf) => {
		model = gltf.scene;

		// Centrar modelo
		const box = new THREE.Box3().setFromObject(model);
		const size = box.getSize(new THREE.Vector3());
		const center = box.getCenter(new THREE.Vector3());
		const maxDim = Math.max(size.x, size.y, size.z);

		model.position.sub(center);

		// Escalar modelo para que quepa bien en la vista
		const targetSize = 500;
		const scaleFactor = targetSize / (maxDim || 1);
		model.scale.set(scaleFactor, scaleFactor, scaleFactor);

		// Clasificar capas y respaldar materiales mediante herencia (cascade)
		model.traverse((child) => {
			// Heredar el rol del padre (útil porque los nombres suelen estar en los Grupos, no en el Mesh)
			if (child.parent && child.parent.userData.role) {
				child.userData.role = child.parent.userData.role;
			}

			// Asignar rol según el nombre del nodo actual
			const name = child.name.toLowerCase();

			// Categorías primarias (fuerzan su rol)
			if (name.includes("gym") || name.includes("gimnasio")) child.userData.role = "gym";
			else if (name.includes("alberca") || name.includes("pool"))
				child.userData.role = "pool";
			else if (name.includes("techo") || name.includes("roof") || name.includes("techumbre") || name.includes("lamina"))
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
				// Respaldar original
				const originalMat =
					child.material.clone() ||
					new THREE.MeshStandardMaterial({ color: 0xe2e8f0 });
				
				// Aplicar el color claro al material principal
				if (originalMat.color) {
					originalMat.color.setHex(0xe2e8f0); // Color claro (tipo Slate 200)
				}
				
				originalMat.transparent = true;
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

		// Configurar cámara
		camera.position.set(targetSize * 0.8, targetSize * 0.6, targetSize * 0.8);
		camera.lookAt(0, 0, 0);

		// Arrancar controles y ocultar loader
		initLayerControls();
		document.getElementById("loader-overlay").classList.add("hidden");
	},
	undefined,
	(error) => {
		console.error("❌ Loader Error:", error);
		document.querySelector(".loader-text").innerText =
			"Error cargando el modelo";
	},
);

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
				mat.transparent = true;
				return;
			}

			// Modo Específico (Filtro)
			if (role === mode) {
				// Capa activa -> Brillo y color neón
				mat.color.copy(child.userData.highlightColor);
				mat.emissive.copy(child.userData.highlightColor);
				mat.emissiveIntensity = 2.0; // Bloom lo hará brillar
				mat.opacity = 1.0;
				mat.wireframe = false;
				mat.transparent = true;
			} else {
				// Capas inactivas -> Oscuras y transparentes tipo holograma plano
				mat.color.setHex(0x1e293b); // Slate oscuro
				mat.emissive.setHex(0x000000);
				mat.opacity = 0.15;
				mat.wireframe = true;
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

// Bucle de Animación
function animate() {
	requestAnimationFrame(animate);
	controls.update();

	// Pulsación suave para las capas activas (emissive > 1)
	if (model) {
		const time = clock.getElapsedTime();
		model.traverse((child) => {
			if (child.isMesh && child.material.emissiveIntensity > 1.0) {
				const pulse = 1.5 + Math.sin(time * 3) * 0.5;
				child.material.emissiveIntensity = pulse;
			}
		});
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
