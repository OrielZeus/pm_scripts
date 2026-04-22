"""
Build ProcessMaker process_package JSON (version 1) for DLSU RFP V2 imports.

Sources:
  - Example design and processes/RFP V2 - CONCESSIONAIRES.json
  - Example design and processes/RFP V2 - HONORARIUM.json
  - DLSU Back up PROD/forte_dev_files/screens/328-rfp-v2-donations-request-form.json

Outputs three JSON files in this directory. Run: python build_dlsu_import_packages.py
"""

from __future__ import annotations

import copy
import json
import re
import uuid
from pathlib import Path


ROOT = Path(__file__).resolve().parent
REPO = ROOT.parents[2]
EXAMPLES = ROOT.parent / "Example design and processes"
BACKUP_SCREEN = (
    ROOT.parent
    / "DLSU Back up PROD"
    / "forte_dev_files"
    / "screens"
    / "328-rfp-v2-donations-request-form.json"
)


def new_uuid() -> str:
    return str(uuid.uuid4())


def strip_conditions_routing_computed(pkg: dict) -> int:
    """Remove screen computed used for RUL/collection routing (legacy client-side)."""
    removed = 0
    for screen in pkg.get("screens") or []:
        comp = screen.get("computed")
        if not comp:
            continue
        kept = []
        for c in comp:
            name = (c.get("name") or "").strip().lower()
            prop = (c.get("property") or "").strip().lower()
            if name == "conditions for routing" or prop == "conditions":
                removed += 1
                continue
            kept.append(c)
        screen["computed"] = kept
    return removed


def assign_fresh_uuids(pkg: dict) -> None:
    """New UUIDs for process and embedded assets; keep numeric ids so BPMN screenRef/scriptRef stay valid."""
    proc = pkg.get("process") or {}
    proc["uuid"] = new_uuid()
    if "id" in proc:
        proc["id"] = None
    proc["status"] = "INACTIVE"
    pkg["process"] = proc

    for screen in pkg.get("screens") or []:
        screen["uuid"] = new_uuid()

    for script in pkg.get("scripts") or []:
        script["uuid"] = new_uuid()


def replace_bpmn_screen_ref(bpmn: str, old_id: int, new_id: int) -> str:
    return re.sub(
        rf'pm:screenRef="{old_id}"',
        f'pm:screenRef="{new_id}"',
        bpmn,
    )


def load_json(path: Path) -> dict:
    with path.open(encoding="utf-8") as f:
        return json.load(f)


def write_json(path: Path, data: dict) -> None:
    with path.open("w", encoding="utf-8", newline="\n") as f:
        json.dump(data, f, ensure_ascii=False, separators=(",", ":"))


def build_concessionaires() -> dict:
    src = load_json(EXAMPLES / "RFP V2 - CONCESSIONAIRES.json")
    pkg = copy.deepcopy(src)
    proc = pkg["process"]
    proc["name"] = "RFP V2 — CONCESSIONAIRES (DLSU import · 2026-04)"
    proc["description"] = (
        "Request for Payment CONCESSIONAIRES — re-packaged for dev import. "
        "Isolate from legacy prod IDs; activate after scripts/SQL connectors are wired."
    )
    assign_fresh_uuids(pkg)
    return pkg


def build_donations() -> dict:
    base = load_json(EXAMPLES / "RFP V2 - CONCESSIONAIRES.json")
    donation_screen = load_json(BACKUP_SCREEN)
    pkg = copy.deepcopy(base)

    old_form_id = 326
    new_form_id = int(donation_screen["id"])

    pkg["screens"][1] = copy.deepcopy(donation_screen)

    proc = pkg["process"]
    proc["name"] = "RFP V2 — DONATIONS (DLSU import · 2026-04)"
    proc["description"] = (
        "Request for Payment DONATIONS — form screen taken from PROD backup "
        "328-rfp-v2-donations-request-form; flow cloned from CONCESSIONAIRES package."
    )
    proc["bpmn"] = replace_bpmn_screen_ref(proc["bpmn"], old_form_id, new_form_id)

    assign_fresh_uuids(pkg)
    return pkg


def build_honorarium() -> dict:
    src = load_json(EXAMPLES / "RFP V2 - HONORARIUM.json")
    pkg = copy.deepcopy(src)
    proc = pkg["process"]
    proc["name"] = "RFP V2 — HONORARIUM (DLSU import · 2026-04)"
    proc["description"] = (
        "Request for Payment HONORARIUM — client-side computed `conditions` (RUL routing) "
        "removed from payee screen; target environment should set routing via SQL/script."
    )
    removed = strip_conditions_routing_computed(pkg)
    if removed == 0:
        raise SystemExit(
            "Expected to remove at least one 'conditions' computed from Honorarium package."
        )
    assign_fresh_uuids(pkg)
    return pkg


def main() -> None:
    if not EXAMPLES.is_dir():
        raise SystemExit(f"Missing examples folder: {EXAMPLES}")
    if not BACKUP_SCREEN.is_file():
        raise SystemExit(f"Missing donations screen backup: {BACKUP_SCREEN}")

    write_json(ROOT / "rfp-v2-dlsu-import--concessionaires.json", build_concessionaires())
    write_json(ROOT / "rfp-v2-dlsu-import--donations.json", build_donations())
    write_json(ROOT / "rfp-v2-dlsu-import--honorarium.json", build_honorarium())
    print("Wrote 3 packages to", ROOT)


if __name__ == "__main__":
    main()
