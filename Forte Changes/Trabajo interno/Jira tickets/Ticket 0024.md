# Tenders limitados a 5 en R12 (caso con 7 tenders)

## Contexto
Fuente: Correo 2 de Mayra Aguilar (Solicitudes Antiguas)
Numero original: Punto 10

## Solicitud del cliente
En el tema de los Tenders cuando utilizamos la R12, aparece la tabla pero esta limitado a 5... (Ver quoted 1892 'Pantera Sunset, LLC').

## Alcance
Ampliar la estructura de Tenders en el OpenL para soportar mas de 5.

## Archivos afectados
- Reglas de cotizacion FORTE actualizadas.xlsx

## OpenL
- Workbook: Reglas de cotizacion FORTE actualizadas.xlsx (cuando aplica)
- Regla: Main Rules!BusinessFinalPremium (datatype FinalPremium, solo Tender_1..Tender_5)
- Cambio requerido: ver seccion Alcance

## Documentos
- Documento: Reglas de cotizacion FORTE actualizadas.xlsx
- Cláusula/seccion: N/A
- Cambio requerido: ver seccion Alcance

## Scripts
- Script: 26-yacht-set-information-slip.php (itera 1..5 explicitamente)
- ID: N/A
- Cambio requerido: Ver notas del ticket; no se modifico codigo en esta pasada salvo que se indique lo contrario en Observaciones.

## Pantallas y configuraciones
No se identificaron pantallas de ProcessMaker directamente relacionadas con este ticket, salvo que se indique lo contrario en Observaciones.

## Criterios de aceptacion
El slip muestra correctamente hasta 7 (o el numero maximo definido) tenders.

## Casos de prueba
Ver 'Matriz de pruebas.xlsx' en la carpeta 'Pruebas realizadas', prueba(s) asociada(s) a Ticket 0024.

## Dependencias
26-yacht-set-information-slip.php (itera 1..5 explicitamente)

## Riesgos
Ver columna Riesgo del 'Seguimiento interno de cambios.xlsx' para este ticket.

## Publicacion
No se publico nada a DEV ni PROD en esta sesion. Ver 'Instrucciones de publicacion.xlsx' dentro de 'Paquete para desarrollo' / 'Paquete para produccion'.

## Reversion
Version anterior disponible en 'Trabajo interno/Versiones anteriores'.

## Estado
Requiere confirmacion

## Evidencia
Confirmado en el OpenL: el datatype y la tabla de calculo de prima solo definen 5 tenders, es una limitacion estructural real.

## Observaciones
No se modifico: es un cambio estructural (agregar campos al datatype, filas a la regla, y ajustar el script en varias secciones), no una edicion de datos. Se requiere primero que el negocio confirme cuantos tenders debe soportar el producto de forma permanente.
