"""Print nested screen IDs referenced in a ProcessMaker screen JSON file."""
import json
import sys
from pathlib import Path


def nested_ids(config_str: str) -> set[int]:
    ids: set[int] = set()

    def walk(o):
        if isinstance(o, dict):
            if o.get("component") == "FormNestedScreen":
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

    if config_str:
        walk(json.loads(config_str))
    return ids


def main():
    for path in sys.argv[1:]:
        p = Path(path)
        d = json.loads(p.read_text(encoding="utf-8"))
        cfg = d.get("config") or ""
        print(p.name, "id", d.get("id"), "nested:", sorted(nested_ids(cfg)))


if __name__ == "__main__":
    main()
