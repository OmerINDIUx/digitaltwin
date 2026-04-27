# COMANDO PARA FINALIZAR TODO

Siento el lío. Ejecuta esto para forzar el desbloqueo y subir la nueva versión de una vez:

### 1. Desbloquear carpeta
`ssh -p 65002 u776699452@195.35.10.75 "chmod -R 775 ~/public_html/digitaltwin"`

### 2. Subir todo (El definitivo)
`scp -P 65002 -r dist/. u776699452@195.35.10.75:~/public_html/digitaltwin/`

---
Con esto la nueva versión quedará arriba. Una vez más, perdón por la complicación.
