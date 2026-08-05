# Comparaciones realizadas

## Duplicados exactos detectados (por hash SHA-256)
- `CLÁUSULA DE BATERÍAS DE LITIO .docx` == `CLÁUSULA DE BATERÍAS DE LITIO  (1).docx` (hash `e39c758475e34b5dab6aba06ce72dd6c4ead940336f95b9db7f0da75107daf2e`). Ver `00_Source_Inventory/SHA256_Manifest.txt`.

## Comparaciones de contenido (texto)
- Depreciación >20 años (ES vs EN): mismos porcentajes (50%/10%/60%), traducción consistente.
- Depreciación <20 años: solo existe EN (40%/10%/35%); no hay ES para comparar — brecha documentada en `Risks_and_Pending`.
- Texto de motores internos (cláusula cortada, REQ-004): se comparó el texto que el cliente dice haber solicitado (en el cuerpo del correo) contra el resultado real mostrado en la captura del correo — el propio cliente aportó el "antes/después"; no fue necesario reconstruirlo desde archivos.

## Comparación DEV vs PROD
No fue posible — `Forte_Prod_Files` no contiene una copia utilizable de scripts ni de la mayoría de pantallas (ver `Environment_Status`). Cualquier afirmación sobre "qué hay en PROD hoy" se marca `DEPLOYMENT NOT VERIFIED` en todo el tracker.

## Comparación de versiones del OpenL
Solo se tiene **una** copia del libro OpenL (la subida por Elmer). No hay una versión "anterior" conocida para diff celda por celda; el análisis de `OpenL_Changes` describe el estado ACTUAL de las reglas relevantes, no un diff antes/después.
