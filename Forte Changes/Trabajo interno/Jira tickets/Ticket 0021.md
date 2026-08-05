# Clausula de cancelacion con 'Mexbrit'/'MXB' fijo en vez de dinamico

## Contexto
Fuente: Correo 1 de Mayra Aguilar
Numero original: Punto 7

## Solicitud del cliente
De la clausula de cancelacion, ajustador, donde aparece Mexbrit o MXB, modificar por el source correspondiente que muchos casos puede ser Forte UW LLC.

## Alcance
Usar la variable dinamica de source (ya usada correctamente en otra seccion del mismo script) en el parrafo de cancelacion del template base.

## Archivos afectados
- 8 templates base de Clausula de Cancelacion (uno por cada BaseText/idioma donde existe la clausula): AMERICAN YACHT FORM R12 with Sections/22-GENERAL-CLAUSES.docx; Cláusulas del Instituto CL.328 (version base y version con exclusiones)/24-Clausula-del-instituto-de-exclusion-de-contaminacion-radioactiva.docx; Forma Americana R12 (version base y Panama)/21-Clausulas-Generales.docx; Institute Yacht Clauses CL.328 deleted/24-GENERAL-CLAUSES.docx; Según Póliza POL 1 2013/09-Clausula-del-instituto-de-exclusion-de-contaminacion-radioactiva.docx; Upload Documents/9712__22-GENERAL-CLAUSES.docx

## OpenL
- Workbook: Reglas de cotizacion FORTE actualizadas.xlsx (cuando aplica)
- Regla: N/A
- Cambio requerido: ver seccion Alcance

## Documentos
- Documento: 8 templates base de Clausula de Cancelacion (uno por cada BaseText/idioma donde existe la clausula): AMERICAN YACHT FORM R12 with Sections/22-GENERAL-CLAUSES.docx; Cláusulas del Instituto CL.328 (version base y version con exclusiones)/24-Clausula-del-instituto-de-exclusion-de-contaminacion-radioactiva.docx; Forma Americana R12 (version base y Panama)/21-Clausulas-Generales.docx; Institute Yacht Clauses CL.328 deleted/24-GENERAL-CLAUSES.docx; Según Póliza POL 1 2013/09-Clausula-del-instituto-de-exclusion-de-contaminacion-radioactiva.docx; Upload Documents/9712__22-GENERAL-CLAUSES.docx
- Cláusula/seccion: Clausula de cancelacion
- Cambio requerido: ver seccion Alcance

## Scripts
- Script: 26-yacht-set-information-slip.php (linea 1483-1489, patron de referencia usado para el reemplazo), 40-yacht-generate-slip.php
- ID: N/A
- Cambio requerido: Ver notas del ticket; no se modifico codigo en esta pasada salvo que se indique lo contrario en Observaciones.

## Pantallas y configuraciones
No se identificaron pantallas de ProcessMaker directamente relacionadas con este ticket, salvo que se indique lo contrario en Observaciones.

## Criterios de aceptacion
La clausula de cancelacion usa el nombre de source correcto segun el negocio (dinamico), no un texto fijo.

## Casos de prueba
Ver 'Matriz de pruebas.xlsx' en la carpeta 'Pruebas realizadas', prueba(s) asociada(s) a Ticket 0021.

## Dependencias
26-yacht-set-information-slip.php (linea 1483-1489, patron de referencia usado para el reemplazo), 40-yacht-generate-slip.php

## Riesgos
Ver columna Riesgo del 'Seguimiento interno de cambios.xlsx' para este ticket.

## Publicacion
Copia corregida incluida en 'Paquete para desarrollo' y 'Paquete para produccion'; falta que alguien con acceso al Collection/ambiente de ProcessMaker la suba para reemplazar el documento actual. Ver 'Instrucciones de publicacion.xlsx'.

## Reversion
Version anterior disponible en 'Trabajo interno/Versiones anteriores'.

## Estado
Actualizado

## Evidencia
Se localizaron los templates base reales (descargados previamente por tools/download_templates_by_basetext.ps1 en Iframe Documents/DLSU/Templates por Base Text). Las 8 variantes de la Clausula de Cancelacion tenian 'Mexbrit' fijo (11 ocurrencias cada una). Se reemplazo cada ocurrencia por el token dinamico ${YQP_COMPLETE_NAME_SOURCE}, que es la MISMA variable que el script 26-yacht-set-information-slip.php ya usa correctamente en la linea 1483-1489 para el nombre del source en el bloque de firma del reasegurador, y que sigue el mismo patron de token ${YQP_...} ya usado en estos mismos templates (ej. ${YQP_PAYMENT_TERMS}). Verificado: 0 ocurrencias de 'Mexbrit' restantes en las 8 copias corregidas; conteo de parrafos y tablas identico al original; ZIP/XML validos.

## Observaciones
Se genero una copia corregida de cada uno de los 8 templates (no se modificaron los originales). Copias en 'Para compartir con el cliente/Documentos actualizados/Templates de clausulas y ajustadores corregidos' y en los paquetes DEV/PROD; originales respaldados en 'Trabajo interno/Versiones anteriores/Templates originales (antes de corregir)'. Falta que alguien con acceso al Collection de ProcessMaker suba estas 8 copias para reemplazar los documentos actuales (ver Instrucciones de publicacion).
