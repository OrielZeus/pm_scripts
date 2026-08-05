# -*- coding: utf-8 -*-
"""
Generate 3 ProcessMaker screen-package JSON files for the Code Review Process,
cloning component structure / CSS / layout from the template "test 2 3 eo.json".

Output folder:  Code Review Process Screens/
  01_code_review_request_screen.json
  02_code_review_screen.json
  03_code_review_fix_screen.json

Strategy: load the template, extract one FULL sample of every component type
(so the heavy "inspector" Vue schemas are preserved verbatim), then clone +
override only `config` to build Code Review screens. All variables use the
uppercase CR_ prefix. CSS, header, footer and container selectors are reused.
"""
import json, uuid, copy, os

TEMPLATE = r"C:\Users\egosu\Downloads\test 2 3 eo.json"
OUT_DIR = os.path.join(os.getcwd(), "Code Review Process Screens")

REQUIRED = [{"value": "required",
             "helper": "Checks if the length of the String representation of the value is >",
             "content": "Required"}]


def nu():
    return str(uuid.uuid4())


# ----------------------------------------------------------- load template
pkg = json.load(open(TEMPLATE, encoding="utf-8"))
TPL_SCREEN = pkg["screens"][0]
CUSTOM_CSS = TPL_SCREEN["custom_css"]
CATEGORIES = TPL_SCREEN.get("categories", [])

# collect one full sample per component type
SAMPLES = {}
def collect(node):
    if isinstance(node, list):
        for n in node:
            collect(n)
    elif isinstance(node, dict):
        c = node.get("component")
        if c and c not in SAMPLES:
            SAMPLES[c] = node
        if isinstance(node.get("items"), list):
            for sub in node["items"]:
                collect(sub)
for p in TPL_SCREEN["config"]:
    collect(p.get("items", []))


def clone(component):
    """Deep copy a pristine sample of `component` with a fresh uuid."""
    obj = copy.deepcopy(SAMPLES[component])
    obj["uuid"] = nu()
    # strip any leftover nested items on leaf clones; callers set items explicitly
    return obj


# ------------------------------------------------------------ factories
def _mc(columns, selector=None):
    """columns: list of columns, each column a list of components."""
    n = clone("FormMultiColumn")
    n["items"] = columns
    if selector is not None:
        n["config"]["customCssSelector"] = selector
    elif "customCssSelector" in n["config"]:
        n["config"]["customCssSelector"] = None
    return n


def stack(items, selector=None):
    """Single-column vertical stack -> items = [[*items]]."""
    return _mc([list(items)], selector)


def row(left, right, selector=None):
    """Two-column row -> items = [[left], [right]]."""
    return _mc([[left], [right]], selector)


def html(content):
    n = clone("FormHtmlViewer")
    n["config"]["content"] = content
    n["config"]["renderVarHtml"] = False
    return n


def inp(name, label, readonly=False, required=False, placeholder="", helper=None):
    n = clone("FormInput")
    cfg = n["config"]
    cfg["name"] = name
    cfg["label"] = label
    cfg["readonly"] = readonly
    cfg["placeholder"] = placeholder
    cfg["validation"] = copy.deepcopy(REQUIRED) if required else []
    cfg["helper"] = helper
    return n


def textarea(name, label, readonly=False, required=False, placeholder="", rows=3, helper=None):
    n = clone("FormTextArea")
    cfg = n["config"]
    cfg["name"] = name
    cfg["label"] = label
    cfg["readonly"] = readonly
    cfg["rows"] = rows
    cfg["richtext"] = False
    cfg["placeholder"] = placeholder
    cfg["validation"] = copy.deepcopy(REQUIRED) if required else []
    cfg["helper"] = helper
    return n


def select(name, label, required=False, placeholder="Select...", options=None, helper=None):
    n = clone("FormSelectList")
    cfg = n["config"]
    cfg["name"] = name
    cfg["label"] = label
    cfg["placeholder"] = placeholder
    cfg["readonly"] = False
    cfg["validation"] = copy.deepcopy(REQUIRED) if required else []
    cfg["helper"] = helper
    opts = options or []
    o = cfg["options"]
    o["optionsList"] = opts
    o["jsonData"] = json.dumps(opts)
    o["dataSource"] = "provideData"   # TODO: switch to Collection / Data Connector
    o["pmqlQuery"] = None
    return n


def datepicker(name, label, readonly=False, required=False):
    n = clone("FormDatePicker")
    cfg = n["config"]
    cfg["name"] = name
    cfg["label"] = label
    cfg["disabled"] = readonly
    cfg["dataFormat"] = "date"
    cfg["validation"] = copy.deepcopy(REQUIRED) if required else []
    return n


def button(label, var_name, value, variant, selector):
    n = clone("FormButton")
    cfg = n["config"]
    cfg["label"] = label
    cfg["name"] = var_name        # submitted variable
    cfg["fieldValue"] = value     # value written on submit
    cfg["event"] = "submit"
    cfg["variant"] = variant
    cfg["defaultSubmit"] = True
    cfg["customCssSelector"] = selector
    return n


# section helpers using the template's CSS classes
def step(num, title, desc):
    return [
        html('<p class="Textlitle">Step %s</p>' % num),
        html('<p class="TitleHealth">%s</p>' % title),
        html('<p class="TextField">%s</p>' % desc),
    ]


def section_title(text):
    return html('<p class="TitleSeccion2">%s</p>' % text)


def hr():
    return html('<hr style="border: 2px solid #FFFFFF;" />')


# -------------------------------------------------- header / footer (reused)
def find_by_selector(node, selector):
    if isinstance(node, list):
        for n in node:
            r = find_by_selector(n, selector)
            if r:
                return r
    elif isinstance(node, dict):
        if node.get("config", {}).get("customCssSelector") == selector:
            return node
        if isinstance(node.get("items"), list):
            return find_by_selector(node["items"], selector)
    return None


def header(reviewer_line, screen_line):
    """Clone the template header container, swap the two title HTML viewers."""
    src = find_by_selector(TPL_SCREEN["config"], "Bg-Header2")
    hdr = copy.deepcopy(src)
    refresh_uuids(hdr)
    # collect HtmlViewers in order, swap the two title lines
    hv = []
    collect_component(hdr, "FormHtmlViewer", hv)
    if len(hv) >= 2:
        hv[0]["config"]["content"] = '<div class="d-none d-sm-block">\n<h4 class="hdrTxt3">%s</h4>\n</div>' % reviewer_line
        hv[1]["config"]["content"] = '<div class="d-none d-sm-block">\n<h3 class="hdrTxt2">%s</h3>\n</div>' % screen_line
    return hdr


def footer():
    src = find_by_selector(TPL_SCREEN["config"], "Bg-Header3")
    ft = copy.deepcopy(src) if src else stack([], "Bg-Header3")
    refresh_uuids(ft)
    return ft


def refresh_uuids(node):
    if isinstance(node, dict):
        if "uuid" in node:
            node["uuid"] = nu()
        for v in node.values():
            refresh_uuids(v)
    elif isinstance(node, list):
        for v in node:
            refresh_uuids(v)


def collect_component(node, comp, out):
    if isinstance(node, dict):
        if node.get("component") == comp:
            out.append(node)
        for v in node.values():
            collect_component(v, comp, out)
    elif isinstance(node, list):
        for v in node:
            collect_component(v, comp, out)


def screen_package(title, description, page_name, body_items, computed):
    """Assemble outer container + page + screen + package wrapper."""
    outer = stack([header("Code Review", title), stack(body_items, "Bg-Form2"), footer()],
                  "Bg-containerAT")
    page = {"name": page_name, "items": [outer], "order": 1}
    scr = {
        "id": None, "uuid": nu(), "screen_category_id": "1",
        "title": title, "description": description, "type": "FORM",
        "config": [page], "computed": computed, "custom_css": CUSTOM_CSS,
        "created_at": "2026-06-26T00:00:00+00:00", "updated_at": "2026-06-26T00:00:00+00:00",
        "status": "ACTIVE", "key": None, "watchers": [], "translations": None,
        "is_template": 0, "asset_type": None, "is_default": 0, "projects": "[]",
        "categories": copy.deepcopy(CATEGORIES),
    }
    return {"type": "screen_package", "version": pkg.get("version", 2),
            "screens": [scr], "screen_categories": [], "scripts": []}


# ===================================================================== SCREEN 1
def build_request():
    body = []
    body += step("1", "Code Review Request",
                 "Create a new code review request. Complete the information below and submit it for AI review.")
    body.append(section_title("Request Information"))
    body.append(row(
        inp("CR_REQUEST_ID", "Review ID", readonly=True,
            helper="Auto-generated (see computed section)."),
        inp("CR_REQUEST_DATE", "Request Date", readonly=True,
            helper="Auto-generated current date (see computed section)."),
    ))
    body.append(row(
        select("CR_ACCOUNT_CLIENT", "Account / Client", required=True,
               placeholder="Select account / client",
               helper="TODO: bind to a Collection data source. Leave options empty until configured."),
        select("CR_TAM_USER", "TAM", required=True, placeholder="Select TAM",
               helper="TODO: bind to a Data Connector (group users). Leave options empty until configured."),
    ))
    body.append(section_title("Review Details"))
    body.append(textarea("CR_REVIEW_DESCRIPTION", "Review Description", required=True, rows=4,
                         placeholder="Describe what needs to be reviewed."))
    body.append(section_title("Jira Reference"))
    body.append(inp("CR_JIRA_LINK", "Jira Link", required=True,
                    placeholder="Paste Jira ticket or work package link."))
    body.append(hr())
    body.append(section_title("Submit"))
    body.append(row(
        html("&nbsp;"),
        button("Send to AI Review", "CR_REQUEST_ACTION", "send_to_ai_review", "primary", "btn-position"),
    ))
    computed = [
        {"id": 1, "name": "Generate Review ID", "type": "javascript",
         "formula": "/* TODO: adjust to your Review ID source.\n"
                    " * Defaults to the ProcessMaker request id. */\n"
                    "return (this._request && this._request.id) ? this._request.id : '';",
         "property": "CR_REQUEST_ID"},
        {"id": 2, "name": "Set Request Date", "type": "javascript",
         "formula": "/* Current date at request creation (YYYY-MM-DD). */\n"
                    "return new Date().toISOString().split('T')[0];",
         "property": "CR_REQUEST_DATE"},
    ]
    return screen_package("CRP - Code Review Request",
                          "Requester creates a code review request and submits it for AI review.",
                          "CRP - Code Review Request", body, computed)


# ===================================================================== SCREEN 2
def build_review():
    body = []
    body += step("2", "Code Review",
                 "Review the request and the AI analysis below, add your comments, then Approve or send back for a fix.")
    body.append(section_title("Request Information"))
    body.append(row(
        inp("CR_REQUEST_ID", "Review ID", readonly=True),
        inp("CR_REQUEST_DATE", "Request Date", readonly=True),
    ))
    body.append(row(
        inp("CR_ACCOUNT_CLIENT", "Account / Client", readonly=True),
        inp("CR_TAM_USER", "TAM", readonly=True),
    ))
    body.append(textarea("CR_REVIEW_DESCRIPTION", "Review Description", readonly=True, rows=3))
    body.append(inp("CR_JIRA_LINK", "Jira Link", readonly=True))

    body.append(section_title("AI Review Result"))
    body.append(textarea("CR_AI_REVIEW_SUMMARY", "AI Review Summary", readonly=True, rows=4,
                         helper="Populated by the AI/script task before this review task."))
    body.append(textarea("CR_AI_REVIEW_DETAILS", "AI Review Details", readonly=True, rows=6,
                         helper="Optional. Populated by the AI/script task."))

    body.append(section_title("Reviewer Decision"))
    body.append(inp("CR_REVIEW_DATE", "Review Date", readonly=True,
                    helper="Auto-generated current datetime (see computed section)."))
    body.append(textarea("CR_REVIEWER_COMMENTS", "Reviewer Comments", required=True, rows=4,
                         placeholder="Add review comments, approval notes, or fix instructions.",
                         helper="Required to prevent incomplete reviews. (Conditional 'required on Need Fix' "
                                "must be configured separately if desired.)"))

    body.append(section_title("Defect Details"))
    body.append(row(
        select("CR_DEFECT_SEVERITY", "Defect Severity", required=False, placeholder="Select severity",
               options=[{"value": "Low", "content": "Low"},
                        {"value": "Medium", "content": "Medium"},
                        {"value": "High", "content": "High"}],
               helper="Required when 'Need Fix' is selected (configure conditional validation)."),
        inp("CR_AFFECTED_SCREEN", "Affected Screen", placeholder="Screen, form, or UI section affected.",
            helper="Required when 'Need Fix' is selected."),
    ))
    body.append(row(
        inp("CR_AFFECTED_FILE", "Affected File", placeholder="File, script, or component affected.",
            helper="Required when 'Need Fix' is selected."),
        html("&nbsp;"),
    ))
    body.append(textarea("CR_DEFECT_DESCRIPTION", "Defect Description", required=False, rows=4,
                         placeholder="Explain what must be fixed.",
                         helper="Required when 'Need Fix' is selected."))

    body.append(hr())
    body.append(section_title("Decision"))
    body.append(row(
        button("Need Fix", "CR_REVIEW_DECISION", "need_fix", "danger", "Bg-Reject"),
        button("Approve", "CR_REVIEW_DECISION", "approved", "primary", "Bg-Approve"),
    ))
    computed = [
        {"id": 1, "name": "Set Review Date", "type": "javascript",
         "formula": "/* Current datetime when the reviewer opens/submits the review. */\n"
                    "return new Date().toISOString();",
         "property": "CR_REVIEW_DATE"},
    ]
    return screen_package("CRP - Code Review",
                          "Reviewer evaluates the request and AI output, then Approves or requests a Fix.",
                          "CRP - Code Review", body, computed)


# ===================================================================== SCREEN 3
def build_fix():
    body = []
    body += step("3", "Code Review Fix",
                 "Review the reviewer feedback below, describe the changes you made, then resubmit for review.")
    body.append(section_title("Original Request"))
    body.append(row(
        inp("CR_REQUEST_ID", "Review ID", readonly=True),
        inp("CR_REQUEST_DATE", "Request Date", readonly=True),
    ))
    body.append(row(
        inp("CR_ACCOUNT_CLIENT", "Account / Client", readonly=True),
        inp("CR_TAM_USER", "TAM", readonly=True),
    ))
    body.append(textarea("CR_REVIEW_DESCRIPTION", "Review Description", readonly=True, rows=3))
    body.append(inp("CR_JIRA_LINK", "Jira Link", readonly=True))

    body.append(section_title("Latest Review Feedback"))
    body.append(row(
        inp("CR_REVIEW_DATE", "Review Date", readonly=True),
        inp("CR_DEFECT_SEVERITY", "Defect Severity", readonly=True),
    ))
    body.append(textarea("CR_REVIEWER_COMMENTS", "Reviewer Comments", readonly=True, rows=4))
    body.append(row(
        inp("CR_AFFECTED_SCREEN", "Affected Screen", readonly=True),
        inp("CR_AFFECTED_FILE", "Affected File", readonly=True),
    ))
    body.append(textarea("CR_DEFECT_DESCRIPTION", "Defect Description", readonly=True, rows=4))
    body.append(textarea("CR_REVIEW_HISTORY", "Review History", readonly=True, rows=5,
                         helper="Optional. Shows previous review/fix attempts if populated by the BPMN/script."))

    body.append(section_title("Fix Response"))
    body.append(inp("CR_FIX_DATE", "Fix Date", readonly=True,
                    helper="Auto-generated current datetime (see computed section)."))
    body.append(textarea("CR_FIX_DESCRIPTION", "Fix Description", required=True, rows=4,
                         placeholder="Describe the changes made to address the reviewer feedback."))
    body.append(textarea("CR_FIX_COMMENTS", "Comments for Reviewer", required=True, rows=4,
                         placeholder="Explain what was corrected and ask the reviewer to validate again."))
    body.append(inp("CR_FIX_LINK", "Fix Link / Evidence", required=False,
                    placeholder="Add Jira, evidence, commit, or reference link if available."))

    body.append(hr())
    body.append(section_title("Resubmit"))
    body.append(row(
        html("&nbsp;"),
        button("Resubmit to Review", "CR_FIX_ACTION", "resubmit_to_review", "primary", "btn-position"),
    ))
    computed = [
        {"id": 1, "name": "Set Fix Date", "type": "javascript",
         "formula": "/* Current datetime when the fix screen is opened/submitted. */\n"
                    "return new Date().toISOString();",
         "property": "CR_FIX_DATE"},
    ]
    return screen_package("CRP - Code Fix",
                          "Assigned user reviews feedback, documents the fix, and resubmits to the reviewer.",
                          "CRP - Code Fix", body, computed)


# ===================================================================== write
def variables_in(obj, found):
    if isinstance(obj, dict):
        if obj.get("component") in ("FormInput", "FormTextArea", "FormSelectList",
                                     "FormDatePicker", "FormButton"):
            nm = obj.get("config", {}).get("name")
            if nm:
                found.add(nm)
        for v in obj.values():
            variables_in(v, found)
    elif isinstance(obj, list):
        for v in obj:
            variables_in(v, found)


def write(name, data):
    os.makedirs(OUT_DIR, exist_ok=True)
    path = os.path.join(OUT_DIR, name)
    with open(path, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=1)
    # validations
    d = json.load(open(path, encoding="utf-8"))
    assert d["type"] == "screen_package"
    scr = d["screens"][0]
    assert scr["custom_css"] == CUSTOM_CSS, "custom CSS not preserved"
    vs = set()
    variables_in(scr["config"], vs)
    bad = [v for v in vs if v.lower().startswith("form_")]
    assert not bad, "generic form_ variables present: %s" % bad
    assert all(v.startswith("CR_") for v in vs), "non CR_ variable: %s" % vs
    print("wrote", name, "(%d bytes) | vars:" % os.path.getsize(path), sorted(vs))


write("01_code_review_request_screen.json", build_request())
write("02_code_review_screen.json", build_review())
write("03_code_review_fix_screen.json", build_fix())

# cross-screen button value check
import re
allvals = set()
for fn in os.listdir(OUT_DIR):
    txt = open(os.path.join(OUT_DIR, fn), encoding="utf-8").read()
    for v in ["send_to_ai_review", "approved", "need_fix", "resubmit_to_review"]:
        if '"%s"' % v in txt:
            allvals.add(v)
print("button values present across files:", sorted(allvals))
print("done -> ", OUT_DIR)
