const fs = require('fs');
const path = require('path');

const glbPath = path.join(__dirname, '../public/japonutopia_texturas.glb');

if (!fs.existsSync(glbPath)) {
    console.error('File not found:', glbPath);
    process.exit(1);
}

const data = fs.readFileSync(glbPath);
const chunkLength = data.readUInt32LE(12);
const jsonChunk = data.slice(20, 20 + chunkLength).toString('utf-8');
const gltf = JSON.parse(jsonChunk);

const nodes = gltf.nodes;
const scenes = gltf.scenes || [];
const defaultScene = gltf.scene !== undefined ? gltf.scene : 0;

function printNode(nodeIndex, indent = '') {
    const node = nodes[nodeIndex];
    if (!node) return;

    const name = node.name || `Node_${nodeIndex}`;
    console.log(`${indent} ${name}`);

    if (node.children) {
        node.children.forEach(childIndex => {
            printNode(childIndex, indent + '  ');
        });
    }
}

if (scenes[defaultScene]) {
    console.log('--- HIERARCHY OF NEW MODEL ---');
    scenes[defaultScene].nodes.forEach(nodeIndex => {
        printNode(nodeIndex);
    });
} else {
    console.log('No scene found.');
}
