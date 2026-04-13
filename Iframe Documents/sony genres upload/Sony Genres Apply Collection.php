<?php
/**
 * Sony Genres — apply changes to collection_23 (template)
 *
 * Expects JSON with user decision and plan from the client (or server-validated).
 * Implement according to your policy:
 *
 * - replace: DELETE truncate {API_HOST}/collections/23/truncate then import or bulk POST records.
 * - adjust: PUT/PATCH only divergent records (use record_id).
 * - mix: POST new + PATCH existing; do not delete rows missing from Excel unless you define that rule.
 *
 * Example body:
 * {
 *   "request_id": 123,
 *   "strategy": "adjust|replace|mix",
 *   "rows": [ { "record_id": 1, "data": { "name":"", "value":"", "row_id":"", "content":"" } } ]
 * }
 *
 * For security, validate permissions and re-read the collection before applying.
 */

return [
    'success' => false,
    'message' => 'Implement this PSTools with collections API calls (see api documentation.json).',
    'hint' => 'Use the iframe comparison result and the backup stored on the request.',
];
