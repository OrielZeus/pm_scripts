# FORTE Yachts — Deployment Runbook (borrador, pendiente de ejecución)

**Importante:** este runbook es un PLAN, no un registro de despliegue ejecutado. Ningún paso de este documento se llevó a cabo en esta auditoría. Ver también la pestaña `Deployment_Plan` del tracker maestro para la versión estructurada en tabla.

## Prerrequisito general
Generar un backup real y completo de PROD (`1120-forte-code-backup-batch.php`, `action=export_all`, `target=prod`) ANTES de tocar cualquier archivo, para tener una línea base de rollback confiable. Sin esto, cualquier publicación a PROD es de alto riesgo.

## Orden sugerido de implementación

### 1. Reaseguradores (REQ-008) — Riesgo bajo/medio
1. Confirmar con Mayra si "Scor Reinsurance Company" debe ser una fila nueva o reutilizar una de las 3 variantes regionales existentes.
2. Backup de las pestañas `Countries -Cedents - Brokers` y `Reinsurers` del OpenL.
3. Agregar fila(s) con nombre + RGRE correcto.
4. Si se requiere firma personalizada, agregar bloque HTML en `Reinsurers`.
5. Prueba: cotizar seleccionando cada reasegurador nuevo, confirmar RGRE y firma en el PDF resultante.
6. Aprobación: Mayra confirma visualmente el PDF.
7. Rollback: restaurar las 2 pestañas desde el backup del paso 2.

### 2. Cláusula de cancelación "Mexbrit" (REQ-007) — Riesgo bajo (una vez ubicado el template)
1. Ubicar el template base real (pendiente, ver Acciones manuales).
2. Backup del template base.
3. Reemplazar el texto fijo "Mexbrit"/"MXB" por la variable `YQP_COMPLETE_NAME_SOURCE`, replicando el patrón ya usado en `26-yacht-set-information-slip.php` línea 1483-1489.
4. Prueba: cotizar con `YQP_SOURCE` = "Forte UW LLC" y también con "Mexbrit" (regresión), confirmar que ambos casos muestran el nombre correcto.
5. Aprobación: Mayra confirma ambos PDFs.
6. Rollback: restaurar el template desde el backup del paso 2.

### 3. Tenders (REQ-010) — Riesgo alto (cambio estructural)
1. Decisión de negocio: ¿hasta cuántos tenders debe soportar el producto de forma permanente (7, 10, ilimitado)?
2. Backup de `Main Rules` y `Slip Rules`.
3. Ampliar el datatype `FinalPremium` y la tabla `BusinessFinalPremium` con los campos/filas nuevos (`Tender_6`, `Tender_7`, ...).
4. Ampliar `Slip Rules` (filas condicionadas a `MaxTenders >= N`).
5. Ajustar `26-yacht-set-information-slip.php` en las secciones que iteran explícitamente 1..5.
6. Prueba: caso real con 7 tenders (o equivalente), MÁS prueba de regresión con 1-5 tenders (deben comportarse exactamente igual que antes).
7. Aprobación: Mayra + equipo técnico confirman.
8. Rollback: restaurar `Main Rules`/`Slip Rules` y revertir el script.

### 4. Cláusulas opcionales (REQ-002, REQ-004, REQ-009) — Riesgo medio (tabla grande, 1020 filas)
1. Obtener caso de prueba real renovación vs. nuevo negocio de Mayra.
2. Backup de la pestaña `Clauses`.
3. Localizar y corregir la fila cortada de "motores internos" (REQ-004).
4. Investigar y corregir la diferencia renovación/nuevo (REQ-002).
5. Insertar las 4 cláusulas nuevas al final, antes de firmas, de forma general para todo BaseText/idioma (REQ-009) — bloqueado hasta tener el texto en español de depreciación <20 años.
6. Prueba: cotizar negocio nuevo y renovación con mismos parámetros, comparar clausulado.
7. Aprobación: Mayra confirma paridad y textos completos.
8. Rollback: restaurar pestaña `Clauses`.

### 5. Ajustadores (REQ-013, REQ-014) — Bloqueado
No se puede planear el despliegue hasta ubicar el archivo/Collection real. Una vez ubicado, seguir el mismo patrón: backup → corregir duplicado + quitar Alejandro Martínez → probar agregando un ajustador nuevo → confirmar con Mayra → rollback disponible.

## Criterio general de aprobación para publicar a PROD
- Todos los `Test_Cases` relacionados deben estar en estado `PASSED` con evidencia (PDF) adjunta.
- La prueba de regresión general (TC-006) debe pasar sin diferencias en un negocio ya cotizado previamente.
- Mayra (o quien ella designe) debe confirmar explícitamente cada cambio antes de publicar a PROD.
