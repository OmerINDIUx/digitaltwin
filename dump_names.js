import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';
import { DRACOLoader } from 'three/examples/jsm/loaders/DRACOLoader.js';
import fs from 'fs';

// This script is meant to be run in a Node environment that can handle Three.js (using jsdon/headless) 
// BUT, since we are in a limited environment, let's try to just read the file as a string for clues 
// or use a more robust Node approach if dependencies allow.
// Actually, I'll just update main.js to console.log them and the user can see them in their browser console.

console.log("To see all layer names, open your browser console (F12) while the app is running.");
