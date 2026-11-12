# Quitar nombre y correo de Alejandro Martinez del bloque de Ajustadores

## Contexto
Fuente: Correo 3 de Mayra Aguilar (captura marcada)
Numero original: Punto 14

## Solicitud del cliente
En la parte de los Ajustadores, quitar el nombre de Alejandro Martinez asi como su correo.

## Alcance
Eliminar 'Alejandro Martinez' y 'alejandro.martinez@ctplc.com' del bloque de ajustador.

## Archivos afectados
- 7 templates base '01-Informacion/General...' (uno por cada BaseText donde aparece el bloque de ajustador): Cláusulas del Instituto CL.328 (version base y version con exclusiones); Forma Americana R12 (version base y Panama); Institute Yacht Clauses CL.328 deleted; Póliza Estar Seguros (Venezuela); Según Póliza POL 1 2013 0707

## OpenL
- Workbook: Reglas de cotizacion FORTE actualizadas.xlsx (cuando aplica)
- Regla: N/A
- Cambio requerido: ver seccion Alcance

## Documentos
- Documento: 7 templates base '01-Informacion/General...' (uno por cada BaseText donde aparece el bloque de ajustador): Cláusulas del Instituto CL.328 (version base y version con exclusiones); Forma Americana R12 (version base y Panama); Institute Yacht Clauses CL.328 deleted; Póliza Estar Seguros (Venezuela); Según Póliza POL 1 2013 0707
- Cláusula/seccion: Bloque de Ajustador
- Cambio requerido: ver seccion Alcance

## Scripts
- Script: No aplica (correccion de texto fijo en documento, no de logica)
- ID: N/A
- Cambio requerido: Ver notas del ticket; no se modifico codigo en esta pasada salvo que se indique lo contrario en Observaciones.

## Pantallas y configuraciones
No se identificaron pantallas de ProcessMaker directamente relacionadas con este ticket, salvo que se indique lo contrario en Observaciones.

## Criterios de aceptacion
El bloque de ajustador muestra unicamente a Fernando Leon y su correo.

## Casos de prueba
Ver 'Matriz de pruebas.xlsx' en la carpeta 'Pruebas realizadas', prueba(s) asociada(s) a Ticket 0028.

## Dependencias
No aplica (correccion de texto fijo en documento, no de logica)

## Riesgos
Ver columna Riesgo del 'Seguimiento interno de cambios.xlsx' para este ticket.

## Publicacion
Copia corregida incluida en 'Paquete para desarrollo' y 'Paquete para produccion'; falta que alguien con acceso al Collection/ambiente de ProcessMaker la suba para reemplazar el documento actual. Ver 'Instrucciones de publicacion.xlsx'.

## Reversion
Version anterior disponible en 'Trabajo interno/Versiones anteriores'.

## Estado
Actualizado

## Evidencia
Se localizaron los 7 templates base reales con el bloque de ajustador. Cada uno tenia la linea de texto 'Alejandro Martínez / Fernando León' y, en un parrafo/hipervinculo separado, el correo 'alejandro.martinez@ctplc.com'. Se elimino el segmento 'Alejandro Martínez / ' del renglon de nombres (queda solo 'Fernando León') y se elimino por completo el parrafo del hipervinculo de su correo. Se conservo intacto 'Fernando León' y 'Fernando.Leon@ctplc.com' en los 7 archivos. Verificado: 0 ocurrencias de 'Alejandro' restantes; conteo de parrafos disminuyo en exactamente 1 por archivo (el parrafo de correo eliminado); tablas sin cambios; ZIP/XML validos.

## Observaciones
Se genero una copia corregida de cada uno de los 7 templates (no se modificaron los originales). Copias en 'Para compartir con el cliente/Documentos actualizados/Templates de clausulas y ajustadores corregidos' y en los paquetes DEV/PROD; originales respaldados en 'Trabajo interno/Versiones anteriores/Templates originales (antes de corregir)'. La logica de duplicacion del ajustador (Ticket 0027) sigue bloqueada por separado: este ticket solo corrige el texto fijo, no la condicion que decide cuando mostrarlo.
