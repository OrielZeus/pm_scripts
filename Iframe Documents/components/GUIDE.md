# Vue iframe components (Iframe Documents / `components`)

This folder holds **editable** templates for ProcessMaker screens that load a **pstools PHP script** inside `iframe-psTools`, following the same conventions as **`Iframe Documents/northleaf_dev`** (reference implementation). Do **not** copy production scripts from `northleaf_dev` into client deliverables unless intended; use this folder as the canonical **pattern** for new work.

## Canonical northleaf reference (do not modify the original)

| Artifact | Location |
|----------|----------|
| POC script (Vue grid in iframe, hidden `iframe-*` fields) | `Iframe Documents/northleaf_dev/scripts/740-poc-is-03-custom-grid-iframe-faster.php` |
| Meta (title / id) | `Iframe Documents/northleaf_dev/scripts/meta/740-poc-is-03-custom-grid-iframe-faster.json` |

That script illustrates **POC – IS.03 Custom Grid iFrame Faster**: Bootstrap + Vue (CDN), sticky table behavior, and **publish-back** fields the parent screen reads via jQuery.

## End-to-end flow

1. **Screen computed property** `IframeGridHtml` builds HTML that embeds:
   - `iframe id="iframe-psTools"`
   - `src="/api/1.0/pstools/script/{scriptSlug}?data=" + encodeURIComponent(JSON.stringify(...))`
2. **ProcessMaker** runs the PHP script behind that URL. The script must return **`PSTOOLS_RESPONSE_HTML`** (see below).
3. **Inside the iframe**, Vue renders **hidden** `<input>` elements whose **`id` values start with `iframe-`** (e.g. `iframe-IN_TOTAL_HST`). These mirror the IDs the parent screen’s **computed properties** and **calcs** query.
4. **Parent screen** reads values with `$('#iframe-psTools').contents().find('#iframe-...')` (and/or `contentDocument`), often wrapped in try/catch for cross-origin edge cases.

## PHP script contract (pstools iframe)

Northleaf uses this return shape so the gateway injects HTML into the iframe response:

```php
return [
    'PSTOOLS_RESPONSE_HTML' => str_replace(['<|', '</|'], ['<', '</'], $html)
];
```

- **`$html`** is a full HTML document string.
- **`<|` / `</|` placeholders**: Vue/HTML tags are written as `<|input`, `<|div`, etc. inside the PHP source so editors and parsers do not treat `<input` as PHP/HTML prematurely. They are stripped back to real tags before return.

Your minimal example script is: **`example-screen/example-vue-iframe-screen.php`**.

## Hidden field naming (publish-back to the screen)

Match **IDs** exactly between:

- Vue: `id="iframe-EXAMPLE_ROWS_JSON"` (and any numeric totals you map to calcs).
- Screen computed / calc: `document.getElementById('iframe-psTools')` … `find('#iframe-EXAMPLE_ROWS_JSON')` or jQuery `$('#iframe-psTools').contents().find(...)`.

Northleaf POC uses the same idea for totals, e.g. `iframe-IN_TOTAL_HST`, `iframe-IN_OUTSTANDING_TOTAL` (see grep in `740-...php` around the hidden inputs).

## Files in this folder

| Path | Purpose |
|------|---------|
| `example-screen/example-vue-iframe-screen.php` | Minimal PHP + Vue iframe; publish-back hidden fields |
| `example-screen/computed/*.js` | Copy/paste formulas for Screen **Computed Properties** |
| `calc/*.js` | Copy/paste bodies for **Calculated properties** / script-task style logic |
| `example-screen/screen-model/components-vue-iframe-example-minimal.json` | Exported screen definition (structure preserved) |
| `example-fields-direct/example-vue-iframe-fields-direct.php` | Campos directos (nombre / email), IDs `iframe-USER_NAME`, `iframe-USER_EMAIL` |
| `example-fields-direct/computed/*.js` | IframeGrid + JSON + lectura nombre/correo desde el iframe |
| `example-fields-direct/calc/*.js` | Calcs que leen los mismos IDs vía jQuery |
| `example-fields-direct/screen-model/components-vue-iframe-fields-direct.json` | Modelo de pantalla (misma estructura base que el minimal) |

## Slug discipline

The **script slug** in ProcessMaker (URL segment) must equal `scriptSlug` in `IframeGridHtml` (e.g. `'example-vue-iframe-screen'`). Publish the PHP script under that slug and keep calcs/computed references aligned.

## Why calcs / computed often show `0` or empty (iframe “no captura”)

Two separate issues usually explain this:

### 1. ProcessMaker does not watch the iframe DOM

Calculated properties and computed properties run when ProcessMaker’s **screen model** thinks something relevant changed (screen variables, navigation, submit, etc.).  
When Vue **inside** `iframe-psTools` updates hidden `<input>` values, the **parent** screen does **not** automatically re-evaluate calcs — there is no reactive link from iframe JS to PM’s evaluator.

So you may see `0` until the user changes another field, blurs an input, or something else forces a refresh. Northleaf mitigates this by (among other things) having the iframe script **trigger a button click** on the parent form when validation runs (see `querySelector` + `click()` toward the end of `740-poc-is-03-custom-grid-iframe-faster.php` — the selector is environment-specific).

### 2. Race: calcs run before the iframe loads

On first paint, `iframe-psTools` may still be empty or Vue may not have mounted yet. The first evaluation of `$('#iframe-psTools').contents().find(...)` then returns nothing → `0`.  
If PM does not run the calc again after load, the value stays wrong until another screen event fires.

### 3. Syntax / access errors

- Prefer avoid **`??`** in calc/computed bodies if your PM build uses an older JS engine; use explicit `== null` checks and `try/catch` around `.contents()` (same-origin only).
- `.contents()` only works when the iframe document is **same-origin** with the parent (typical for `/api/1.0/pstools/script/...` on the same host).

### What to do in practice

- Test after **changing any normal screen variable** or after **Save** / task submit to confirm values propagate once PM re-runs formulas.
- Align with Northleaf: after data is ready in the iframe, **dispatch whatever your project uses** (hidden button click, PM-specific control, etc.) so the parent form recalculates.
- Optionally duplicate critical iframe outputs into **computed properties** that run on the same triggers as your other fields (still subject to refresh timing).

The calc snippets under `calc/` use `try/catch` and conservative checks where applicable.

## Finding `#iframe-psTools` from computed properties (`__pmResolveIframePsTools`)

Some PM deployments render the task screen inside a nested frame or evaluate formulas in a context where `document.getElementById('iframe-psTools')` does not see the iframe (it lives under `parent`/`top`). The updated computed snippets in `example-screen/computed/example-rows-*.js` resolve the iframe from **`document`**, **`window.parent.document`**, and **`window.top.document`** (each wrapped in try/catch).

They also start with **`void this.IframeGridHtml`** so ProcessMaker ties these computed props to **`IframeGridHtml`** when that string changes (e.g. `ExampleTitle` / payload changes). They still do **not** re-run when only the Vue app *inside* the iframe updates hidden inputs—see “Why calcs…” above for that limitation.
