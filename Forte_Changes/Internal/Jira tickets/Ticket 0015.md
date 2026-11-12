# Tabla de Tenders no aparece con BaseText CL.328

## Contexto
Fuente: Correo 1 de Mayra Aguilar
Numero original: Punto 1

## Solicitud del cliente
La tabla de los Tenders, me dicen que sale para la R12 pero no para los demas textos CL. 328 por ejemplo.

## Alcance
Hacer que la tabla de Tenders aparezca para cualquier BaseText cuando aplique.

## Archivos afectados
- Templates base ya localizados en Iframe Documents/DLSU/Templates por Base Text; Reglas de cotizacion FORTE actualizadas.xlsx

## OpenL
- Workbook: Reglas de cotizacion FORTE actualizadas.xlsx (cuando aplica)
- Regla: Slip Rules (SlipRulesSumInsuredES/EN) - no condiciona por BaseText
- Cambio requerido: ver seccion Alcance

## Documentos
- Documento: Templates base ya localizados en Iframe Documents/DLSU/Templates por Base Text; Reglas de cotizacion FORTE actualizadas.xlsx
- Cláusula/seccion: Seccion de Tenders
- Cambio requerido: ver seccion Alcance

## Scripts
- Script: 26-yacht-set-information-slip.php
- ID: ID NOT FOUND
- Cambio requerido: Ver notas del ticket; no se modifico codigo en esta pasada salvo que se indique lo contrario en Observaciones.

## Pantallas y configuraciones
No se identificaron pantallas de ProcessMaker directamente relacionadas con este ticket, salvo que se indique lo contrario en Observaciones.

## Criterios de aceptacion
El slip en CL.328 muestra la tabla de Tenders igual que en R12.

## Casos de prueba
Ver 'Matriz de pruebas.xlsx' en la carpeta 'Pruebas realizadas', prueba(s) asociada(s) a Ticket 0015.

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
Con los templates base ya localizados se descarta la causa que se sospechaba antes (plantilla CL.328 faltante): el template 'Cláusulas del Instituto CL.328/01-Informacion-General-y-Condiciones-Especiales-C328.docx' SI contiene el token ${YQP_SLIP_TENDERS_SLIP}, igual que el de R12. Esto confirma que el documento no es la causa: el problema esta en que el OpenL/script no esta llenando ese token con datos para BaseText CL.328.

## Observaciones
No se modifico el OpenL: la tabla Clauses/Slip Rules tiene 1020 filas y no se identifico la fila especifica que condiciona (o deberia condicionar) la seccion de Tenders por BaseText. Se requiere un caso de prueba real en CL.328 para aislarla antes de editar.
