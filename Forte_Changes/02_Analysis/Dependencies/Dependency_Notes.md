# Notas de dependencias

Ver la matriz completa y estructurada en `03_Change_Tracker/FORTE_Master_Change_Tracker.xlsx`, pestaña `Dependency_Map`.

## Cadena general confirmada (para las clausulas / documento)
`YQP_COUNTRY_BUSINESS + YQP_LANGUAGE + YQP_REASSURED_CEDENT + YQP_BASE_TEXT`
→ OpenL `GetClauses` / `GetPreliminaryClauses` (pestaña `Clauses`, 1020 filas)
→ `26-yacht-set-information-slip.php` (arma `PM_OPEN_CLAUSES`, `PM_OPEN_PRELIMINARY_CLAUSES`)
→ `52-yacht-clear-hidden-variables-and-set-clauses-selected.php` (`YQP_CLAUSES_SELECTED`)
→ Template del slip (no localizado en este repositorio)
→ Slip PDF generado
→ Ambiente: DEV (evidencia parcial) / PROD (desconocido, backup vacío)

## Cadena de Tenders
`YQP_TENDERS_INFORMATION` (grid, hasta N filas ingresadas por el usuario)
→ `26-yacht-set-information-slip.php` calcula `$maxTender`, `TenderSumInsuredN`, `TenderPercentageN` (N=1..5 codificado explícitamente)
→ OpenL `Main Rules!BusinessFinalPremium` (Tender_1..Tender_5 fijos)
→ OpenL `Slip Rules` (tabla de resumen, filas condicionadas a `MaxTenders >= N`, N=1..5)
→ Slip PDF: solo puede mostrar hasta 5 tenders aunque el usuario haya ingresado más.

## Cadena de firma dinámica de fuente (patrón de referencia para REQ-007)
`YQP_SOURCE` + `YQP_COMPLETE_NAME_SOURCE`
→ usado en `26-yacht-set-information-slip.php`, `40-yacht-generate-slip.php`, `46-yacht-prepare-documentation.php`
→ NO usado en el párrafo de cláusula de cancelación del template base (texto fijo "Mexbrit")

## Piezas sin ubicar (rompen la cadena de trazabilidad)
- Template base R12 (.docx/.dotx) — ID NOT FOUND
- Template base CL.328 — ID NOT FOUND
- Texto/Collection de la cláusula de guerra completa — ID NOT FOUND
- Collection o documento base del bloque de Ajustadores — ID NOT FOUND
- Slip de referencia de Sandybell (Puerto Rico) — no incluido en los archivos disponibles
