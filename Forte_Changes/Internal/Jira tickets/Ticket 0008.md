# Agregar minimo de USD 250.00 en tabla de deducibles

## Contexto
Fuente: Documento de correcciones (comentario de Sandybell, 2025-10-07)
Numero original: Comentario 7

## Solicitud del cliente
with minimum USD 250.00

## Alcance
Agregar el texto junto al valor porcentual correspondiente.

## Archivos afectados
- Slip Puerto Rico MIC actualizado.docx

## OpenL
- Workbook: Reglas de cotizacion FORTE actualizadas.xlsx (cuando aplica)
- Regla: N/A
- Cambio requerido: ver seccion Alcance

## Documentos
- Documento: Slip Puerto Rico MIC actualizado.docx
- Cláusula/seccion: Tabla de deducibles
- Cambio requerido: ver seccion Alcance

## Scripts
- Script: N/A
- ID: N/A
- Cambio requerido: No aplica

## Pantallas y configuraciones
No se identificaron pantallas de ProcessMaker directamente relacionadas con este ticket, salvo que se indique lo contrario en Observaciones.

## Criterios de aceptacion
La celda de deducible incluye el texto 'with minimum USD 250.00' junto al valor correcto.

## Casos de prueba
Ver 'Matriz de pruebas.xlsx' en la carpeta 'Pruebas realizadas', prueba(s) asociada(s) a Ticket 0008.

## Dependencias
Ninguna identificada

## Riesgos
Ver columna Riesgo del 'Seguimiento interno de cambios.xlsx' para este ticket.

## Publicacion
No se publico nada a DEV ni PROD en esta sesion. Ver 'Instrucciones de publicacion.xlsx' dentro de 'Paquete para desarrollo' / 'Paquete para produccion'.

## Reversion
Version anterior disponible en 'Trabajo interno/Versiones anteriores'.

## Estado
Requiere revision manual

## Evidencia
Comentario 7 leido de comments.xml; contexto de la celda: '3.00 %3% ' (valores de 2 celdas aparentemente concatenados sin separador claro).

## Observaciones
No se edito automaticamente: el contenido de la celda es ambiguo (parece contener 2 valores numericos distintos superpuestos, '3.00%' y '3%'), y una edicion automatica podria alterar una cifra de deducible real en un documento legal. Se requiere que alguien con el diseño original de esta tabla confirme el valor y la celda exacta antes de escribir el texto.
