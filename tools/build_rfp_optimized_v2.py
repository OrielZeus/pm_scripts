#!/usr/bin/env python3
"""
Build a complete RFP V2 process_package JSON from:
- RFP-V2-dlsu-import (BPMN + scripts with code)
- forte_dev_files/screens/*.json (full screens from PROD backup)

Patches Review task (node_12) to set pm:screenRef to the approver screen (323).
"""
from __future__ import annotations

import json
import re
import uuid
from pathlib import Path
from typing import Any

ROOT = Path(__file__).resolve().parents[1]
BACKUP_SCREENS = (
    ROOT
    / "Iframe Documents"
    / "DLSU"
    / "DLSU Back up PROD"
    / "forte_dev_files"
    / "screens"
)
IMPORT_DIR = ROOT / "Iframe Documents" / "DLSU" / "RFP-V2-dlsu-import-2026-04"
OUT_DIR = ROOT / "Processes" / "DLSU" / "RFP V2 Optimized Exports"

VARIANTS: dict[str, dict[str, Any]] = {
    "concessionaires": {
        "import_file": "rfp-v2-dlsu-import--concessionaires.json",
        "out_name": "RFP V2 - CONCESSIONAIRES - OPTIMIZED V2.json",
        "main_screen_id": 326,
    },
    "donations": {
        "import_file": "rfp-v2-dlsu-import--donations.json",
        "out_name": "RFP V2 - DONATIONS - OPTIMIZED V2.json",
        "main_screen_id": 328,
    },
    "honorarium": {
        "import_file": "rfp-v2-dlsu-import--honorarium.json",
        "out_name": "RFP V2 - HONORARIUM - OPTIMIZED V2.json",
        "main_screen_id": 321,
    },
}

REVIEW_SCREEN_ID = 323
NESTED_READONLY_ID = 324
REQUESTOR_ID = 320
PAYEE_DROPDOWN_ID = 357


def load_screen(screen_id: int) -> dict[str, Any]:
    """Load screen JSON from backup folder by numeric id prefix."""
    matches = list(BACKUP_SCREENS.glob(f"{screen_id}-*.json"))
    if not matches:
        raise FileNotFoundError(f"No backup screen file for id {screen_id} in {BACKUP_SCREENS}")
    if len(matches) > 1:
        matches.sort(key=lambda p: len(p.name))
    data = json.loads(matches[0].read_text(encoding="utf-8"))
    if data.get("id") != screen_id:
        raise ValueError(f"Screen file {matches[0]} id mismatch")
    return data


def patch_review_screen_ref(bpmn: str, screen_id: int) -> str:
    """Ensure node_12 (Review RFP Requests) has pm:screenRef."""
    pattern = (
        r'(<bpmn:task id="node_12" name="Review RFP Requests")(\s[^>]*)(>)'
    )

    def repl(m: re.Match[str]) -> str:
        start, attrs, end = m.group(1), m.group(2), m.group(3)
        if "pm:screenRef=" in attrs:
            attrs = re.sub(r'\spm:screenRef="[^"]*"', f' pm:screenRef="{screen_id}"', attrs)
        else:
            attrs = f'{attrs} pm:screenRef="{screen_id}"'
        return start + attrs + end

    new_bpmn, n = re.subn(pattern, repl, bpmn, count=1)
    if n != 1:
        raise RuntimeError("Could not patch node_12 Review task in BPMN")
    return new_bpmn


def screens_for_variant(main_id: int, include_payee: bool) -> list[dict[str, Any]]:
    ids = [REQUESTOR_ID, main_id, REVIEW_SCREEN_ID, NESTED_READONLY_ID]
    if include_payee:
        ids.append(PAYEE_DROPDOWN_ID)
    seen: set[int] = set()
    out: list[dict[str, Any]] = []
    for sid in ids:
        if sid in seen:
            continue
        seen.add(sid)
        out.append(load_screen(sid))
    return out


def build_package(variant_key: str) -> dict[str, Any]:
    spec = VARIANTS[variant_key]
    import_path = IMPORT_DIR / spec["import_file"]
    pkg = json.loads(import_path.read_text(encoding="utf-8"))

    main_id = spec["main_screen_id"]
    include_payee = variant_key == "honorarium"
    pkg["screens"] = screens_for_variant(main_id, include_payee)
    pkg["process"]["bpmn"] = patch_review_screen_ref(pkg["process"]["bpmn"], REVIEW_SCREEN_ID)

    pkg["process"]["name"] = spec["out_name"].replace(".json", "")
    # Keep a clear description
    pkg["process"]["description"] = (
        f"{pkg['process'].get('description') or ''} "
        f"Optimized V2: full screens from PROD backup (incl. review {REVIEW_SCREEN_ID}), "
        f"Review task screenRef patched, nested screens validated."
    ).strip()

    # Fresh export-friendly UUID on process (optional — helps avoid collisions on import)
    pkg["process"]["uuid"] = str(uuid.uuid4())

    return pkg


def write_utf8_no_bom(path: Path, data: dict[str, Any]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    text = json.dumps(data, ensure_ascii=False, separators=(",", ":"))
    path.write_text(text, encoding="utf-8", newline="\n")


def main() -> None:
    for key in VARIANTS:
        print("Building", key, "...")
        pkg = build_package(key)
        out = OUT_DIR / VARIANTS[key]["out_name"]
        write_utf8_no_bom(out, pkg)
        print("  Wrote", out, "screens", len(pkg["screens"]), "scripts", len(pkg["scripts"]))


if __name__ == "__main__":
    main()
