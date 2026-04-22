const fs = require('fs');
const path = require('path');

const glbPath = path.join(__dirname, '../public/japonutopia_texturas.glb');

const data = fs.readFileSync(glbPath);
const chunkLength = data.readUInt32LE(12);
const jsonChunk = data.slice(20, 20 + chunkLength).toString('utf-8');
const gltf = JSON.parse(jsonChunk);

const nodes = gltf.nodes;
const meshes = gltf.meshes || [];
const materials = gltf.materials || [];

console.log('--- CANCHA MATERIALS ---');
nodes.filter(n => (n.name || '').toLowerCase().includes('cancha')).forEach(root => {
    if (root.children) {
        root.children.forEach(childIdx => {
            const childNode = nodes[childIdx];
            console.log(`Node: ${childNode.name}`);
            if (childNode.mesh !== undefined) {
                const mesh = meshes[childNode.mesh];
                mesh.primitives.forEach(p => {
                    if (p.material !== undefined) {
                        const mat = materials[p.material];
                        console.log(`  - Material: ${mat.name || 'Unnamed'} (BaseColor: ${JSON.stringify(mat.pbrMetallicRoughness?.baseColorFactor)})`);
                    }
                });
            }
            if (childNode.children) {
                childNode.children.forEach(gcIdx => {
                    const gcNode = nodes[gcIdx];
                    if (gcNode.mesh !== undefined) {
                        const mesh = meshes[gcNode.mesh];
                        mesh.primitives.forEach(p => {
                            if (p.material !== undefined) {
                                const mat = materials[p.material];
                                console.log(`    -- Sub-Node: ${gcNode.name || 'Unnamed'} Material: ${mat.name || 'Unnamed'}`);
                            }
                        });
                    }
                });
            }
        });
    }
});
