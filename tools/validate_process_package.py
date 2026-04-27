#!/usr/bin/env python3
"""Validate ProcessMaker process_package JSON integrity (refs, nested screens)."""
from __future__ import annotations

import json
import re
import sys
import xml.etree.ElementTree as ET
from pathlib import Path
from typing import Any


def _ns_bpmn() -> dict[str, str]:
    return {
        "bpmn": "http://www.omg.org/spec/BPMN/20100524/MODEL",
        "pm": "http://processmaker.com/BPMN/2.0/Schema.xsd",
    }


def extract_refs_from_bpmn(bpmn_xml: str) -> tuple[set[int], set[int]]:
    """Return (screen_ids, script_ids) from pm:screenRef / pm:scriptRef attributes."""
    screens: set[int] = set()
    scripts: set[int] = set()
    if not bpmn_xml or not bpmn_xml.strip():
        return screens, scripts
    root = ET.fromstring(bpmn_xml)
    ns = _ns_bpmn()
    for el in root.iter():
        tag = el.tag.split("}")[-1] if "}" in el.tag else el.tag
        if tag not in (
            "task",
            "userTask",
            "manualTask",
            "callActivity",
            "scriptTask",
            "serviceTask",
            "sendTask",
            "receiveTask",
        ):
            continue
        sr = el.attrib.get("{http://processmaker.com/BPMN/2.0/Schema.xsd}screenRef")
        if sr is None:
            sr = el.attrib.get("pm:screenRef")  # unlikely without ns
        scr = el.attrib.get("{http://processmaker.com/BPMN/2.0/Schema.xsd}scriptRef")
        if scr is None:
            scr = el.attrib.get("pm:scriptRef")
        for val, dest in ((sr, screens), (scr, scripts)):
            if val is None or val == "":
                continue
            try:
                dest.add(int(val))
            except ValueError:
                pass
    return screens, scripts


def nested_screen_ids_from_config(config_str: Any) -> set[int]:
    """Recursively find nested screen integer IDs in screen config JSON."""
    ids: set[int] = set()

    def walk(o: Any) -> None:
        if isinstance(o, dict):
            comp = o.get("component")
            if comp == "FormNestedScreen":
                cfg = o.get("config") or {}
                sid = cfg.get("screen")
                if isinstance(sid, int):
                    ids.add(sid)
                elif isinstance(sid, str) and sid.isdigit():
                    ids.add(int(sid))
            for v in o.values():
                walk(v)
        elif isinstance(o, list):
            for v in o:
                walk(v)

    if isinstance(config_str, str) and config_str.strip():
        try:
            walk(json.loads(config_str))
        except json.JSONDecodeError:
            pass
    elif isinstance(config_str, (dict, list)):
        walk(config_str)
    return ids


def validate_package(data: dict[str, Any]) -> dict[str, Any]:
    report: dict[str, Any] = {
        "json_ok": True,
        "type_ok": data.get("type") == "process_package",
        "version_ok": str(data.get("version")) == "1",
        "bpmn_present": bool(data.get("process", {}).get("bpmn")),
        "screen_refs_ok": True,
        "nested_screen_refs_ok": True,
        "script_refs_ok": True,
        "missing_screens_from_bpmn": [],
        "missing_nested_screens": [],
        "missing_scripts_from_bpmn": [],
        "tasks_without_screen_when_expected": [],
    }

    proc = data.get("process") or {}
    bpmn = proc.get("bpmn") or ""
    screens_list = data.get("screens") or []
    scripts_list = data.get("scripts") or []

    screen_ids = {s["id"] for s in screens_list if isinstance(s, dict) and "id" in s}
    script_ids = {s["id"] for s in scripts_list if isinstance(s, dict) and "id" in s}

    bpmn_screens, bpmn_scripts = extract_refs_from_bpmn(bpmn)

    for sid in sorted(bpmn_screens):
        if sid not in screen_ids:
            report["missing_screens_from_bpmn"].append(sid)
            report["screen_refs_ok"] = False

    for rid in sorted(bpmn_scripts):
        if rid not in script_ids:
            report["missing_scripts_from_bpmn"].append(rid)
            report["script_refs_ok"] = False

    nested_needed: set[int] = set()
    for sc in screens_list:
        if not isinstance(sc, dict):
            continue
        nested_needed |= nested_screen_ids_from_config(sc.get("screen_config_data") or sc.get("config"))

    for nid in sorted(nested_needed):
        if nid not in screen_ids:
            report["missing_nested_screens"].append(nid)
            report["nested_screen_refs_ok"] = False

    # Optional: user tasks that typically need screens (heuristic: name contains Review)
    try:
        root = ET.fromstring(bpmn)
        ns = _ns_bpmn()
        for el in root.iter():
            tag = el.tag.split("}")[-1]
            if tag not in ("userTask", "task"):
                continue
            name = el.attrib.get("name") or ""
            sr = el.attrib.get("{http://processmaker.com/BPMN/2.0/Schema.xsd}screenRef")
            if "review" in name.lower() and (sr is None or sr == ""):
                report["tasks_without_screen_when_expected"].append(
                    {"id": el.attrib.get("id"), "name": name}
                )
    except ET.ParseError:
        report["bpmn_parse_error"] = True

    report["all_pass"] = (
        report["type_ok"]
        and report["version_ok"]
        and report["bpmn_present"]
        and report["screen_refs_ok"]
        and report["nested_screen_refs_ok"]
        and report["script_refs_ok"]
        and not report.get("bpmn_parse_error")
    )
    return report


def main() -> None:
    for path in sys.argv[1:]:
        p = Path(path)
        try:
            data = json.loads(p.read_text(encoding="utf-8-sig"))
        except Exception as e:
            print(f"{p.name}: JSON ERROR {e}")
            continue
        r = validate_package(data)
        print(f"\n=== {p.name} ===")
        for k, v in r.items():
            print(f"  {k}: {v}")


if __name__ == "__main__":
    main()
