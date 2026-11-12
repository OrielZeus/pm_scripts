# 08_Rollback — Instrucciones

Como no se modificó ningún archivo original ni se publicó ningún cambio, **no hay nada que revertir todavía**. Esta carpeta documenta el procedimiento de rollback que deberá seguirse UNA VEZ que se implementen los cambios descritos en `Deployment_Plan`:

1. Antes de editar cualquier archivo (OpenL, template, script), copiar el original a `08_Rollback/Previous_OpenL` (o `Previous_Templates` / `Previous_Scripts` según corresponda), conservando el nombre original + sufijo `_PRE_CHANGE_<fecha>`.
2. Registrar en `08_Rollback/Previous_ID_Mapping` cualquier ID de documento/Collection que se vaya a reemplazar, antes de reemplazarlo.
3. Si una validación en DEV falla (`DEV FAILED`) o una validación en PROD falla (`PROD FAILED`), restaurar el archivo desde esta carpeta y volver a publicar la versión anterior.
4. Documentar en `Version_History` (pestaña del tracker maestro) la fecha y el motivo del rollback.

Carpetas `Previous_OpenL`, `Previous_Templates`, `Previous_Scripts`, `Previous_ID_Mapping`: vacías por ahora (no hubo cambios que respaldar en esta auditoría).
