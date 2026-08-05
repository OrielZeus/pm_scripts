# Reemplazar endoso de deducible por viento huracanado

## Contexto
Fuente: Documento de correcciones (comentario de Sandybell, 2025-10-07)
Numero original: Comentario 3

## Solicitud del cliente
Quitar esta endoso y reemplazar por Special Deductible for Named Windstorm, Hurricane and/or Lightning.

## Alcance
Reemplazar el texto exacto del bullet.

## Archivos afectados
- Reglas de cotizacion FORTE actualizadas.xlsx (pestana Clauses); tambien corregido de forma cosmetica en Slip Puerto Rico MIC actualizado.docx

## OpenL
- Workbook: Reglas de cotizacion FORTE actualizadas.xlsx (cuando aplica)
- Regla: Clauses (columna H, filas 39 y 51)
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
El bullet dice 'Special Deductible for Named Windstorm, Hurricane and/or Lightning.' en CUALQUIER cotizacion nueva, no solo en este documento.

## Casos de prueba
Ver 'Matriz de pruebas.xlsx' en la carpeta 'Pruebas realizadas', prueba(s) asociada(s) a Ticket 0004.

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
CORRECCION IMPORTANTE sobre el hallazgo original: el texto del endoso NO es texto fijo del documento, viene 100% textual de la pestana 'Clauses' del OpenL (columna H), que el script 26-yacht-set-information-slip.php trae via la llamada GetClauses. Se encontro en 2 filas identicas: fila 39 (grupo Puerto Rico / Multinational Insurance Company (Puerto Rico)-MIC / ES / AMERICAN YACHT FORM R12, el mismo caso exacto de este documento) y fila 51 (grupo general EN / AMERICAN YACHT FORM R12, aplicable a cualquier pais/reasegurador). Arreglar solo el documento generado NO hubiera corregido las cotizaciones futuras.

## Observaciones
Se corrigieron ambas filas (39 y 51) en el Excel real, verificado con diff celda por celda (unicas 2 celdas afectadas, mismo tipo de dato 'string' antes y despues, sin tocar formulas). El cambio en el documento generado se dejo tambien como estaba (referencia de como debe verse el resultado).
