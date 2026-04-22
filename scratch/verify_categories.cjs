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
const categories = {
    gym: false,
    pool: false,
    canchas: false,
    admin: false,
    terreno: false,
    estructura: false,
    techo: false
};

nodes.forEach(node => {
    const name = (node.name || '').toLowerCase();
    if (name.includes('gym') || name.includes('gimnasio')) categories.gym = true;
    if (name.includes('pool') || name.includes('alberca')) categories.pool = true;
    if (name.includes('cancha') || name.includes('tenis') || name.includes('padel')) categories.canchas = true;
    if (name.includes('admin') || name.includes('administracion')) categories.admin = true;
    if (name.includes('terreno')) categories.terreno = true;
    if (name.includes('estructura') || name.includes('structure')) categories.estructura = true;
    if (name.includes('techo') || name.includes('roof')) categories.techo = true;
});

console.log('Category check:', categories);

// Also list top-level nodes
const scenes = gltf.scenes || [];
const defaultScene = gltf.scene !== undefined ? gltf.scene : 0;
if (scenes[defaultScene]) {
    console.log('Top level nodes:');
    scenes[defaultScene].nodes.forEach(nodeIndex => {
        console.log(`- ${nodes[nodeIndex].name || 'Unnamed'}`);
    });
}
