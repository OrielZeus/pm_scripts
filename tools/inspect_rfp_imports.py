import json
import re
import sys
from pathlib import Path

base = Path(
    r"c:/Users/Oriel/Processmaker/ProcessMakerScripts/Iframe Documents/DLSU/RFP-V2-dlsu-import-2026-04"
)
if len(sys.argv) > 1:
    for path in sys.argv[1:]:
        p = Path(path)
        d = json.load(open(p, encoding="utf-8"))
        b = d["process"]["bpmn"]
        refs = sorted({int(x) for x in re.findall(r'pm:screenRef="(\d+)"', b)})
        srefs = sorted({int(x) for x in re.findall(r'pm:scriptRef="(\d+)"', b)})
        print(p.name)
        print("  screenRefs in bpmn:", refs)
        print("  scriptRefs:", srefs)
        print("  screens in pkg:", sorted(s["id"] for s in d["screens"]))
    sys.exit(0)

for p in sorted(base.glob("*.json")):
    d = json.load(open(p, encoding="utf-8"))
    b = d["process"]["bpmn"]
    refs = sorted({int(x) for x in re.findall(r'pm:screenRef="(\d+)"', b)})
    srefs = sorted({int(x) for x in re.findall(r'pm:scriptRef="(\d+)"', b)})
    print(p.name)
    print("  screens in pkg:", [s["id"] for s in d["screens"]])
    print("  screenRefs in bpmn:", refs)
    print("  scriptRefs:", srefs)
    print("  scripts in pkg:", [s["id"] for s in d["scripts"]])
