# Reemplazar codigos de forma de poliza por 'MULTINATIONAL YACHT POLICY 1/2026'

## Contexto
Fuente: Documento de correcciones (comentario de Sandybell, 2026-04-22)
Numero original: Comentario 5

## Solicitud del cliente
Eliminar estos dos y sustituir por MULTINATIONAL YACHT POLICY 1/2026

## Alcance
Reemplazar el texto exacto de los 2 codigos por el nombre de poliza indicado.

## Archivos afectados
- Reglas de cotizacion FORTE actualizadas.xlsx (pestana Clauses); tambien corregido de forma cosmetica en Slip Puerto Rico MIC actualizado.docx

## OpenL
- Workbook: Reglas de cotizacion FORTE actualizadas.xlsx (cuando aplica)
- Regla: Clauses (columna H, filas 33 y 58)
- Cambio requerido: ver seccion Alcance

## Documentos
- Documento: Reglas de cotizacion FORTE actualizadas.xlsx (pestana Clauses); tambien corregido de forma cosmetica en Slip Puerto Rico MIC actualizado.docx
- Cláusula/seccion: ADDITIONAL CONDITIONS
- Cambio requerido: ver seccion Alcance

## Scripts
- Script: 26-yacht-set-information-slip.php (linea 378, llamada GetClauses)
- ID: N/A
- Cambio requerido: Ver notas del ticket; no se modifico codigo en esta pasada salvo que se indique lo contrario en Observaciones.

## Pantallas y configuraciones
No se identificaron pantallas de ProcessMaker directamente relacionadas con este ticket, salvo que se indique lo contrario en Observaciones.

## Criterios de aceptacion
El bullet dice 'MULTINATIONAL YACHT POLICY 1/2026;IL0136 (05/04)...' en CUALQUIER cotizacion nueva, no solo en este documento.

## Casos de prueba
Ver 'Matriz de pruebas.xlsx' en la carpeta 'Pruebas realizadas', prueba(s) asociada(s) a Ticket 0006.

## Dependencias
26-yacht-set-information-slip.php (linea 378, llamada GetClauses)

## Riesgos
Ver columna Riesgo del 'Seguimiento interno de cambios.xlsx' para este ticket.

## Publicacion
Copia corregida incluida en 'Paquete para desarrollo' y 'Paquete para produccion'; falta que alguien con acceso al Collection/ambiente de ProcessMaker la suba para reemplazar el documento/regla actual. Ver 'Instrucciones de publicacion.xlsx'.

## Reversion
Version anterior disponible en 'Trabajo interno/Versiones anteriores'.

## Estado
Actualizado

## Evidencia
CORRECCION IMPORTANTE sobre el hallazgo original: igual que el Ticket 0004, este texto viene 100% de la pestana 'Clauses' del OpenL (columna H), filas 33 (grupo Puerto Rico/MIC, el caso exacto de este documento) y 58 (grupo general EN/R12). Arreglar solo el documento generado no hubiera corregido cotizaciones futuras.

## Observaciones
Se corrigieron ambas filas (33 y 58) en el Excel real, reemplazando unicamente 'IL P 001 01 04;OPT 0004(12-14);' por 'MULTINATIONAL YACHT POLICY 1/2026;' y conservando el resto de la lista de codigos (IL0136, MYP-0015, etc.), igual que se hizo en el documento. Verificado con diff celda por celda.
