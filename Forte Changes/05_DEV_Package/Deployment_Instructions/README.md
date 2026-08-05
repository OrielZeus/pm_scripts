# 05_DEV_Package — Instrucciones

**Esta auditoría no generó archivos listos para desplegar.** Es una fase de análisis/documentación (Fases 1-8 del prompt maestro); ningún archivo original de OpenL, template o script fue modificado.

Lo que SÍ contiene esta carpeta:
- `Files_To_Update`: vacío — no se determinó con evidencia suficiente el archivo exacto a reemplazar para ningún requerimiento (faltan templates base, ver `Risks_and_Pending`).
- `New_Files`: vacío — no se generó ningún archivo nuevo.
- `ID_Mapping`: ver `03_Change_Tracker/FORTE_Master_Change_Tracker.xlsx` pestaña `Document_ID_Mapping` (todos los IDs relevantes están marcados `ID NOT FOUND`).
- `Deployment_Instructions`: este archivo + la pestaña `Deployment_Plan` del tracker maestro, con el orden sugerido de implementación (1. Reaseguradores, 2. Cláusula Mexbrit, 3. Tenders, 4. Cláusulas opcionales, 5. Ajustadores).
- `Validation_Checklist`: ver abajo.

## Validation_Checklist (a ejecutar cuando exista ambiente DEV accesible)
- [ ] El workbook OpenL abre sin errores tras cualquier edición.
- [ ] Todas las 19 pestañas siguen presentes, en el mismo orden.
- [ ] Las tablas `BusinessFinalPremium`, `GetClauses`, `GetPreliminaryClauses`, `GetReinsurers`, `GetReinsurerSign` conservan su firma (nombre y parámetros).
- [ ] Un negocio cotizado ANTES del cambio, vuelto a cotizar con los mismos datos DESPUÉS del cambio, produce el mismo resultado (prueba de regresión, ver `Test_Cases`, TC-006).
- [ ] Cada requerimiento tiene al menos un caso de prueba ejecutado con evidencia (PDF del slip) antes de considerarse `DEV VALIDATED`.
