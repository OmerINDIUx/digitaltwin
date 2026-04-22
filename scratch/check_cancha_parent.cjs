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

function getParent(nodeIdx) {
    for (let i = 0; i < nodes.length; i++) {
        if (nodes[i].children && nodes[i].children.includes(nodeIdx)) {
            return i;
        }
    }
    return -1;
}

const canchaIdx = nodes.findIndex(n => (n.name || '') === 'Canchas');
if (canchaIdx !== -1) {
    let pIdx = getParent(canchaIdx);
    if (pIdx !== -1) {
        console.log(`Parent of Canchas is: ${nodes[pIdx].name || 'Unnamed'}`);
    } else {
        console.log('Canchas is a root node.');
    }
}
