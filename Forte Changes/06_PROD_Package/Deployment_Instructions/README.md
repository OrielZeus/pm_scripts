# 06_PROD_Package — Instrucciones

**No se preparó ningún archivo para PROD.** Motivo: (1) esta auditoría no implementó cambios de código/datos, solo los documentó; (2) no existe un backup utilizable de PROD contra el cual planear un despliegue seguro — la carpeta `Forte_Prod_Files` del repositorio conectado está prácticamente vacía.

## Prerrequisito obligatorio antes de cualquier publicación a PROD
1. Ejecutar la herramienta de backup (`1120-forte-code-backup-batch.php`, acción `export_all`, `target=prod`) para obtener una copia real y completa de PROD.
2. Repetir el análisis de `02_Analysis` comparando esa copia real de PROD contra DEV.
3. Solo entonces preparar `Files_To_Update`, `New_Files`, `Reference_Old_Versions` e `ID_Mapping` con base en diferencias reales verificadas.

`Reference_Old_Versions`: vacío — no hay versión de PROD contra la cual generar una referencia de rollback fiable.
