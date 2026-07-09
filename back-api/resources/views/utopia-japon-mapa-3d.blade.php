<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa 3D | UTOPÍA Japón</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800;900&display=swap');

        html, body {
            margin: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            font-family: 'Outfit', sans-serif;
            background: #f7fafc;
            color: #fff;
        }

        #viewer {
            position: fixed;
            inset: 0;
            background: linear-gradient(180deg, #f7fbff 0%, #ffffff 56%, #eef4f2 100%);
        }

        #labels {
            position: fixed;
            inset: 0;
            z-index: 3;
            pointer-events: none;
        }

        .map-label {
            position: absolute;
            transform: translate(-50%, -50%);
            display: none;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 999px;
            background: rgba(15, 23, 42, .76);
            padding: 10px 14px;
            color: #fff;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
            white-space: nowrap;
            box-shadow: 0 14px 40px rgba(0, 0, 0, .28);
            backdrop-filter: blur(12px);
            pointer-events: auto;
            cursor: pointer;
        }

        .map-label.is-active {
            display: block;
        }

        .map-label.is-selected {
            background: #7c3aed;
            border-color: rgba(255, 255, 255, .45);
        }

        .panel {
            position: fixed;
            z-index: 4;
            border: 1px solid rgba(255, 255, 255, .16);
            background: rgba(15, 23, 42, .72);
            box-shadow: 0 24px 80px rgba(0, 0, 0, .34);
            backdrop-filter: blur(18px);
        }

        .zone-button[aria-pressed="true"] {
            background: #7c3aed;
            color: #fff;
            border-color: rgba(255, 255, 255, .34);
        }

        .loading {
            position: fixed;
            inset: 0;
            z-index: 6;
            display: grid;
            place-items: center;
            padding: 24px;
            background:
                radial-gradient(circle at 72% 22%, rgba(124, 58, 237, .2), transparent 30%),
                linear-gradient(135deg, #dceeff, #efe8ff 58%, #c8f3ea);
        }

        .loading.is-hidden {
            display: none;
        }

        @media (max-width: 860px) {
            .panel-info {
                top: auto;
                bottom: 16px;
                left: 16px;
                right: 16px;
                width: auto;
            }

            .panel-help {
                display: none;
            }
        }
    </style>
</head>
<body>
    <canvas id="viewer" aria-label="Mapa 3D real de UTOPÍA Japón"></canvas>
    <div id="labels"></div>

    <div id="loading" class="loading">
        <div class="max-w-md rounded-3xl border border-white/15 bg-white/10 p-8 text-center shadow-2xl backdrop-blur">
            <p class="text-[10px] font-black uppercase tracking-[0.26em] text-violet-100">UTOPÍA Japón</p>
            <h1 class="mt-3 text-3xl font-black">Cargando mapa 3D real</h1>
            <p class="mt-3 text-sm font-semibold leading-6 text-white/72">
                Usando el modelo existente <strong>japonutopia_capasrenovadas.glb</strong>.
            </p>
        </div>
    </div>

    <header class="panel left-4 right-4 top-4 rounded-2xl px-4 py-3 md:left-6 md:right-auto md:w-[430px]">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.28em] text-violet-200">Mapa de orientación</p>
                <h1 class="text-2xl font-black leading-tight md:text-3xl">UTOPÍA Japón 3D</h1>
            </div>
            <a href="{{ url('/panel') }}" class="shrink-0 rounded-full bg-white px-4 py-2 text-xs font-black uppercase tracking-wide text-slate-950 transition hover:bg-slate-100">
                Panel
            </a>
        </div>
    </header>

    <aside class="panel panel-info bottom-4 left-4 right-4 rounded-2xl p-4 md:bottom-6 md:left-6 md:right-auto md:w-[390px]">
        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-violet-200">Cómo llegar dentro del complejo</p>
        <div id="zone-list" class="mt-3 grid grid-cols-2 gap-2"></div>
        <div class="mt-4 rounded-2xl bg-white/10 p-4">
            <h2 id="zone-title" class="text-lg font-black">Vista general</h2>
            <p id="zone-description" class="mt-1 text-sm font-semibold leading-6 text-white/72">
                Explora el modelo real de UTOPÍA Japón. Selecciona una zona para enfocar la cámara.
            </p>
        </div>
    </aside>

    <div class="panel panel-help right-6 top-24 rounded-2xl p-4 md:w-[280px]">
        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-violet-200">Controles</p>
        <p class="mt-3 text-sm font-semibold leading-6 text-white/72">
            Arrastra para girar, usa la rueda para acercarte y selecciona una etiqueta para ubicar un espacio.
        </p>
        <div class="mt-4 flex gap-2">
            <button id="reset-camera" class="rounded-full bg-white px-4 py-2 text-xs font-black uppercase tracking-wide text-slate-950 transition hover:bg-slate-100">Vista inicial</button>
            <button id="toggle-labels" class="rounded-full bg-violet-700 px-4 py-2 text-xs font-black uppercase tracking-wide text-white transition hover:bg-violet-800">Etiquetas</button>
        </div>
    </div>

    <script type="importmap">
        {
            "imports": {
                "three": "https://unpkg.com/three@0.183.2/build/three.module.js",
                "three/addons/": "https://unpkg.com/three@0.183.2/examples/jsm/"
            }
        }
    </script>
    <script type="module">
        import * as THREE from 'three';
        import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
        import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';
        import { DRACOLoader } from 'three/addons/loaders/DRACOLoader.js';

        const MODEL_URL = @json(route('utopias.japon.model'));
        const canvas = document.getElementById('viewer');
        const labelsRoot = document.getElementById('labels');
        const loading = document.getElementById('loading');

        const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: false });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.outputColorSpace = THREE.SRGBColorSpace;
        renderer.toneMapping = THREE.ACESFilmicToneMapping;
        renderer.toneMappingExposure = 1.35;
        renderer.shadowMap.enabled = true;
        renderer.shadowMap.type = THREE.PCFSoftShadowMap;

        const scene = new THREE.Scene();
        scene.background = new THREE.Color(0xf7fbff);
        scene.fog = new THREE.Fog(0xf7fbff, 2100, 6200);

        const camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, .1, 100000);
        camera.position.set(1240, 860, 1380);

        const controls = new OrbitControls(camera, renderer.domElement);
        controls.enableDamping = true;
        controls.dampingFactor = .08;
        controls.minDistance = 80;
        controls.maxDistance = 4200;
        controls.maxPolarAngle = Math.PI / 2.05;
        controls.target.set(0, 0, 0);

        scene.add(new THREE.HemisphereLight(0xffffff, 0xe9edf1, 3.1));
        scene.add(new THREE.AmbientLight(0xffffff, 1.2));

        const sun = new THREE.DirectionalLight(0xffffff, 3.2);
        sun.position.set(-720, 1300, 900);
        sun.castShadow = true;
        sun.shadow.mapSize.set(2048, 2048);
        sun.shadow.camera.near = 10;
        sun.shadow.camera.far = 4200;
        sun.shadow.camera.left = -1400;
        sun.shadow.camera.right = 1400;
        sun.shadow.camera.top = 1400;
        sun.shadow.camera.bottom = -1400;
        scene.add(sun);

        const fill = new THREE.DirectionalLight(0xdfefff, 1.8);
        fill.position.set(-520, 340, -460);
        scene.add(fill);

        const frontFill = new THREE.DirectionalLight(0xffffff, 1.35);
        frontFill.position.set(0, 420, 900);
        scene.add(frontFill);

        const ground = new THREE.Mesh(
            new THREE.CircleGeometry(1500, 96),
            new THREE.ShadowMaterial({ color: 0x9aa3ad, opacity: .18 })
        );
        ground.rotation.x = -Math.PI / 2;
        ground.position.y = -1.2;
        ground.receiveShadow = true;
        scene.add(ground);

        const loader = new GLTFLoader();
        const draco = new DRACOLoader();
        draco.setDecoderPath('https://www.gstatic.com/draco/versioned/decoders/1.5.7/');
        loader.setDRACOLoader(draco);

        const zones = [
            { id: 'overview', label: 'Vista general', description: 'Vista completa del mapa 3D real de UTOPÍA Japón.', target: [0, 0, 0], camera: [1240, 860, 1380], matchNames: [], markerHeight: 16 },
            { id: 'acceso', label: 'Accesos', description: 'Ubica la llegada al complejo y comienza el recorrido desde los accesos principales.', target: [-380, 0, 260], camera: [-680, 360, 520], matchNames: ['acceso', 'entrada'], markerHeight: 16 },
            { id: 'alberca', label: 'Centro acuático', description: 'Referencia para llegar a la zona de alberca y actividades acuáticas.', target: [-260, 0, -250], camera: [-620, 330, -80], matchNames: ['alberca', 'acuatico', 'pool'], markerHeight: 16 },
            { id: 'gimnasio', label: 'Gimnasio', description: 'Zona de actividad física y entrenamiento.', target: [230, 0, -220], camera: [620, 340, -60], matchNames: ['gimnasio', 'gym'], markerHeight: 42 },
            { id: 'canchas', label: 'Canchas', description: 'Área deportiva abierta para actividades y encuentros.', target: [380, 0, 220], camera: [760, 390, 520], matchNames: ['canchas', 'cancha', 'futbol', 'basquet'], markerHeight: 16 },
            { id: 'cultura', label: 'Casas culturales', description: 'Espacios para talleres, aprendizaje e intercambio comunitario.', target: [-70, 0, 330], camera: [320, 320, 720], matchNames: ['cultura', 'cultural', 'casa'], markerHeight: 16 },
            { id: 'cuidados', label: 'Cuidados', description: 'Servicios del Sistema Público de Cuidados dentro de la UTOPÍA.', target: [280, 0, 20], camera: [660, 330, 290], matchNames: ['cuidados', 'servicios'], markerHeight: 16 }
        ].map((zone) => ({
            ...zone,
            cameraOffset: zone.camera.map((value, index) => value - zone.target[index]),
        }));

        let model = null;
        let labelsVisible = true;
        let selectedZone = zones[0];
        const labelItems = [];
        const roofHints = ['lamina', 'techo', 'techumbre', 'cubierta', 'tela'];

        function fitModel(root) {
            const box = new THREE.Box3().setFromObject(root);
            const size = box.getSize(new THREE.Vector3());
            const center = box.getCenter(new THREE.Vector3());
            const maxDim = Math.max(size.x, size.y, size.z);
            const scale = 1500 / (maxDim || 1);
            root.scale.setScalar(scale);
            root.position.x -= center.x * scale;
            root.position.z -= center.z * scale;
            root.position.y -= box.min.y * scale;
            root.updateMatrixWorld(true);
        }

        function applyArchitecturalModelStyle(root) {
            root.traverse((child) => {
                if (!child.isMesh) return;

                child.castShadow = true;
                child.receiveShadow = true;
                const hierarchyName = getHierarchyName(child);
                const isRoof = roofHints.some((hint) => hierarchyName.includes(hint));

                child.material = new THREE.MeshStandardMaterial({
                    color: isRoof ? 0xdbeafe : 0xf2f3f5,
                    roughness: isRoof ? .35 : .86,
                    metalness: 0,
                    emissive: isRoof ? 0x93c5fd : 0xffffff,
                    emissiveIntensity: isRoof ? .18 : .06,
                    transparent: isRoof,
                    opacity: isRoof ? .04 : 1,
                    depthWrite: !isRoof,
                    side: THREE.DoubleSide
                });

                const geometry = child.geometry;
                if (!geometry || geometry.userData?.edgesCreated) return;

                const edges = new THREE.LineSegments(
                    new THREE.EdgesGeometry(geometry, 36),
                    new THREE.LineBasicMaterial({
                        color: isRoof ? 0x7dd3fc : 0x9ca3af,
                        transparent: true,
                        opacity: isRoof ? .1 : .42
                    })
                );
                edges.name = `${child.name || 'mesh'}_architectural_edges`;
                edges.renderOrder = 2;
                child.add(edges);
                geometry.userData.edgesCreated = true;
            });
        }

        function normalizeName(value) {
            return (value || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');
        }

        function getHierarchyName(object) {
            const parts = [];
            let current = object;

            while (current) {
                if (current.name) {
                    parts.push(current.name);
                }
                current = current.parent;
            }

            return normalizeName(parts.join(' '));
        }

        function getObjectWorldCenter(object) {
            const box = new THREE.Box3().setFromObject(object);
            const center = box.getCenter(new THREE.Vector3());

            if (!Number.isFinite(center.x) || !Number.isFinite(center.y) || !Number.isFinite(center.z)) {
                return object.getWorldPosition(new THREE.Vector3());
            }

            return center;
        }

        function resolveZoneAnchors(root) {
            const namedObjects = [];
            const terrainObject = root.getObjectByName('Terreno');

            root.traverse((child) => {
                if (!child.name) return;
                namedObjects.push({
                    object: child,
                    normalizedName: normalizeName(child.name),
                });
            });

            zones.forEach((zone) => {
                if (!zone.matchNames.length) return;

                const match = namedObjects.find(({ normalizedName }) =>
                    zone.matchNames.some((hint) => normalizedName.includes(normalizeName(hint)))
                );

                if (!match) return;

                const center = getObjectWorldCenter(match.object);
                zone.target = [center.x, center.y, center.z];
                zone.camera = zone.target.map((value, index) => value + zone.cameraOffset[index]);
            });

            const accessZone = zones.find((zone) => zone.id === 'acceso');
            if (accessZone && terrainObject) {
                const terrainBox = new THREE.Box3().setFromObject(terrainObject);
                const edgeX = terrainBox.min.x + (terrainBox.max.x - terrainBox.min.x) * .12;
                const edgeZ = terrainBox.min.z + (terrainBox.max.z - terrainBox.min.z) * .78;

                accessZone.target = [edgeX, terrainBox.max.y, edgeZ];
                accessZone.camera = accessZone.target.map((value, index) => value + accessZone.cameraOffset[index]);
            }
        }

        function createMarker(zone) {
            const group = new THREE.Group();
            group.position.set(zone.target[0], zone.target[1] + (zone.markerHeight || 16), zone.target[2]);

            const pin = new THREE.Mesh(
                new THREE.CylinderGeometry(10, 10, 7, 32),
                new THREE.MeshStandardMaterial({ color: 0xa78bfa, emissive: 0x3b0764, emissiveIntensity: .45 })
            );
            pin.castShadow = true;
            group.add(pin);

            const ring = new THREE.Mesh(
                new THREE.TorusGeometry(20, 2.5, 8, 36),
                new THREE.MeshBasicMaterial({ color: 0xc4b5fd, transparent: true, opacity: .75 })
            );
            ring.rotation.x = Math.PI / 2;
            group.add(ring);

            scene.add(group);

            const el = document.createElement('button');
            el.type = 'button';
            el.className = 'map-label';
            el.textContent = zone.label;
            el.addEventListener('click', () => selectZone(zone));
            labelsRoot.appendChild(el);

            labelItems.push({ zone, group, el });
        }

        function renderZoneButtons() {
            const list = document.getElementById('zone-list');
            list.innerHTML = '';
            zones.forEach((zone) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.dataset.zone = zone.id;
                button.className = 'zone-button rounded-xl border border-white/15 bg-white/8 px-3 py-3 text-left text-xs font-black uppercase tracking-wide text-white/82 transition hover:bg-white/14';
                button.textContent = zone.label;
                button.addEventListener('click', () => selectZone(zone));
                list.appendChild(button);
            });
        }

        function selectZone(zone) {
            selectedZone = zone;
            document.getElementById('zone-title').textContent = zone.label;
            document.getElementById('zone-description').textContent = zone.description;
            document.querySelectorAll('.zone-button').forEach((button) => {
                button.setAttribute('aria-pressed', button.dataset.zone === zone.id ? 'true' : 'false');
            });
            labelItems.forEach((item) => {
                item.el.classList.toggle('is-selected', item.zone.id === zone.id);
                item.group.scale.setScalar(item.zone.id === zone.id ? 1.45 : 1);
            });

            controls.target.set(zone.target[0], zone.target[1], zone.target[2]);
            camera.position.set(zone.camera[0], zone.camera[1], zone.camera[2]);
        }

        function updateLabels() {
            labelItems.forEach(({ zone, group, el }) => {
                const pos = group.position.clone();
                pos.y += 45;
                pos.project(camera);
                const visible = labelsVisible && pos.z < 1;
                el.classList.toggle('is-active', visible);
                if (visible) {
                    el.style.left = `${(pos.x * .5 + .5) * window.innerWidth}px`;
                    el.style.top = `${(-pos.y * .5 + .5) * window.innerHeight}px`;
                }
            });
        }

        renderZoneButtons();

        loader.load(
            MODEL_URL,
            (gltf) => {
                model = gltf.scene;
                fitModel(model);
                applyArchitecturalModelStyle(model);
                scene.add(model);
                resolveZoneAnchors(model);
                zones.filter((zone) => zone.id !== 'overview').forEach(createMarker);
                selectZone(zones[0]);
                loading.classList.add('is-hidden');
            },
            undefined,
            (error) => {
                console.error(error);
                loading.querySelector('h1').textContent = 'No se pudo cargar el modelo';
                loading.querySelector('p').innerHTML = 'Revisa que exista <strong>public/japonutopia_capasrenovadas.glb</strong> dentro de esta instalacion.';
            }
        );

        document.getElementById('reset-camera').addEventListener('click', () => selectZone(zones[0]));
        document.getElementById('toggle-labels').addEventListener('click', () => {
            labelsVisible = !labelsVisible;
            updateLabels();
        });

        window.addEventListener('resize', () => {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        });

        function animate() {
            controls.update();
            labelItems.forEach((item) => {
                item.group.rotation.y += .012;
            });
            updateLabels();
            renderer.render(scene, camera);
            requestAnimationFrame(animate);
        }

        animate();
    </script>
    @include('partials.indi-footer', ['overlay' => true, 'theme' => 'dark'])
</body>
</html>
