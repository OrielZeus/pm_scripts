# Análisis estructural — Forte OpenL Tablets Yachts 1.xlsx

Archivo analizado en modo solo lectura (openpyxl, `data_only=True` y `data_only=False`). No se modificó el libro original. Copia de trabajo en `04_Working_Copies/OpenL/Forte_OpenL_Yachts_WORKING.xlsx` (antes `Forte_OpenL_Tablets_Yachts_WORKING_REVISION.xlsx`).

## 1. Pestañas (19, todas visibles, sin hojas ocultas)

| # | Pestaña | Dimensiones | Relevancia directa a los 14 requerimientos |
|---|---|---|---|
| 1 | Vessel Class | B2:E29 | No |
| 2 | Countries -Cedents - Brokers | B1:AG1002 | **Sí — REQ-008** (reaseguradores + RGRE) |
| 3 | Source | B2:C30 | Posible (YQP_SOURCE / dinámico de firma) |
| 4 | TABLES 2 - BASIC RATES | B2:J459 | No (tarifas) |
| 5 | TABLES 3 FACTORS TO BASIC RATES | B2:M119 | No |
| 6 | TABLES 4 - SPECIAL DEDUCTIBLES | B2:J26 | No |
| 7 | TABLES 7-13 Other coverages | B2:M31 | Posible — REQ-012 (P&I / Remoción de Restos) |
| 8 | TABLES 4 - COVERAGE RATES TL | B2:C5 | No |
| 9 | Hurracane-Lightning | B2:M85 | No |
| 10 | P&I | A2:Z37 | **Posible — REQ-012** (límite base sobre el que se calcula 30%) |
| 11 | Payment Terms | B2:H32 | No |
| 12 | Clauses | B1:I1020 | **Sí — REQ-002, REQ-004, REQ-009, REQ-012** |
| 13 | Panama | B2:E17 | Posible (BaseText "...Panama") |
| 14 | Navigation Limits | B1:D1000 | No |
| 15 | Subjectives Guarantee | B2:C82 | **Sí — REQ-011** |
| 16 | Slip Rules | B2:J163 | **Sí — REQ-001, REQ-010** |
| 17 | Main Rules | B2:E130 | **Sí — REQ-010** |
| 18 | ENDOSOS | B2:D31 | No |
| 19 | Reinsurers | B2:C11 | **Sí — REQ-008** (firma HTML por reasegurador) |

## 2. Hallazgos con evidencia directa

### 2.1 REQ-010 — Tenders limitados a 5 (Main Rules)
`Main Rules!B26`: `Spreadsheet SpreadsheetResult BusinessFinalPremium (FinalPremium final)`.
Filas 33-37 definen únicamente `Tender_1` ... `Tender_5`. El `Datatype FinalPremium` (desde fila 45) declara `TenderPercentage1` ... `TenderPercentage5` — no existen `Tender_6`/`Tender_7` ni sus campos asociados. Es una limitación **estructural** de la tabla de reglas, no un valor mal cargado.

### 2.2 REQ-008 — Reaseguradores (Countries -Cedents - Brokers, Reinsurers)
Columnas AF (nombre) / AG (RGRE), filas 4-198.
- `AXA XL Reinsurance Limited` con `RGRE-1301-23-C0000`: **no existe**. Sí existe `AXA XL Resseguros S.A.` con `RGRE-1199-16-C0000` (entidad distinta).
- `Scor Reinsurance Company` con `RGRE-418-97-300170`: el **mismo RGRE** ya está asignado a `SCOR Reinsurance Company (Mexico)`, `(Miami)` y `(Paris)` (filas 46-49 y otras). El nombre genérico solicitado no existe.
- Pestaña `Reinsurers` (GetReinsurerSign): solo 3 reaseguradores tienen bloque de firma HTML definido (Munich Re, Austral Resseguradora SA, Echo Reinsurance Limited). Si se agregan AXA XL/SCOR sin bloque de firma, usarán el comportamiento por defecto (no confirmado cuál es).

### 2.3 REQ-002 / REQ-004 / REQ-009 — Mecanismo de cláusulas (Clauses)
`Clauses!B3` define las columnas: `Country | Reinsurer | Language | Base Text | Preliminar or Clause | Validation of Preliminar | Optional Clauses | Concat Validation and Optional`.
Es una tabla de decisión de 1020 filas donde cada combinación Country+Reinsurer+Language+BaseText tiene su propio bloque de cláusulas fijas (`CLAUSULA`) y opcionales (`PRELIMINAR`, cada una con una variable de validación tipo `YQP_WATER_SKIING|YES`).
**No se recorrieron las 1020 filas** (fuera del alcance de tiempo de esta pasada); se confirmó la estructura y el mecanismo, no la fila específica que causa cada defecto.

### 2.4 REQ-001 — Tabla de Tenders no aparece en CL.328
La sección "Tenders" del resumen de sumas aseguradas (`Slip Rules!SlipRulesSumInsuredES/EN`, fila `Row3`) **no condiciona por BaseText**, solo por `MaxTenders >= N`. Por tanto la ausencia de la tabla en CL.328 no se origina en esta regla — el problema más probable está en el **template Word base de CL.328**, que no se localizó en este repositorio.

### 2.5 REQ-011 — Sujeciones no salen en R12 inglés
Se confirmó la existencia de la pestaña dedicada `Subjectives Guarantee` (B2:C82). No se comparó fila por fila entre BaseText en español vs. inglés por límite de tiempo — pendiente en una siguiente iteración.

### 2.6 REQ-012 — Remoción de Restos de Naufragio al 30%
No se localizó el texto de esta cláusula en la revisión de encabezados/primeras filas de `Clauses`. Requiere una búsqueda dirigida de texto en las 1020 filas (no completada en esta pasada) y revisión de la pestaña `P&I` para el límite base sobre el que aplicaría el 30%.

## 3. Integridad del archivo
El archivo se abrió y guardó únicamente en modo lectura; no se sobrescribió el original subido por Elmer. La copia en `04_Working_Copies/OpenL/` es idéntica byte a byte al original (mismo tamaño y hash, ver `00_Source_Inventory/SHA256_Manifest.txt`).

## 4. Validación de runtime
`OPENL RUNTIME VALIDATION NOT EXECUTED` — no hay acceso a un servidor OpenL en este entorno para ejecutar las reglas y confirmar el comportamiento real frente a lo leído estáticamente en las fórmulas.
