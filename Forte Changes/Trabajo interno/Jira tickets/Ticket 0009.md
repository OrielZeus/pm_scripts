# Cambiar 'Hurricane and Lightning' a 'Hurricane and/or Lightning'

## Contexto
Fuente: Documento de correcciones (comentario de Sandybell, 2025-10-07)
Numero original: Comentario 8

## Solicitud del cliente
And/or

## Alcance
Reemplazar 'and' por 'and/or' en la frase indicada.

## Archivos afectados
- Reglas de cotizacion FORTE actualizadas.xlsx (pestana Slip Rules); tambien corregido de forma cosmetica en Slip Puerto Rico MIC actualizado.docx

## OpenL
- Workbook: Reglas de cotizacion FORTE actualizadas.xlsx (cuando aplica)
- Regla: Slip Rules, fila 106, columna C (texto interno de regla OpenL, usado para construir la tabla HTML de deducibles especiales)
- Cambio requerido: ver seccion Alcance

## Documentos
- Documento: Reglas de cotizacion FORTE actualizadas.xlsx (pestana Slip Rules); tambien corregido de forma cosmetica en Slip Puerto Rico MIC actualizado.docx
- Cláusula/seccion: Tabla de deducibles especiales
- Cambio requerido: ver seccion Alcance

## Scripts
- Script: 26-yacht-set-information-slip.php (construccion de YQP_SLIP_DEDUCTIBLE_HTML a partir de la respuesta de OpenL)
- ID: N/A
- Cambio requerido: Ver notas del ticket; no se modifico codigo en esta pasada salvo que se indique lo contrario en Observaciones.

## Pantallas y configuraciones
No se identificaron pantallas de ProcessMaker directamente relacionadas con este ticket, salvo que se indique lo contrario en Observaciones.

## Criterios de aceptacion
El texto dice 'Hurricane and/or Lightning Loss' en CUALQUIER cotizacion nueva, no solo en este documento.

## Casos de prueba
Ver 'Matriz de pruebas.xlsx' en la carpeta 'Pruebas realizadas', prueba(s) asociada(s) a Ticket 0009.

## Dependencias
26-yacht-set-information-slip.php (construccion de YQP_SLIP_DEDUCTIBLE_HTML a partir de la respuesta de OpenL)

## Riesgos
Ver columna Riesgo del 'Seguimiento interno de cambios.xlsx' para este ticket.

## Publicacion
Copia corregida incluida en 'Paquete para desarrollo' y 'Paquete para produccion'; falta que alguien con acceso al Collection/ambiente de ProcessMaker la suba para reemplazar el documento/regla actual. Ver 'Instrucciones de publicacion.xlsx'.

## Reversion
Version anterior disponible en 'Trabajo interno/Versiones anteriores'.

## Estado
Actualizado

## Evidencia
CORRECCION IMPORTANTE: el texto 'Hurricane and Lightning Loss:' vive dentro de la definicion de la regla OpenL en la pestana 'Slip Rules', fila 106. Esa celda contiene texto propio del motor de reglas OpenL (no es una formula de Excel real: Excel/LibreOffice nunca la evaluan, la trata como texto), asi que se corrigio como texto, con cuidado de NO dejar que se reinterprete como formula de Excel al guardar (se verifico que el tipo de celda se mantuvo como texto antes y despues).

## Observaciones
Se corrigio unicamente la fila 106 (unica ocurrencia de esta frase en todo el workbook, confirmado por busqueda exhaustiva). Verificado con diff celda por celda: solo esta celda cambio en toda la pestana Slip Rules, y el resto del contenido de la celda (nombres de funciones internas de OpenL) quedo exactamente igual. NO se ejecuto el recalculo automatico de formulas sobre este archivo porque se comprobo que dicho recalculo reinterpreta por error el texto de reglas OpenL como formulas de Excel invalidas y las corrompe (cambia mayusculas/minusculas de nombres de funciones internas); en su lugar se verifico manualmente que unicamente las 5 celdas esperadas (2 de este ticket... revisar Tickets 0004/0006/0022) cambiaron en todo el archivo.
