# ProcessMaker Scripts — DLSU (Dev / prod alignment)

## Purpose of this repository area

**ProcessMakerScripts** holds reusable assets for ProcessMaker Cloud or on‑prem: PHP **PSTools** scripts (API executors), **iframe** screen fragments that return `PSTOOLS_RESPONSE_HTML`, SQL-backed dashboards, and helpers that follow the same patterns (Guzzle, Pro Service Tools SQL with base64 payloads, Bootstrap + DataTables in iframes). The goal is to keep integration logic versioned, repeatable, and easy to paste into PM script tasks or screen controls.

**DLSU** (this folder) is one productized solution inside that workspace: it **aligns development** with **production** for **users**, **group membership**, and optionally **collections**, using:

- **Production** — read-only access via `PM_PROD_API_HOST` + `PM_PROD_API_TOKEN` and Pro Service Tools SQL.
- **Development** — writes (create users, update groups, optional collection rows) via the script executor’s `API_HOST` + `API_TOKEN` and the ProcessMaker PHP SDK (`$api`).

Nothing in DLSU relies on static JSON files served as runtime configuration. **Operational URLs are always ProcessMaker PSTools endpoints**:

`{API_HOST}/pstools/script/{slug}`

- Most scripts return a **JSON** object in the response body.
- The **Screen iframe** script returns **`PSTOOLS_RESPONSE_HTML`** (HTML for an Iframe control).

Register each PHP file in ProcessMaker as a **Script** with a **slug** (examples below are defaults; override with `pstools_slug_*` in request/screen `$data` if needed).

## Production environment (host and token)

Production is **not** the same as the dev `API_HOST` used to run PSTools. You must define **where production lives** and **which token** can read it (Gazelle / service account).

Configure using **any** of the following (first non-empty wins in each category):

| Purpose | Request `$data` keys | `$data['_env']` | Environment variables |
|--------|-------------------------|-----------------|------------------------|
| Production API base (must include `/api/1.0`) | `api_host_prod`, `production_api_host`, `production_host`, `PM_PROD_API_HOST` | same keys, plus `DLSU_PRODUCTION_API_HOST` | `DLSU_PRODUCTION_API_HOST`, `PM_PROD_API_HOST`, `DLSU_PROD_API_HOST` |
| Production bearer token | `api_token_prod`, `production_api_token`, `production_token`, `pm_prod_api_token`, `PM_PROD_API_TOKEN` | same, plus `DLSU_PRODUCTION_API_TOKEN` | `DLSU_PRODUCTION_API_TOKEN`, `PM_PROD_API_TOKEN`, `DLSU_PROD_API_TOKEN` |

The **iframe** control panel also has fields for production URL and token; when filled, they set `api_host_prod` / `api_token_prod` on the POST body (overriding env for that call).

## DLSU scripts and default slugs

| Slug (example) | File | Response |
|----------------|------|----------|
| `dlsu-config-status` | `DLSU_PSTools_Config_Status.php` | JSON — environment check only |
| `dlsu-user-group-collection-sync` | `DLSU_User_Group_Collection_Sync.php` | JSON — full sync/report |
| `dlsu-request-files` | `DLSU_PSTools_Request_Files.php` | JSON — lists request attachments |
| `dlsu-iframe-control-panel` | `DLSU_Iframe_Control_Panel.php` | HTML — control panel UI |

Full URL example: `https://your-dev.example.com/api/1.0/pstools/script/dlsu-config-status` (POST with JSON body as your PM version expects).

## Iframe control panel

`DLSU_Iframe_Control_Panel.php` renders the **Endpoints & files** tab with the **live** `{API_HOST}/pstools/script/...` URLs for the current environment. It does **not** point to repository paths or separate “public document” URLs for execution.

## Security

Keep bearer tokens in **environment variables** or PM secrets. The iframe masks tokens in configuration echoes; avoid placing long‑lived tokens in client-visible screen data unless your security model requires it.

## Related examples elsewhere in the repo

Under **Iframe Documents**, see CS Dashboard, Sony Genres, Upload files, and Forte Active Task for the same iframe (`<|` tag escaping, `PSTOOLS_RESPONSE_HTML`) and PSTools POST patterns.
