# Análisis de scripts — Forte Yachts (backup DEV, 2026-04-14)

Ubicación: `Iframe Documents/Forte Code Backups/Forte_Dev_Files/scripts/` (154 archivos .php + 151 metadatos JSON en `scripts/meta/`).
`Forte_Prod_Files/scripts/` está prácticamente vacío (solo `.gitkeep` y 1 archivo en `meta/`) — no existe evidencia de PROD para comparar.

## Scripts identificados y su rol (evidencia real, no inferida por nombre solamente)

| Script | Rol confirmado leyendo el código | Requerimientos relacionados |
|---|---|---|
| `26-yacht-set-information-slip.php` (1534 líneas) | "Set Information Slip": arma clausulas (`PM_OPEN_CLAUSES`, `PM_OPEN_PRELIMINARY_CLAUSES`), calcula tenders, arma firma de reaseguradores dinámica | REQ-001,002,004,007,009,010 |
| `52-yacht-clear-hidden-variables-and-set-clauses-selected.php` | Combina `YQP_PRELIMINARY_CLAUSES` + `YQP_CLAUSES_INCLUDED` en `YQP_CLAUSES_SELECTED` | REQ-002,004,009 |
| `31-yacht-clear-hidden-variables.php` | Limpieza de variables ocultas, incluye lógica condicional sobre `YQP_SOURCE != "Other"` | REQ-007 (indirecto) |
| `66-yacht-set-forte-address.php` | Llama a la regla OpenL `GetSignForteAddress` para obtener HTML de dirección/firma dinámica | REQ-007 (patrón de referencia) |
| `40-yacht-generate-slip.php` | Genera el documento final; arma el mismo bloque dinámico de firma de reaseguradores que 26- | REQ-007 |
| `51-templates-get-base-texts.php` (1.7 KB) | Lista de BaseText disponibles (R12, CL.328, Panamá, etc.) | REQ-001,002 |
| `8-yacht-get-dropdowns-options-openl.php` | "Get Dropdowns Options" vía OpenL (`GetCountries`, `GetBrokers`, `GetCedents`, `GetReinsurers`) | REQ-008 (indirecto) |
| `15-yacht-get-openl-rules.php` | "Apply Rate Model" — invoca reglas de tarifas (fuera del foco de los 14 requerimientos, que son de cláusulas/documento) | No aplica directo |
| `59-yacht-send-email-quote-and-slip.php` | "Send Quote Email to Client" | No aplica directo |
| `1120-forte-code-backup-batch.php` / `1121-...-iframe.php` | Herramienta de backup paginado DEV/PROD usada para producir el export analizado en esta auditoría | Meta (evidencia) |

## Mecanismo confirmado: `GetClauses` / `GetPreliminaryClauses`

En `26-yacht-set-information-slip.php`, ambas llamadas a OpenL (`Forte%20Underwriter%20Yachts/Forte%20Underwriter%20Yachts/GetClauses` y `.../GetPreliminaryClauses`) envían **exactamente** estos 4 parámetros:

```
Country   = YQP_COUNTRY_BUSINESS
Language  = YQP_LANGUAGE
Reinsurer = YQP_REASSURED_CEDENT.LABEL
BaseText  = YQP_BASE_TEXT
```

**No se envía ningún indicador de tipo de negocio (nuevo vs. renovación).** Esto es evidencia directa y relevante para REQ-002: si existe una diferencia de clausulado entre renovación y negocio nuevo, no puede depender de un parámetro que el script ni siquiera envía a OpenL — la diferencia debe originarse en cómo cambia alguno de los 4 parámetros sí enviados (por ejemplo, un `BaseText` distinto en renovaciones) o en variables de validación (`YQP_XXX|YES`) que no se están seteando igual en ambos flujos.

## Mecanismo confirmado: firma dinámica de fuente (`YQP_SOURCE` / `YQP_COMPLETE_NAME_SOURCE`)

Usado correctamente en 4 puntos del código (`26-...` líneas 1483-1489, `40-yacht-generate-slip.php` líneas 140-145, `46-yacht-prepare-documentation.php` líneas 260-265): si `YQP_SOURCE != 'Other'`, se arma `"<FORTE_ORDER>% por/by <YQP_COMPLETE_NAME_SOURCE>"`. Este es el patrón que debería replicarse en la cláusula de cancelación (REQ-007), donde el texto sigue fijo a "Mexbrit".

## Búsquedas negativas (evidencia de ausencia, no de inexistencia)

Se buscó explícitamente en los 154 scripts .php y 406 pantallas .json del backup DEV:
- `Mexbrit`, `MXB`, `ForteUW`, `claims@`: **0 coincidencias reales** (una búsqueda inicial case-insensitive de "mxb" produjo falsos positivos dentro de cadenas base64 de imágenes incrustadas en pantallas; se descartaron).
- `Alejandro`, `Charles Taylor`: **0 coincidencias**.
- `JWA`, `guerra`, `war clause`: sin coincidencias relevantes a Yachts (una única coincidencia en un script de validación de documentos con IA genérico, no específico de la cláusula de guerra).

**Conclusión:** el contenido de la cláusula de guerra completa (REQ-003) y el bloque de Ajustadores (REQ-013/014) no viven en ningún script PHP ni pantalla JSON de este backup — deben vivir en un template base de Word o en una Collection de ProcessMaker no incluida en el export analizado.

## Validación de sintaxis/lógica
No se ejecutó ningún linter PHP ni se corrió el código (fuera del alcance de esta sesión, sin acceso a un entorno ProcessMaker). El análisis es de lectura estática únicamente.
