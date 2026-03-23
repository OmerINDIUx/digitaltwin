const fs = require('fs');

try {
    const buffer = fs.readFileSync('c:/Users/C-2025-0192/OneDrive - GAMI INGENIERIA E INSTALACIONES S.A. DE C.V/Escritorio/three.js-dev/three.js-dev/mi-proyecto-3d/public/japonutopia_capasrenovadas.glb');

    const chunkLength = buffer.readUInt32LE(12);
    const jsonBuffer = buffer.subarray(20, 20 + chunkLength);
    const gltf = JSON.parse(jsonBuffer.toString('utf8'));

    function findPathToMesh(nodeIndex, path = []) {
        const node = gltf.nodes[nodeIndex];
        const newPath = [...path, node.name || 'UNNAMED'];
        
        if (node.mesh !== undefined) {
            // Found a mesh, let's see its path
            console.log("Mesh Path:", newPath.join(" -> "));
        }
        
        if (node.children) {
            node.children.forEach(childIndex => findPathToMesh(childIndex, newPath));
        }
    }

    if (gltf.scenes && gltf.scenes.length > 0) {
        const scene = gltf.scenes[gltf.scene || 0];
        scene.nodes.forEach(nodeIndex => findPathToMesh(nodeIndex));
    }

} catch (err) {
    console.error("Error reading GLB:", err);
}
