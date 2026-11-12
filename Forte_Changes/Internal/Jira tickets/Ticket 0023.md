# Agregar 4 clausulas opcionales nuevas de forma general

## Contexto
Fuente: Correo 2 de Mayra Aguilar (Solicitudes Nuevas)
Numero original: Punto 9

## Solicitud del cliente
Retomando el punto 4 del correo anterior, clausulas Opcionales, necesitamos agregar otras 4 clausulas (ver celda 11 a 14 del excel adjunto)...

## Alcance
Insertar los 3 textos de clausula recibidos (mas 1 pendiente) de forma general en la pestana Clauses del OpenL.

## Archivos afectados
- Reglas de cotizacion FORTE actualizadas.xlsx

## OpenL
- Workbook: Reglas de cotizacion FORTE actualizadas.xlsx (cuando aplica)
- Regla: Pestana Clauses (insertar filas nuevas)
- Cambio requerido: ver seccion Alcance

## Documentos
- Documento: Reglas de cotizacion FORTE actualizadas.xlsx
- Cláusula/seccion: Bateria de Litio, Depreciacion (>20 anos), Depreciacion (<20 anos)
- Cambio requerido: ver seccion Alcance

## Scripts
- Script: 26-yacht-set-information-slip.php
- ID: N/A
- Cambio requerido: Ver notas del ticket; no se modifico codigo en esta pasada salvo que se indique lo contrario en Observaciones.

## Pantallas y configuraciones
No se identificaron pantallas de ProcessMaker directamente relacionadas con este ticket, salvo que se indique lo contrario en Observaciones.

## Criterios de aceptacion
Las 3-4 clausulas nuevas aparecen al final de las clausulas, antes de firmas, en cualquier BaseText/idioma.

## Casos de prueba
Ver 'Matriz de pruebas.xlsx' en la carpeta 'Pruebas realizadas', prueba(s) asociada(s) a Ticket 0023.

## Dependencias
26-yacht-set-information-slip.php

## Riesgos
Ver columna Riesgo del 'Seguimiento interno de cambios.xlsx' para este ticket.

## Publicacion
No se publico nada a DEV ni PROD en esta sesion. Ver 'Instrucciones de publicacion.xlsx' dentro de 'Paquete para desarrollo' / 'Paquete para produccion'.

## Reversion
Version anterior disponible en 'Trabajo interno/Versiones anteriores'.

## Estado
Bloqueado, pendiente de confirmacion

## Evidencia
3 de 4 textos verificados y listos (litio ES/EN completos; depreciacion mayores 20 anos ES/EN completos; depreciacion menores 20 anos solo EN). Duplicado exacto detectado entre las 2 copias en espanol de la clausula de litio (mismo SHA-256).

## Observaciones
No se inserto nada en el OpenL: agregar filas 'de forma general' en una tabla de decision de 1020 filas sin conocer la estructura de clave correcta (que combinacion de Country/Reinsurer/Language/BaseText debe activarlas) podria aplicar la clausula donde no corresponde. Falta ademas el texto en espanol de 'Depreciacion (Menores a 20 anos)'.
