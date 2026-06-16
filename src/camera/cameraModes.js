import * as THREE from "three";

const STANDARD_MOUSE_BUTTONS = {
  LEFT: THREE.MOUSE.ROTATE,
  MIDDLE: THREE.MOUSE.DOLLY,
  RIGHT: THREE.MOUSE.PAN,
};

export function applyTopDownControls(camera, controls) {
  camera.up.set(0, 0, -1);
  controls.enableRotate = true;
  controls.enablePan = true;
  controls.screenSpacePanning = false;
  controls.mouseButtons = STANDARD_MOUSE_BUTTONS;
}

export function applyStandardControls(camera, controls) {
  camera.up.set(0, 1, 0);
  controls.enableRotate = true;
  controls.enablePan = true;
  controls.screenSpacePanning = false;
  controls.mouseButtons = STANDARD_MOUSE_BUTTONS;
}

export function setTopDownCameraView(camera, controls, distance) {
  applyTopDownControls(camera, controls);
  camera.position.set(0, distance, 0);
  controls.target.set(0, 0, 0);
  camera.lookAt(controls.target);
  controls.update();
}
