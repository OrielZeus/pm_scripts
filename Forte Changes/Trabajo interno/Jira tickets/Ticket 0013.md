# Fijar el encabezado 'AMERICAN YACHT FORM R12 - WARRANTIES AND GENERAL CONDITIONS' como texto permanente

## Contexto
Fuente: Documento de correcciones (comentario de Sandybell, 2026-04-22)
Numero original: Comentario 12

## Solicitud del cliente
Incluir American Yacht Form R12 como texto fijo

## Alcance
Confirmar que este encabezado se genere siempre para BaseText R12, sin depender de una condicion que pueda omitirlo.

## Archivos afectados
- Slip Puerto Rico MIC actualizado.docx (el texto ya esta presente aqui)

## OpenL
- Workbook: Reglas de cotizacion FORTE actualizadas.xlsx (cuando aplica)
- Regla: Desconocida (no localizada)
- Cambio requerido: ver seccion Alcance

## Documentos
- Documento: Slip Puerto Rico MIC actualizado.docx (el texto ya esta presente aqui)
- Cláusula/seccion: AMERICAN YACHT FORM R12 - WARRANTIES AND GENERAL CONDITIONS
- Cambio requerido: ver seccion Alcance

## Scripts
- Script: 51-templates-get-base-texts.php (candidato)
- ID: ID NOT FOUND
- Cambio requerido: Ver notas del ticket; no se modifico codigo en esta pasada salvo que se indique lo contrario en Observaciones.

## Pantallas y configuraciones
No se identificaron pantallas de ProcessMaker directamente relacionadas con este ticket, salvo que se indique lo contrario en Observaciones.

## Criterios de aceptacion
El encabezado aparece siempre que BaseText sea R12.

## Casos de prueba
Ver 'Matriz de pruebas.xlsx' en la carpeta 'Pruebas realizadas', prueba(s) asociada(s) a Ticket 0013.

## Dependencias
51-templates-get-base-texts.php (candidato)

## Riesgos
Ver columna Riesgo del 'Seguimiento interno de cambios.xlsx' para este ticket.

## Publicacion
No se publico nada a DEV ni PROD en esta sesion. Ver 'Instrucciones de publicacion.xlsx' dentro de 'Paquete para desarrollo' / 'Paquete para produccion'.

## Reversion
Version anterior disponible en 'Trabajo interno/Versiones anteriores'.

## Estado
Requiere confirmacion (nivel de sistema, no solo de documento)

## Evidencia
El texto ya esta presente en este documento (parrafo 251).

## Observaciones
Relacionado con el Ticket 0003 (misma familia de solicitudes sobre 'texto fijo' de R12).
