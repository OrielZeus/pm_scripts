# Actualizar la clausula 'Sanction Limitation and Exclusion Clause' a su version vigente

## Contexto
Fuente: Documento de correcciones (comentario de Sandybell, 2025-10-07)
Numero original: Comentario 14

## Solicitud del cliente
Sustituir por Sanction Limitation and Exclusion Clause: No (re)insurer shall be deemed to provide cover... European Union, United Kingdom or United States of America or Switzerland ... undersigned reinsurer.

## Alcance
Reemplazar el cuerpo completo de la clausula por el texto exacto indicado por la revisora.

## Archivos afectados
- Slip Puerto Rico MIC actualizado.docx (ya corregido); ademas 3 templates base en ingles: AMERICAN YACHT FORM R12 with Sections/21-INSTITUTE-RADIOACTIVE-CONTAMINATION.docx y 22-GENERAL-CLAUSES.docx; Institute Yacht Clauses CL.328 deleted/24-GENERAL-CLAUSES.docx

## OpenL
- Workbook: Reglas de cotizacion FORTE actualizadas.xlsx (cuando aplica)
- Regla: N/A
- Cambio requerido: ver seccion Alcance

## Documentos
- Documento: Slip Puerto Rico MIC actualizado.docx (ya corregido); ademas 3 templates base en ingles: AMERICAN YACHT FORM R12 with Sections/21-INSTITUTE-RADIOACTIVE-CONTAMINATION.docx y 22-GENERAL-CLAUSES.docx; Institute Yacht Clauses CL.328 deleted/24-GENERAL-CLAUSES.docx
- Cláusula/seccion: Sanction Limitation and Exclusion Clause
- Cambio requerido: ver seccion Alcance

## Scripts
- Script: N/A
- ID: N/A
- Cambio requerido: Ver notas del ticket; no se modifico codigo en esta pasada salvo que se indique lo contrario en Observaciones.

## Pantallas y configuraciones
No se identificaron pantallas de ProcessMaker directamente relacionadas con este ticket, salvo que se indique lo contrario en Observaciones.

## Criterios de aceptacion
El cuerpo de la clausula incluye 'United Kingdom' y 'Switzerland', y dice 'undersigned reinsurer' (no '(re)insurer'), en el documento generado Y en los templates base reutilizables.

## Casos de prueba
Ver 'Matriz de pruebas.xlsx' en la carpeta 'Pruebas realizadas', prueba(s) asociada(s) a Ticket 0014.

## Dependencias
N/A

## Riesgos
Ver columna Riesgo del 'Seguimiento interno de cambios.xlsx' para este ticket.

## Publicacion
Copia corregida incluida en 'Paquete para desarrollo' y 'Paquete para produccion'; falta que alguien con acceso al Collection/ambiente de ProcessMaker la suba para reemplazar el documento/regla actual. Ver 'Instrucciones de publicacion.xlsx'.

## Reversion
Version anterior disponible en 'Trabajo interno/Versiones anteriores'.

## Estado
Actualizado

## Evidencia
CORRECCION IMPORTANTE: la clausula de sanciones tambien vive como texto fijo en templates base reutilizables (los mismos documentos '21-INSTITUTE-RADIOACTIVE-CONTAMINATION.docx'/'22-GENERAL-CLAUSES.docx'/'24-GENERAL-CLAUSES.docx' que ya se habian localizado para el Ticket 0021), y esos 3 templates en ingles SI tenian la version desactualizada (sin Reino Unido/Suiza, con '(re)insurer'). Se corrigieron con el mismo texto exacto que aprobo Sandybell. Se encontraron ADEMAS 5 templates en ESPAÑOL con el mismo tipo de clausula desactualizada (CL.328 base y con exclusiones, Panama, POL 1 2013, y Estar Seguros Venezuela) pero su version en español usa una redaccion distinta a la del ingles, y no hay instruccion del cliente ni texto aprobado para la version en español -- NO se modificaron para evitar inventar una traduccion legal.

## Observaciones
Se corrigieron 3 templates en ingles (verificado: 'United Kingdom' y 'Switzerland' presentes, 'undersigned reinsurer)' presente, 'undersigned (re)insurer)' ausente; conteo de parrafos sin cambios; ZIP/XML validos). Queda pendiente una decision del cliente sobre si aplicar la misma actualizacion a los 5 templates en español, y con que texto exacto (ver Trabajo interno/Jira tickets/Ticket 0014.md para el detalle de los 5 archivos y su texto actual).
