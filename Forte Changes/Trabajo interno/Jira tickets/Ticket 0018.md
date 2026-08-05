# Clausula de limitacion de indemnizacion (motores internos) aparece cortada

## Contexto
Fuente: Correo 1 de Mayra Aguilar
Numero original: Punto 4

## Solicitud del cliente
De las clausulas opcionales que se te pidio agregar se pegaron algunas incorrectas, por ejemplo, se pidio agregar esta (texto completo en espanol/ingles) ... Pero aparece asi en los slips de español, (esta cortada).

## Alcance
Restaurar el texto completo de la clausula en la pestana Clauses del OpenL.

## Archivos afectados
- Reglas de cotizacion FORTE actualizadas.xlsx

## OpenL
- Workbook: Reglas de cotizacion FORTE actualizadas.xlsx (cuando aplica)
- Regla: Pestana Clauses (fila no identificada de 1020)
- Cambio requerido: ver seccion Alcance

## Documentos
- Documento: Reglas de cotizacion FORTE actualizadas.xlsx
- Cláusula/seccion: Limitacion de indemnizacion, motores internos
- Cambio requerido: ver seccion Alcance

## Scripts
- Script: 26-yacht-set-information-slip.php
- ID: N/A
- Cambio requerido: Ver notas del ticket; no se modifico codigo en esta pasada salvo que se indique lo contrario en Observaciones.

## Pantallas y configuraciones
No se identificaron pantallas de ProcessMaker directamente relacionadas con este ticket, salvo que se indique lo contrario en Observaciones.

## Criterios de aceptacion
El texto completo aparece en el slip en espanol, igual que en ingles.

## Casos de prueba
Ver 'Matriz de pruebas.xlsx' en la carpeta 'Pruebas realizadas', prueba(s) asociada(s) a Ticket 0018.

## Dependencias
26-yacht-set-information-slip.php

## Riesgos
Ver columna Riesgo del 'Seguimiento interno de cambios.xlsx' para este ticket.

## Publicacion
No se publico nada a DEV ni PROD en esta sesion. Ver 'Instrucciones de publicacion.xlsx' dentro de 'Paquete para desarrollo' / 'Paquete para produccion'.

## Reversion
Version anterior disponible en 'Trabajo interno/Versiones anteriores'.

## Estado
Requiere confirmacion

## Evidencia
El propio correo trae el texto completo esperado y el resultado cortado real (evidencia directa del cliente).

## Observaciones
No se modifico: no se identifico la fila/celda exacta dentro de las 1020 de la pestana Clauses; se requiere localizarla antes de editar para no afectar otras combinaciones.
