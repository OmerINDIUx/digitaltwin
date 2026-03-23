import "./style.css";
import * as THREE from "three";
import { OrbitControls } from "three/examples/jsm/controls/OrbitControls.js";
import { GLTFLoader } from "three/examples/jsm/loaders/GLTFLoader.js";
import { DRACOLoader } from "three/examples/jsm/loaders/DRACOLoader.js";
import { EffectComposer } from "three/examples/jsm/postprocessing/EffectComposer.js";
import { RenderPass } from "three/examples/jsm/postprocessing/RenderPass.js";
import { UnrealBloomPass } from "three/examples/jsm/postprocessing/UnrealBloomPass.js";
import { MeshSurfaceSampler } from "three/examples/jsm/math/MeshSurfaceSampler.js";

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
scene.background = new THREE.Color(0x1a1a1a); // bg-light-gray
// scene.fog = new THREE.FogExp2(0xf1f5f9, 0.0008); // Eliminamos la neblina

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
const ambientLight = new THREE.AmbientLight(0xffffff, 0.4);
scene.add(ambientLight);

const dirLight = new THREE.DirectionalLight(0xffffff, 0.7);
dirLight.position.set(100, 200, 50);
scene.add(dirLight);

const fillLight = new THREE.DirectionalLight(0xaabbff, 0.3);
fillLight.position.set(-100, 50, -50);
scene.add(fillLight);

// Helpers eliminados para un look más limpio (daylight)

// Postprocesamiento (Bloom / Resplandor Neón)
const renderScene = new RenderPass(scene, camera);
const bloomPass = new UnrealBloomPass(
	new THREE.Vector2(window.innerWidth, window.innerHeight),
	1.5,
	0.4,
	0.85,
);
bloomPass.threshold = 0.5;
bloomPass.strength = 0.1; // Menos intensidad para encajar con el estilo claro
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

		// Generar bosque usando InstancedMesh sobre Terrenos
		model.updateMatrixWorld(true);

		// Zonas prohibidas para los árboles
		const forbiddenBoxes = [];
		model.traverse((child) => {
			const name = child.name ? child.name.toLowerCase() : "";
			if (!child.userData.boxAdded && (
				name.includes("gimnasio") || name.includes("gym") ||
				name.includes("alberca") || name.includes("pool") ||
				name.includes("estructura") || name.includes("administracion") ||
				name.includes("cancha") || name.includes("techo")
			)) {
				const box = new THREE.Box3().setFromObject(child);
				// Extender la caja hacia arriba/abajo al infinito para medir escudo en 2D (footprint XZ)
				box.min.y = -Infinity;
				box.max.y = Infinity;
				// Dar un margen de seguridad de 3.5 unidades para que las ramas no invadan
				box.min.x -= 3.5; box.max.x += 3.5;
				box.min.z -= 3.5; box.max.z += 3.5;
				forbiddenBoxes.push(box);
				
				// Evitar duplicar las cajas para sub-hijos
				child.traverse(c => c.userData.boxAdded = true);
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
			const numTreesPerType = 150; // Total 450 árboles

			trees.forEach((treeGltf) => {
				const treeMeshes = [];
				treeGltf.scene.traverse((c) => {
					if (c.isMesh) treeMeshes.push(c);
				});

				const instancedMeshes = treeMeshes.map((tm) => {
					const mat = tm.material.clone();
					mat.transparent = true;
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
						// Escalar a un tamaño razonable
						const s = (Math.random() * 0.5 + 0.8) * 1.5;
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

		// Configurar cámara
		camera.position.set(targetSize * 0.8, targetSize * 0.6, targetSize * 0.8);
		camera.lookAt(0, 0, 0);

		// Arrancar controles y ocultar loader
		initLayerControls();
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
				mat.transparent = true;
				return;
			}

			// Modo Específico (Filtro)
			if (role === mode) {
				// Capa activa -> Brillo y color
				mat.color.copy(child.userData.highlightColor);
				mat.emissive.copy(child.userData.highlightColor);
				mat.emissiveIntensity = 1.0;
				mat.opacity = 1.0;
				mat.wireframe = false;
				mat.transparent = true;
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
