const fs = require('fs');
const path = require('path');

const glbPath = path.join(__dirname, '../public/japonutopia_texturas.glb');

const data = fs.readFileSync(glbPath);
const chunkLength = data.readUInt32LE(12);
const jsonChunk = data.slice(20, 20 + chunkLength).toString('utf-8');
const gltf = JSON.parse(jsonChunk);

const nodes = gltf.nodes;
const scenes = gltf.scenes || [];
const defaultScene = gltf.scene !== undefined ? gltf.scene : 0;

function findNodeByName(namePart) {
    return nodes.filter(n => (n.name || '').toLowerCase().includes(namePart.toLowerCase()));
}

const canchaNodes = findNodeByName('cancha');
console.log('Nodes matching "cancha":', canchaNodes.map(n => ({ name: n.name, childrenCount: (n.children || []).length })));

if (canchaNodes.length > 0) {
    canchaNodes.forEach(root => {
        if (root.children) {
            console.log(`Children of ${root.name}:`);
            root.children.forEach(childIdx => {
                console.log(`  - ${nodes[childIdx].name || 'Unnamed'}`);
                if (nodes[childIdx].children) {
                     nodes[childIdx].children.forEach(gcIdx => {
                         console.log(`    -- ${nodes[gcIdx].name || 'Unnamed'}`);
                     });
                }
            });
        }
    });
}
