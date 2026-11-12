# Clausulas opcionales incompletas en renovacion

## Contexto
Fuente: Correo 1 de Mayra Aguilar
Numero original: Punto 2

## Solicitud del cliente
Las clausulas opcionales, no salen todas para negocios de renovacion, para negocio nuevo si salen todas, no entendemos por que hay diferencias.

## Alcance
Igualar el clausulado opcional entre renovacion y negocio nuevo para la misma combinacion Country/Reinsurer/Language/BaseText.

## Archivos afectados
- Reglas de cotizacion FORTE actualizadas.xlsx

## OpenL
- Workbook: Reglas de cotizacion FORTE actualizadas.xlsx (cuando aplica)
- Regla: GetClauses / GetPreliminaryClauses (pestana Clauses, 1020 filas)
- Cambio requerido: ver seccion Alcance

## Documentos
- Documento: Reglas de cotizacion FORTE actualizadas.xlsx
- Cláusula/seccion: Multiple (depende de la combinacion)
- Cambio requerido: ver seccion Alcance

## Scripts
- Script: 26-yacht-set-information-slip.php, 52-yacht-clear-hidden-variables-and-set-clauses-selected.php
- ID: N/A
- Cambio requerido: Ver notas del ticket; no se modifico codigo en esta pasada salvo que se indique lo contrario en Observaciones.

## Pantallas y configuraciones
No se identificaron pantallas de ProcessMaker directamente relacionadas con este ticket, salvo que se indique lo contrario en Observaciones.

## Criterios de aceptacion
Renovacion y negocio nuevo, con los mismos parametros, muestran las mismas clausulas opcionales.

## Casos de prueba
Ver 'Matriz de pruebas.xlsx' en la carpeta 'Pruebas realizadas', prueba(s) asociada(s) a Ticket 0016.

## Dependencias
26-yacht-set-information-slip.php, 52-yacht-clear-hidden-variables-and-set-clauses-selected.php

## Riesgos
Ver columna Riesgo del 'Seguimiento interno de cambios.xlsx' para este ticket.

## Publicacion
No se publico nada a DEV ni PROD en esta sesion. Ver 'Instrucciones de publicacion.xlsx' dentro de 'Paquete para desarrollo' / 'Paquete para produccion'.

## Reversion
Version anterior disponible en 'Trabajo interno/Versiones anteriores'.

## Estado
Requiere confirmacion

## Evidencia
Confirmado que el script no envia ningun indicador de renovacion/nuevo a OpenL; la diferencia debe originarse en una fila especifica de las 1020 de la tabla Clauses, no identificada.

## Observaciones
No se modifico la tabla Clauses: hacerlo sin conocer la fila exacta podria romper otras combinaciones validas. Se requiere un caso de prueba real (numero de cotizacion) de renovacion vs nuevo para aislar la fila.
