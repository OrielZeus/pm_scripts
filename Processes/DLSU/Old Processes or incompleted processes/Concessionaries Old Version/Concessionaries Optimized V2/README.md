# Concessionaries Optimized V2

Esta carpeta contiene una base V2 optimizada para `Concessionaries` usando routing dinámico desde colección.

## Estructura

- `Scripts/89-rfp-v2-get-next-approver.php`
  - Reemplaza lógica legacy por consulta SQL en `collection_<id>` usando `encodeSql()` + `apiGuzzle()`.
  - Usa variable de entorno genérica:
    - `RFP_V2_ROUTING_COLLECTION_ID=124` (DEV)
- `Scripts/90-rfp-v2-initialize-approver-value.php`
  - Avanza el aprobador por nivel en cada aprobación.
- `Scripts/91-rfp-v2-push-to-collection.php`
  - Script de push a colección (copiado desde versión old para continuidad).
- `Screens/*.json`
  - Screens heredados de la versión old para facilitar migración.
  - En `Request for Payment Form CONCESSIONARIES.json` se desactivó el `computed` legacy `conditions`.
- `Screens/RFP V2 - Email Notification Display.json`
  - Screen DISPLAY opcional para preview de notificaciones por correo.
- `Process/RFP V2 - CONCESSIONAIRES - OPTIMIZED FLOW.bpmn`
  - Diseño BPMN optimizado (flujo dinámico de aprobación).

## Variables de entorno requeridas

- `API_HOST`
- `API_TOKEN`
- `RFP_V2_ROUTING_COLLECTION_ID` (usar `124` en DEV)

## Mapeo recomendado en BPMN (script tasks)

- `Retrieve Requestor Information` -> script de requestor info existente.
- `Get Next Approver` -> `89-rfp-v2-get-next-approver.php`.
- `Update Approver Information` -> `90-rfp-v2-initialize-approver-value.php`.
- `Push Transaction Details to Collection` -> `91-rfp-v2-push-to-collection.php`.

## Variables de salida clave de routing

- `until_level_number`
- `level_number`
- `approver_id`
- `approver_name`
- `approver_email`
- `L1managerId..LnmanagerId`
- `L1managerEmail..LnmanagerEmail`
- `L1managerName..LnmanagerName`

