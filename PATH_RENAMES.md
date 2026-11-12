# Renombres de rutas y archivos (path cleanup)

Documento de referencia para commit / push / clone en Windows y GitHub.

**Problema:** rutas absolutas sobre o cerca del límite de Windows (~260) y nombres con espacios/acentos que fallan al subir, bajar o hacer checkout.

**Resultado:** la ruta más larga del pack Forte bajó de **~381** a **~178** caracteres absolutos (en esta máquina, base `...\pm_scripts`).

**Regla:** si un archivo no aparece abajo, no se renombró el leaf (solo pudo moverse de carpeta).

---

## 1. Carpetas raíz

| Antes | Ahora |
|---|---|
| `Forte Changes/` | `Forte_Changes/` |
| `Code Review Process Screens/` | `Code_Review_Screens/` |
| `Additional Screen/` | `Additional_Screen/` |
| `Iframe Documents/My Pending Actions/` | `Iframe Documents/My_Pending_Actions/` |
| `Iframe Documents/scripts processmaker/` | `Iframe Documents/scripts_processmaker/` |
| `Iframe Documents/sony genres upload/` | `Iframe Documents/sony_genres_upload/` |
| `Weekly Reports Sept 7-11/` | `Weekly_Reports/2026-09-07_to_11/` |
| `Weekly Reports Sept 14-20/` | `Weekly_Reports/2026-09-14_to_20/` |
| `Weekly Reports Oct 5-9/` | `Weekly_Reports/2026-10-05_to_09/` |
| `Weekly Reports Oct 12-16/` | `Weekly_Reports/2026-10-12_to_16/` |
| `Weekly Reports Oct 19-23/` | `Weekly_Reports/2026-10-19_to_23/` |
| `Weekly Reports Oct 26-30/` | `Weekly_Reports/2026-10-26_to_30/` |
| `Weekly Reports Nov 2-6/` | `Weekly_Reports/2026-11-02_to_06/` |

---

## 2. Forte — carpetas largas (Client / Internal)

| Antes | Ahora |
|---|---|
| `Para compartir con el cliente/` | `Client_Share/` |
| `OpenL actualizado/` | `OpenL_Updated/` |
| `Documentos actualizados/` | `Updated_Docs/` |
| `Templates de clausulas y ajustadores corregidos/` | `Clauses_Templates/` |
| `Trabajo interno/` | `Internal/` |
| `Versiones anteriores/` | `Prev_Versions/` |
| `Templates originales (antes de corregir)/` | `Templates_Original/` |
| `Paquete para producción/` | `PROD_Pkg/` |
| `Paquete para desarrollo/` | `DEV_Pkg/` |
| `Templates corregidos/` | `Templates_Fixed/` |

### Packs de templates (mismo short name en Client_Share e Internal)

| Antes | Ahora |
|---|---|
| `AMERICAN YACHT FORM R12 with Sections/` | `AYF_R12/` |
| `Cláusulas del Instituto para Yates CL. 328, 01-11-85/` | `CL328/` |
| `Cláusulas del Instituto para Yates CL. 328, 01-11-85, con Cláusulas 5.1, 11.6.2, 19.2.1 y 19.3 excluidas/` | `CL328_excl/` |
| `Forma Americana R12 para yates, con las siguientes secciones amparadas y textos modificados/` | `AYF_R12_mod/` |
| `Forma Americana R12 para yates, con las siguientes secciones amparadas y textos modificados. Panama/` | `AYF_R12_Panama/` |
| `Institute Yacht Clauses CL.328, 01-11-85 with clauses 5.1, 11.6.2, 19.2.1 and 19.3 deleted/` | `IYC_CL328_excl/` |
| `Póliza de Seguro de Yates y Embarcaciones de Recreo Estar Seguros (Aprobado por la superintendencia de la Actividad Aseguradora fecha 2 de Marzo 2005/` | `Poliza_Estar_Seguros_VE/` |
| `Según Póliza de Yates del Instituto POL 1 2013 0707/` | `POL1_2013/` |
| `Upload Documents/` | `Upload_Docs/` |

---

## 3. Forte — archivos (antes → ahora)

| Antes | Ahora |
|---|---|
| `Reporte final de implementacion.md` | `Reporte_Final_Implementacion.md` |
| `Slip Puerto Rico MIC actualizado.docx` | `Slip_PR_MIC_Updated.docx` |
| `Reglas de cotizacion FORTE actualizadas.xlsx` | `FORTE_OpenL_Rules_Updated.xlsx` |
| `CLÁUSULA DE BATERÍAS DE LITIO .docx` | `Clausula_Baterias_Litio_ES.docx` |
| `LITHIUM BATTERY CLAUSE.docx` | `Clausula_Baterias_Litio_EN.docx` |
| `CLÁUSULA DE DEPRECIACIÓN (MAYORES A 20 AÑOS).docx` | `Clausula_Depreciacion_Mayores_20_ES.docx` |
| `CLÁUSULA DE DEPRECIACIÓN (MAYORES A 20 AÑOS) INGLÉS.docx` | `Clausula_Depreciacion_Mayores_20_EN.docx` |
| `CLÁUSULA DE DEPRECIACIÓN (MENORES A 20 AÑOS) INGLÉS.docx` | `Clausula_Depreciacion_Menores_20_EN.docx` |
| `correcciones slip (3).docx` | `Correcciones_Slip_Ejemplo.docx` |
| `CLAUSES TO BE INCLUDED (ADICIONALES)_14072026.xlsx` | `Clauses_Adicionales_14072026.xlsx` |
| `Forte_OpenL_Tablets_Yachts_WORKING_REVISION.xlsx` | `Forte_OpenL_Yachts_WORKING.xlsx` |
| `DEV_52-yacht-clear-hidden-variables-and-set-clauses-selected.php` | `DEV_52-yacht-clear-hidden-set-clauses.php` |
| `DEV_8-yacht-get-dropdowns-options-openl.php` | `DEV_08-yacht-get-dropdowns-openl.php` |
| `DEV_59-yacht-send-email-quote-and-slip.php` | `DEV_59-yacht-send-email-quote-slip.php` |
| `DEV_85-yacht-approve-confirmation-slip.json` | `DEV_85-yacht-approve-confirm-slip.json` |
| `01-General-Information-and-Special-Conditions-American-Form-R12.docx` | `01-General_Info_Special_Conditions_R12.docx` |
| `01-General-Information-and-Special-Conditions.docx` | `01-General_Info_Special_Conditions.docx` |
| `01-Informacion-General-y-Condiciones-Especiales.docx` | `01-Info_General_Condiciones.docx` |
| `01-Informacion-General-y-Condiciones-Especiales-C328.docx` | `01-Info_General_Condiciones_C328.docx` |
| `01-Informacion-General-y-Condiciones-Especiales-Ven.docx` | `01-Info_General_Condiciones_VE.docx` |
| `01-Informacion-General-y-Condiciones-Especiales-POL.docx` | `01-Info_General_Condiciones_POL.docx` |
| `24-Clausula-del-instituto-de-exclusion-de-contaminacion-radioactiva.docx` | `24-Exclusion_Contaminacion_Radioactiva.docx` |
| `09-Clausula-del-instituto-de-exclusion-de-contaminacion-radioactiva.docx` | `09-Exclusion_Contaminacion_Radioactiva.docx` |
| `9832__01-General-Information-and-Special-Conditions-American-Form-R12.docx` | `9832__01-General_Info_Special_Conditions_R12.docx` |

---

## 4. Iframes / scripts

| Antes | Ahora |
|---|---|
| `My Pending Actions Iframe.php` | `My_Pending_Actions_Iframe.php` |
| `Source Snapshot IDs v2.php` | `Source_Snapshot_IDs_v2.php` |
| `Collections List Source IDs.php` | `Collections_List_Source_IDs.php` |
| `Collections Sync IDs To Local.php` | `Collections_Sync_IDs_To_Local.php` |
| `Sony Genres Apply Collection.php` | `Sony_Genres_Apply_Collection.php` |
| `Sony Genres Export Backup.php` | `Sony_Genres_Export_Backup.php` |
| `Sony Genres Get Collection Data.php` | `Sony_Genres_Get_Collection_Data.php` |
| `Sony Genres Save Backup To Request.php` | `Sony_Genres_Save_Backup_To_Request.php` |
| `Sony Genres Upload File To Request.php` | `Sony_Genres_Upload_File_To_Request.php` |
| `Sony Genres Upload Main Iframe.php` | `Sony_Genres_Upload_Main_Iframe.php` |
| `Sony Tracks Fix TR_REQUIRE_BASIC_COVER.php` | `Sony_Tracks_Fix_TR_REQUIRE_BASIC_COVER.php` |

> Los **slugs PSTools** en ProcessMaker no cambian solo por renombrar el archivo local.

---

## 5. Weekly reports (patrón)

| Antes (ejemplo) | Ahora |
|---|---|
| `Weekly Reports Oct 12-16/FORTE_Weekly_RequestData_2026-10-12_to_16.json` | `Weekly_Reports/2026-10-12_to_16/FORTE_2026-10-12_to_16.json` |

---

## 6. Contenido claro de este branch

1. Path cleanup + este mapa (`PATH_RENAMES.md`).
2. Scripts yacht DEV actualizados:
   - `26-yacht-set-information-slip.php`
   - `40-yacht-generate-slip.php`
   - `46-yacht-prepare-documentation.php`
   - `59-yacht-send-email-quote-and-slip.php`
3. `Forte_Changes/` reordenado (Client_Share, Internal, reportes MD).
4. Pantallas / iframes / weekly reports con nombres cortos.
5. MDs ajustados a los nombres nuevos.

---

## 7. Verificar tras clonar

```powershell
Get-ChildItem -Recurse -File Forte_Changes,Weekly_Reports,Additional_Screen,Code_Review_Screens |
  ForEach-Object { $_.FullName.Length } | Measure-Object -Maximum
```

Si el máximo supera ~240 en tu máquina, clona más cerca de `C:\` o acorta otra vez `Client_Share/Updated_Docs/Clauses_Templates`.
