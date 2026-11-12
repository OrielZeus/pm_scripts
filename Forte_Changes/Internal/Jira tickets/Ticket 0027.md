# Ajustador duplicado (el nuevo y el que esta por defecto)

## Contexto
Fuente: Correo 2 de Mayra Aguilar (Solicitudes Antiguas)
Numero original: Punto 13

## Solicitud del cliente
Cuando incluimos un ajustador deja el pasado y el que agregamos como nuevo... ya se habia arreglado para que, cuando agregaramos texto en el ajustador se eliminara el que esta por default.

## Alcance
Restaurar la logica que elimina el bloque de ajustador por defecto al agregar uno nuevo (posible regresion).

## Archivos afectados
- El bloque de ajustador por defecto (Charles Taylor Adjusting / Fernando Leon) SI se localizo como texto fijo en los 9 templates base '01-...' (ver Ticket 0028); pero la LOGICA que deberia quitar ese bloque fijo cuando el usuario captura un ajustador distinto no se encontro en ningun script PHP ni pantalla del backup DEV revisado.

## OpenL
- Workbook: Reglas de cotizacion FORTE actualizadas.xlsx (cuando aplica)
- Regla: N/A
- Cambio requerido: ver seccion Alcance

## Documentos
- Documento: El bloque de ajustador por defecto (Charles Taylor Adjusting / Fernando Leon) SI se localizo como texto fijo en los 9 templates base '01-...' (ver Ticket 0028); pero la LOGICA que deberia quitar ese bloque fijo cuando el usuario captura un ajustador distinto no se encontro en ningun script PHP ni pantalla del backup DEV revisado.
- Cláusula/seccion: Bloque de Ajustador
- Cambio requerido: ver seccion Alcance

## Scripts
- Script: No identificado en este backup
- ID: ID NOT FOUND
- Cambio requerido: Ver notas del ticket; no se modifico codigo en esta pasada salvo que se indique lo contrario en Observaciones.

## Pantallas y configuraciones
No se identificaron pantallas de ProcessMaker directamente relacionadas con este ticket, salvo que se indique lo contrario en Observaciones.

## Criterios de aceptacion
Al ingresar un ajustador distinto al de por defecto, solo aparece el nuevo.

## Casos de prueba
Ver 'Matriz de pruebas.xlsx' en la carpeta 'Pruebas realizadas', prueba(s) asociada(s) a Ticket 0027.

## Dependencias
No identificado en este backup

## Riesgos
Ver columna Riesgo del 'Seguimiento interno de cambios.xlsx' para este ticket.

## Publicacion
No se publico nada a DEV ni PROD en esta sesion. Ver 'Instrucciones de publicacion.xlsx' dentro de 'Paquete para desarrollo' / 'Paquete para produccion'.

## Reversion
Version anterior disponible en 'Trabajo interno/Versiones anteriores'.

## Estado
Bloqueado, pendiente de confirmacion

## Evidencia
Con los templates base ya localizados (Iframe Documents/DLSU/Templates por Base Text) se confirma que el bloque de ajustador por defecto esta escrito como TEXTO FIJO directamente en el documento (no como variable condicional), lo cual es coherente con el sintoma reportado: si no existe una condicion/token que oculte este bloque fijo cuando se captura un ajustador nuevo, es logicamente imposible que el sistema lo elimine automaticamente. Sin embargo, no se encontro en el script PHP revisado ningun mecanismo (variable, condicional, o llamada a Collection) que module este bloque segun el ajustador capturado.

## Observaciones
A diferencia del Ticket 0028 (texto fijo a eliminar), este ticket requiere logica condicional (mostrar el bloque por defecto SOLO si el usuario no capturo un ajustador propio), la cual no vive en los templates ni en los scripts de este repositorio. Se requiere que el cliente indique en que pantalla/Collection se captura el 'ajustador nuevo' para poder ubicar y corregir esa logica.
