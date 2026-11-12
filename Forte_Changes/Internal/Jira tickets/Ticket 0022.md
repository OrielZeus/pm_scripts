# Agregar reaseguradores AXA XL Reinsurance Limited y Scor Reinsurance Company

## Contexto
Fuente: Correo 2 de Mayra Aguilar (Solicitudes Nuevas)
Numero original: Punto 8

## Solicitud del cliente
Incluir como Reaseguradores a: AXA XL Reinsurance Limited (RGRE-1301-23-C0000), Scor Reinsurance Company (RGRE-418-97-300170).

## Alcance
Agregar ambos reaseguradores a la tabla de registro de Mexico del OpenL.

## Archivos afectados
- Reglas de cotizacion FORTE actualizadas.xlsx

## OpenL
- Workbook: Reglas de cotizacion FORTE actualizadas.xlsx (cuando aplica)
- Regla: GetRegisterMexico(String Reinsurer) - pestana Countries -Cedents - Brokers, columnas AF:AG
- Cambio requerido: ver seccion Alcance

## Documentos
- Documento: Reglas de cotizacion FORTE actualizadas.xlsx
- Cláusula/seccion: N/A
- Cambio requerido: ver seccion Alcance

## Scripts
- Script: 8-yacht-get-dropdowns-options-openl.php
- ID: Pendiente de ID generado por ProcessMaker (para que sea seleccionable en el dropdown)
- Cambio requerido: Ver notas del ticket; no se modifico codigo en esta pasada salvo que se indique lo contrario en Observaciones.

## Pantallas y configuraciones
No se identificaron pantallas de ProcessMaker directamente relacionadas con este ticket, salvo que se indique lo contrario en Observaciones.

## Criterios de aceptacion
Ambos reaseguradores muestran su RGRE correcto; ambos son seleccionables en el flujo de cotizacion.

## Casos de prueba
Ver 'Matriz de pruebas.xlsx' en la carpeta 'Pruebas realizadas', prueba(s) asociada(s) a Ticket 0022.

## Dependencias
8-yacht-get-dropdowns-options-openl.php

## Riesgos
Ver columna Riesgo del 'Seguimiento interno de cambios.xlsx' para este ticket.

## Publicacion
No se publico nada a DEV ni PROD en esta sesion. Ver 'Instrucciones de publicacion.xlsx' dentro de 'Paquete para desarrollo' / 'Paquete para produccion'.

## Reversion
Version anterior disponible en 'Trabajo interno/Versiones anteriores'.

## Estado
Parcialmente actualizado

## Evidencia
AXA XL Reinsurance Limited agregado en fila 199 (columnas AF/AG) con su RGRE exacto. Verificado que solo esas 2 celdas cambiaron en todo el workbook.

## Observaciones
AXA XL: agregado a la tabla de RGRE (GetRegisterMexico). NO se agrego a la lista de reaseguradores seleccionables (celda Y4, un texto unico con miles de caracteres tipo 'ID|Nombre|ID|Nombre|...') porque esa lista requiere un ID interno numerico que no fue proporcionado y no debe inventarse. SCOR: NO se agrego nada -- el mismo RGRE-418-97-300170 ya esta asignado a 3 variantes regionales existentes (Mexico/Miami/Paris); se requiere que el cliente confirme si debe ser una fila nueva o reutilizar una existente antes de tocar los datos.
