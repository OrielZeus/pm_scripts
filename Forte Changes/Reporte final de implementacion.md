# FORTE Yachts — Reporte final de implementación

## Resumen de implementación
- Tickets revisados: 28 (14 del documento de correcciones de Sandybell + 14 de los correos de Mayra).
- Tickets consolidados: 1 (Ticket 0019 se consolidó en los Tickets 0001-0014, mismo documento y misma revisora).
- Tickets con cambio real implementado: 10 (0001, 0002, 0004, 0005, 0006, 0007, 0009, 0011, 0014, y 0022 parcialmente).
- Tickets bloqueados o pendientes de confirmación: 17.
- Ningún ticket se marcó "Validado" en sentido estricto: falta ejecutar la generación real en ProcessMaker/OpenL (no hay acceso a ese ambiente desde esta sesión).

## OpenL actualizado
- Archivo base: `Forte OpenL Tablets Yachts 1.xlsx` (subido por Elmer).
- Archivo generado: `Reglas de cotización FORTE actualizadas.xlsx`.
- Cambio real: se agregó **AXA XL Reinsurance Limited** (RGRE-1301-23-C0000) en la pestaña "Countries -Cedents - Brokers", tabla `GetRegisterMexico(String Reinsurer)`, celdas AF199:AG199.
- Se comparó el archivo completo (19 pestañas, todas las fórmulas de Main Rules, Slip Rules, Clauses y Reinsurers) contra el original: **solo esas 2 celdas cambiaron**.
- No se agregó Scor Reinsurance Company (requiere tu confirmación, ver Ticket 0022) ni se tocó la lista de reaseguradores seleccionables en el dropdown (celda Y4) porque requiere un ID interno que no fue proporcionado.
- `OPENL RUNTIME VALIDATION NOT EXECUTED`.

## Documentos actualizados
- Documento base: `correcciones slip (3).docx` (con 14 comentarios de revisión de Sandybell Terrón Buendía).
- Documento generado: `Slip Puerto Rico MIC actualizado.docx`.
- 9 correcciones de texto aplicadas (Tickets 0001, 0002, 0004, 0005, 0006, 0007, 0009, 0011, 0014).
- 5 quedaron sin aplicar por requerir información adicional o revisión manual (Tickets 0003, 0008, 0010, 0012, 0013).
- Se verificó que el archivo sigue siendo un .docx válido (638 párrafos de nivel superior, 7 tablas, ZIP y XML válidos) después de los 9 cambios.

## Scripts
- 0 scripts modificados. Los scripts relevantes (26-yacht-set-information-slip.php, 8-yacht-get-dropdowns-options-openl.php, etc.) ya funcionan correctamente según lo que reciben del OpenL/documentos; el trabajo pendiente está en los datos (OpenL) y en el template base (documento), no en el código PHP.

## Pantallas y configuraciones
- No se identificó ninguna pantalla que requiera modificación con evidencia suficiente.
- El bloque de Ajustadores (Tickets 0027, 0028) probablemente vive en una Collection de ProcessMaker no incluida en el backup disponible — pendiente de ubicar.

## Archivos para el cliente
Carpeta `Forte Changes/Para compartir con el cliente/`:
- `OpenL actualizado/Reglas de cotización FORTE actualizadas.xlsx`
- `Documentos actualizados/Slip Puerto Rico MIC actualizado.docx`
- `Seguimiento de cambios/Seguimiento de cambios FORTE.xlsx`

## Trabajo interno
Carpeta `Forte Changes/Trabajo interno/`:
- `Seguimiento interno/Seguimiento interno de cambios.xlsx`
- `Scripts por actualizar/Seguimiento de scripts.xlsx`
- `Pantallas y configuraciones/Pantallas relacionadas.xlsx`
- `Jira tickets/Ticket 0001.md` … `Ticket 0028.md`
- `Pruebas realizadas/Matriz de pruebas.xlsx`
- `Versiones anteriores/` (respaldo de los 2 archivos originales antes de cualquier cambio)
- `Paquete para desarrollo/` y `Paquete para producción/` (instrucciones de publicación, sin publicar nada)
- `Respaldo y reversión/Cómo revertir.md`

## Commit
**No se realizó ningún commit.** Se encontraron 2 bloqueos independientes:

1. **Identidad Git no configurada**: ni `git config user.name` ni `user.email` están definidos, ni local ni globalmente, en el repositorio conectado. El historial del repositorio muestra 2 identidades previas usadas por ti (Elmer): `Nestor Elmer Orihuela Llusco <nestor.orihuela@processmaker.com>` y `Elmer Orihuela <89258406+elorihuela@users.noreply.github.com>`. Por instrucción explícita del prompt, no debo inventar ni asumir cuál usar — necesito que me confirmes el nombre y correo exactos a usar.
2. **El repositorio está bloqueado por otro proceso**: existe un archivo `.git/index.lock` que no pude eliminar (el sistema devuelve "Operation not permitted"). Esto normalmente ocurre cuando otro programa Git (por ejemplo, GitHub Desktop, VS Code, o una terminal Git abierta en tu computadora) tiene el repositorio abierto al mismo tiempo. `git add` falla mientras ese archivo exista.

**No se hizo `git add`, no se preparó ningún archivo en staging, y por supuesto no se hizo commit ni push.**

## Pendientes que debes ejecutar manualmente
1. Cerrar cualquier programa que tenga el repositorio Git abierto en tu computadora (GitHub Desktop, VS Code con Git, terminal, etc.) para liberar el archivo `.git/index.lock`.
2. Confirmarme el nombre y correo exactos a usar para el commit local (o configurar tú mismo `git config user.name` / `user.email` en el repositorio).
3. Una vez resuelto lo anterior, puedo intentar nuevamente el `git add` + commit local (sin push).
4. Revisar y confirmar los tickets marcados "Pendiente de confirmación" (ver `Seguimiento de cambios FORTE.xlsx`), especialmente: nombre definitivo de Scor, número máximo de Tenders a soportar, y ubicación del bloque de Ajustadores.

## Actualización — Templates base reales localizados (misma sesión, continuación)

Se corrigió un hallazgo incompleto: los templates base reutilizables del sistema SÍ existen dentro del repositorio, en `Iframe Documents/DLSU/Templates por Base Text/` (228 archivos .docx, descargados previamente con `tools/download_templates_by_basetext.ps1` desde el ambiente real de ProcessMaker, organizados por BaseText). Con esto se desbloquearon 3 tickets que antes no se podían implementar por falta del archivo real:

- **Ticket 0021** (Cláusula de cancelación con "Mexbrit" fijo): se corrigieron las 8 variantes reales de la Cláusula de Cancelación (una por cada BaseText/idioma donde existe), reemplazando las 11 ocurrencias de "Mexbrit" en cada una por el token dinámico `${YQP_COMPLETE_NAME_SOURCE}` — la misma variable que el script `26-yacht-set-information-slip.php` ya usa correctamente (línea 1483-1489) para el nombre del source. Ahora **Actualizado**.
- **Ticket 0028** (Quitar Alejandro Martínez del bloque de ajustador): se corrigieron los 7 templates reales que contienen el bloque de ajustador por defecto, eliminando el nombre "Alejandro Martínez" y el párrafo completo con su correo (`alejandro.martinez@ctplc.com`), conservando intacto a Fernando León. Ahora **Actualizado**.
- **Ticket 0025** (Sujeciones no aparecen en R12 inglés): se confirmó la causa raíz real — el template en inglés de R12 (`American Form R12`) era el único, de 10 variantes comparadas, al que le faltaba por completo el párrafo `SUBJECT TO: ${YQP_SUBJECTIVES_GUARANTEE_SLIP}`. Se insertó el párrafo faltante en la misma posición y formato que en los templates hermanos (2 archivos corregidos, incluida su copia en Upload Documents). Ahora **Actualizado**.

También se fortaleció la evidencia (sin cambiar el estado de bloqueo, por falta de información que no se debe inventar) de:
- **Ticket 0015** (Tenders no aparece en CL.328): se descarta que sea un problema de documento — el template de CL.328 sí tiene el token de Tenders. La causa está confirmada como del lado del OpenL/script.
- **Ticket 0017** (texto de cláusula de guerra desactualizado): se localizaron 14 documentos reales de cláusula de guerra por BaseText, pero sigue bloqueado porque no se cuenta con el texto "vigente" correcto para comparar y sustituir — insertarlo sin ese texto sería inventar contenido legal.
- **Ticket 0027** (lógica de ajustador duplicado): se confirma que el bloque por defecto es texto fijo en el documento (consistente con el síntoma), pero la lógica que debería ocultarlo al capturar un ajustador nuevo no se encontró en ningún script de este backup; sigue bloqueado.

**Verificación realizada en los 17 archivos corregidos:** apertura y parseo exitoso con python-docx, prueba de integridad ZIP (`testzip`), conteo de párrafos y tablas comparado contra el original (deltas exactamente los esperados: 0 para reemplazos de texto puro, +1 para la línea insertada en Ticket 0025, -1 para el párrafo de correo eliminado en Ticket 0028), y confirmación textual de que el contenido no deseado desapareció y el contenido que debía preservarse (Fernando León, demás cláusulas) sigue intacto.

**Importante:** no se modificó ningún archivo original dentro de `Iframe Documents/DLSU/Templates por Base Text/`. Se generó una copia corregida de cada uno de los 17 archivos afectados; los originales se respaldaron sin cambios en `Trabajo interno/Versiones anteriores/Templates originales (antes de corregir)/`, y las copias corregidas están en `Para compartir con el cliente/Documentos actualizados/Templates de clausulas y ajustadores corregidos/` y en ambos paquetes de publicación (`Paquete para desarrollo/` y `Paquete para producción/`). Falta que alguien con acceso al Collection real de ProcessMaker suba estas copias para reemplazar los documentos actuales del sistema.

### Commit — intento repetido
Se configuró la identidad local de Git (`Elmer Orihuela <89258406+elorihuela@users.noreply.github.com>`, solo local, confirmada por ti). Se reintentó el `git add`, pero **`.git/index.lock` sigue presente y no se puede eliminar** ("Operation not permitted") — el bloqueo sigue siendo externo (probablemente otro programa con el repositorio abierto en tu computadora). El commit local sigue pendiente hasta que ese archivo se libere.

También se detectó que un archivo dentro de la carpeta compartida (`Para compartir con el cliente/Seguimiento de cambios/Seguimiento de cambios FORTE.xlsx`) no se pudo sobrescribir con la versión actualizada por el mismo tipo de bloqueo (probablemente abierto en Excel en tu computadora). La versión más reciente de este archivo sí quedó disponible como descarga en esta conversación; se recomienda cerrarlo en tu computadora para poder sincronizar la copia en la carpeta.

## Segunda actualización — Confirmación de que varias "correcciones" del documento en realidad venían del Excel

Se verificó, celda por celda contra el OpenL real, cada uno de los 9 cambios que se habían marcado como "Actualizado" solo en `Slip Puerto Rico MIC actualizado.docx`. Resultado:

**Sí venían del Excel (y por lo tanto el documento solo no bastaba) — ahora corregidos también en el Excel:**
- **Ticket 0004** (endoso de deducible por viento huracanado): texto encontrado 100% idéntico en la pestaña "Clauses", filas 39 y 51 (columna H). Corregido en ambas filas.
- **Ticket 0006** (códigos de forma de póliza): texto encontrado 100% idéntico en "Clauses", filas 33 y 58. Corregido en ambas.
- **Ticket 0009** ("Hurricane and Lightning" → "and/or"): encontrado en la pestaña "Slip Rules", fila 106 — dentro de una celda que contiene texto propio del motor de reglas OpenL (no una fórmula real de Excel). Corregido con cuidado especial para que Excel/LibreOffice no la reinterprete como fórmula al guardar (se verificó que el tipo de celda no cambió).
- **Ticket 0014** (cláusula de sanciones desactualizada): el cuerpo completo de esta cláusula vive en los mismos 3 templates base en inglés que ya se habían editado para el Ticket 0021 (`21-INSTITUTE-RADIOACTIVE-CONTAMINATION.docx`, `22-GENERAL-CLAUSES.docx`, `24-GENERAL-CLAUSES.docx`). Los 3 tenían la versión desactualizada (sin "United Kingdom"/"Switzerland", con "(re)insurer"). Corregidos con el texto exacto que aprobó Sandybell. Se encontraron 5 templates adicionales en español con el mismo problema, pero con una redacción distinta a la inglesa — no se tocaron por falta de texto aprobado en español.

**Verificados y confirmados como correcciones de documento único (correctamente dejados solo ahí, no requieren cambio en Excel):**
- Ticket 0001 (INTEREST): es un campo de texto libre por cotización, no viene de una tabla de OpenL.
- Ticket 0005 (Section E/F): el fragmento que sí viene de OpenL ("Federal Longshoremen's...") no contiene la letra de sección; el resto de la frase no existe en ningún script, template ni Excel — es texto específico de este documento.
- Ticket 0007 (doble dos puntos "Hull::"): no se encontró ningún "Hull:" con dos puntos en ningún template ni en el Excel — parece un error de tipeo específico de este documento.
- Ticket 0011 (frase de deducible RDC): el texto exacto solicitado no existe en el Excel ni en los templates.

**Verificación de las ediciones al Excel:** diff celda por celda de las 19 pestañas completas confirmó que únicamente cambiaron las 5 celdas esperadas de esta ronda (más las 2 del Ticket 0022 ya reportadas). No se ejecutó el recálculo automático de fórmulas de LibreOffice sobre este archivo en esta ronda porque se comprobó que ese recálculo reinterpreta por error el texto de reglas OpenL como fórmulas de Excel inválidas y las corrompe (cambia mayúsculas/minúsculas de nombres de funciones internas) — se verificó manualmente en su lugar, celda por celda, que el archivo quedó íntegro.

Se actualizaron `Slip Puerto Rico MIC actualizado.docx` (sin cambios adicionales, ya tenía el texto correcto), `Reglas de cotizacion FORTE actualizadas.xlsx` (5 celdas nuevas) y los 3 templates base en inglés de la Cláusula de Sanciones, con sus respaldos y copias en los paquetes de siempre.
