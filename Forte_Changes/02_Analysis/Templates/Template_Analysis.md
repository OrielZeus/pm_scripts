# Análisis de templates / documentos Word

## Documentos analizados (lectura con python-docx, sin alterar originales)

| Archivo (nombre corto; ver `PATH_RENAMES.md`) | Párrafos | Tablas | Hallazgo |
|---|---|---|---|
| Clausula_Baterias_Litio_ES.docx | 17 | 0 | Texto completo verificado (español) |
| CLÁUSULA DE BATERÍAS DE LITIO (1).docx | 17 | 0 | **Byte-idéntico** al anterior (mismo SHA-256 `e39c758...107daf2e`) — duplicado exacto, probable subida repetida por error |
| Clausula_Baterias_Litio_EN.docx | 19 | 0 | Versión en inglés, texto completo |
| Clausula_Depreciacion_Mayores_20_ES.docx | 30 | 0 | Español, factores 50%/10%/60% según rubro |
| Clausula_Depreciacion_Mayores_20_EN.docx | 30 | 0 | Inglés, mismos factores |
| Clausula_Depreciacion_Menores_20_EN.docx | 45 | 0 | Inglés, factores 50%/10%/40%/35% — **falta la versión en español** (no fue subida por el cliente) |
| Correcciones_Slip_Ejemplo.docx | 638 | 7 | Slip real de cliente (Puerto Rico / Multinational Insurance Company (Puerto Rico)-MIC), no es un template base |

## Hallazgo central: cláusula de cancelación con "Mexbrit" hardcodeado (REQ-007)

En `Correcciones_Slip_Ejemplo.docx`, 13 apariciones literales de "Mexbrit":
- Párrafo 487: *"In consideration of Mexbrit giving credit to the Reassured for the premium due to Mexbrit..."* — clausula de cancelación completa, con "Mexbrit" repetido como texto fijo.
- Párrafo 489: *"This clause shall not affect the lien of Mexbrit on the Policies..."*
- Párrafo 624: *"80 % of 100% by MEXBRIT LLC"* — línea de firma del bloque de reaseguradores.
- Párrafo 637: *"MEXBRIT LLC."* — firma final.

Comparado con el script `26-yacht-set-information-slip.php` (líneas 1483-1489), que **sí** arma dinámicamente la línea `"<FORTE_ORDER>% por/by <YQP_COMPLETE_NAME_SOURCE>"` para la sección de firmas cuando `YQP_SOURCE != 'Other'`. El mismo patrón dinámico existente **no fue replicado** en el párrafo de la cláusula de cancelación del documento base. Esta es la causa raíz más probable del punto 7 de Mayra, con **alta confianza** porque se pudo comparar directamente el texto fijo del documento contra la lógica dinámica ya implementada en otra sección del mismo flujo.

No se localizó el **template base** (el archivo fuente antes de generarse el slip) en este repositorio — solo se tiene el documento ya generado como evidencia. Sin el template base no se puede aplicar el cambio directamente; hay que ubicarlo primero (ver `Risks_and_Pending`).

## Bloque de Ajustadores (REQ-013 / REQ-014)

Mayra adjuntó capturas mostrando un bloque de ajustador duplicado:
1. "Charles Taylor Adjusting" (Alejandro Martínez / Fernando León) con `claims@mexbrit.com`.
2. "Panamá Surveyors Bureau Inc. (PSB) a través de Charles Taylor Adjusting" (Fernando León).

Se buscó "Mexbrit", "Alejandro Martínez", "Charles Taylor" y "claims@" en los 638 párrafos de `Correcciones_Slip_Ejemplo.docx`: **no aparecen** — este slip específico no contiene ese bloque, o ya fue corregido en este ejemplo puntual. Tampoco se encontraron estos términos en los 154 scripts PHP ni 406 pantallas JSON del backup DEV (una búsqueda inicial de "mxb" arrojó falsos positivos dentro de imágenes base64 embebidas en las pantallas — se descartaron tras revisión). **Conclusión: el bloque de Ajustadores vive fuera de este repositorio** (probablemente una Collection de ProcessMaker o un documento base no incluido en el export de backup). No se pudo aplicar ni verificar el cambio solicitado por falta de acceso al archivo fuente.

## Cláusulas nuevas pendientes de insertar (REQ-009)
Los 3 textos de cláusula recibidos (litio, depreciación >20, depreciación <20 en inglés) están completos y listos para insertarse, pero:
- Falta el texto en español de "Depreciación (MENORES A 20 años)".
- No se identificó la celda/fila exacta de la pestaña `Clauses` del OpenL donde deberían insertarse para que apliquen "de forma general" (cualquier BaseText, cualquier idioma) como pide Mayra.
