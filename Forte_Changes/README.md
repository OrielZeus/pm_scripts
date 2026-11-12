# Forte_Changes

Paquete de trabajo FORTE Yachts (análisis + evidencias + copias de trabajo + material cliente).

Antes se llamaba **`Forte Changes`**. Mapa completo antes → ahora: [`../PATH_RENAMES.md`](../PATH_RENAMES.md).

## Estructura

| Carpeta | Contenido |
|---|---|
| `00_Source_Inventory/` | Inventario y hashes |
| `01_Requirement_Evidence/` | Correos, comentarios cliente, cláusulas fuente |
| `02_Analysis/` | Notas de OpenL, scripts, templates, dependencias |
| `03_Change_Tracker/` | Tracker maestro Excel |
| `04_Working_Copies/` | Scripts/templates/OpenL de trabajo (`DEV_*`) |
| `05_DEV_Package/` | Instrucciones de despliegue DEV |
| `06_PROD_Package/` | Instrucciones de despliegue PROD |
| `07_Test_Evidence/` | Evidencia de pruebas |
| `08_Rollback/` | Rollback |
| `09_Final_Report/` | Resumen ejecutivo, análisis, pendientes, runbook |
| `Client_Share/` | Material para el cliente (antes `Para compartir con el cliente`) |
| `Internal/` | Trabajo interno: `DEV_Pkg`, `PROD_Pkg`, `Prev_Versions` |

## Scripts yacht tocados en backup DEV

También en `Iframe Documents/Forte Code Backups/Forte_Dev_Files/scripts/`:

- `26-yacht-set-information-slip.php`
- `40-yacht-generate-slip.php`
- `46-yacht-prepare-documentation.php`
- `59-yacht-send-email-quote-and-slip.php`
