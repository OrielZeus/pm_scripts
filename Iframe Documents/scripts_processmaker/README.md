# Scripts ProcessMaker - Full Sync

Carpeta antes: `scripts processmaker`. Ver `../../PATH_RENAMES.md`.

## Archivos en esta carpeta

- `Source_Snapshot_IDs_v2.php` (antes `Source Snapshot IDs v2.php`)

Los scripts de sync de collections viven en `../sony_genres_upload/`:

- `Collections_List_Source_IDs.php` (antes `Collections List Source IDs.php`): snapshot de origen (`users`, `groups`, `group_members`, `environment_variables`, `collections`).
- `Collections_Sync_IDs_To_Local.php` (antes `Collections Sync IDs To Local.php`): sincroniza esas entidades hacia local por API.

## Flujo recomendado (incremental)

1. Ejecuta `Collections_List_Source_IDs.php` en origen para validar volumen por entidad.
2. Ejecuta `Collections_Sync_IDs_To_Local.php` primero con `dry_run=true`.
3. Ejecuta de nuevo con `dry_run=false`.
4. Corre por entidades o por lotes si lo necesitas.

### Ejemplo por entidades

- Paso 1: `["users","groups"]`
- Paso 2: `["group_members"]`
- Paso 3: `["environment_variables","collections"]`

La idea es clonar poco a poco y revisar reportes de `mapped_by_*` y `error_*`.

## Input de ejemplo para Sync

```json
{
  "source_api_host": "https://origen.example/api/1.0",
  "source_api_token": "TOKEN_ORIGEN",
  "local_api_host": "https://local.example/api/1.0",
  "local_api_token": "TOKEN_LOCAL",
  "entities": ["users", "groups", "group_members", "environment_variables", "collections"],
  "dry_run": true,
  "per_page": 100
}
```

## Nota sobre IDs

- El script intenta crear con el mismo `id`.
- Si la API no permite forzar `id`, aplica fallback por clave natural:
  - users: `username`/`email`
  - groups: `name`
  - environment_variables: `name`
  - collections: `name`

## Nota sobre seeds/BD limpia

Para correr seeds necesitas que el contenedor de ProcessMaker esté operativo (artisan funcionando).
Si `artisan` falla por versión de PHP en la imagen, primero hay que usar una imagen compatible con el core/tag.

En `pm_local_docker_latest` se agregó `seed-clean-db.ps1` para ejecutar:

1. `up -d`
2. `php artisan migrate:fresh --seed`
3. `php artisan optimize:clear`
4. `php artisan package:discover`
