# -*- coding: utf-8 -*-
"""
Generates new DLSU RFP Concessionaires screens (ProcessMaker single-screen JSON):
  - 3 EMAIL screens (Pending Approval, Request for More Information, Rejected/Inform)
  - 1 DISPLAY summary screen (visible fields + concessionaires line-items table)

Email layout cloned from "166-approved-request-email.json" (DLSU branded template).
Display layout cloned from FormHtmlViewer DISPLAY screens.

NOTE: the HTML contains literal '%' (CSS) and mustache '{{ }}', so we build it with
plain concatenation + str.replace tokens only -- no %-format / .format / f-strings.
"""
import json, uuid, os

OUT_DIR = os.path.join("Iframe Documents", "DLSU", "CSS Updates Screens")

GREEN = "#006937"
LOGO = "https://dlsu.cloud.processmaker.net/storage/setting/107593/dlsu-logo-green.png?id=c20d09f06ae8bdcd0d0e457e0d90010e"
BUTTON_HREF = "https://dlsu.cloud.processmaker.net/requests/{{_request.id}}"
FONT = "Poppins, Arial, Helvetica, sans-serif"


def sub(template, **kw):
    """Replace __KEY__ tokens. Avoids %/format/f-string conflicts with CSS & mustache."""
    out = template
    for k, v in kw.items():
        out = out.replace("__" + k + "__", v)
    return out


# ---------------------------------------------------------------- EMAIL builder
def email_html(heading, body_lines, button_label):
    body_html = ""
    for ln in body_lines:
        body_html += ('<p style="font-size: 14px; line-height: 1.6; word-break: break-word; '
                      'mso-line-height-alt: 22px; margin: 0 0 10px 0;">' + (ln if ln else "&nbsp;") + '</p>')

    tpl = (
        # logo header
        '<table class="nl-container" style="table-layout: fixed; vertical-align: top; min-width: 320px; '
        'margin: 0 auto; border-spacing: 0; border-collapse: collapse; mso-table-lspace: 0pt; '
        'mso-table-rspace: 0pt; background-color: transparent; width: 100%;" role="presentation" '
        'width="100%" cellspacing="0" cellpadding="0" bgcolor="transparent"><tbody>'
        '<tr style="vertical-align: top;" valign="top">'
        '<td style="word-break: break-word; vertical-align: top;" valign="top">'
        '<div style="background-color: transparent;"><div class="block-grid" style="margin: 0 auto; '
        'min-width: 320px; max-width: 590px; overflow-wrap: break-word; word-wrap: break-word; '
        'word-break: break-word; background-color: transparent;">'
        '<div style="border-collapse: collapse; display: table; width: 100%; background-color: transparent;">'
        '<div class="col num12" style="min-width: 320px; max-width: 590px; display: table-cell; '
        'vertical-align: top; width: 590px;"><div style="width: 100% !important;">'
        '<div style="border: 0px solid transparent; padding: 25px 0px 25px 0px;">'
        '<div class="img-container center autowidth" style="padding-right: 0px; padding-left: 0px;" align="center">'
        '<a style="outline: none;" tabindex="-1" href="https://www.dlsu.edu.ph/" target="_blank" rel="noopener"> '
        '<img class="center autowidth" style="text-decoration: none; -ms-interpolation-mode: bicubic; '
        'height: auto; border: 0; width: 100%; max-width: 346px; display: block;" title="Logo" '
        'src="__LOGO__" alt="Logo" width="346" align="middle" border="0"/></a> </div></div></div></div></div></div></div>'
        # green banner: heading + number + button
        '<div style="background-color: transparent;"><div class="block-grid" style="margin: 0 auto; '
        'min-width: 320px; max-width: 590px; overflow-wrap: break-word; word-wrap: break-word; '
        'word-break: break-word; background-color: __GREEN__;">'
        '<div style="border-collapse: collapse; display: table; width: 100%; background-color: __GREEN__;">'
        '<div class="col num12" style="min-width: 320px; max-width: 590px; display: table-cell; '
        'vertical-align: top; width: 590px;"><div style="width: 100% !important;">'
        '<div style="border: 0px solid transparent; padding: 5px 0px 0px 0px;">'
        '<div style="color: #ffffff; font-family: __FONT__; line-height: 1.2; padding: 40px 10px 10px 10px;">'
        '<div style="font-size: 14px; line-height: 1.2; color: #ffffff; font-family: __FONT__; mso-line-height-alt: 17px;">'
        '<p style="font-size: 22px; line-height: 1.3; word-break: break-word; text-align: center; '
        'mso-line-height-alt: 29px; margin: 0;"><strong>__HEADING__</strong></p></div></div>'
        '<div style="color: #ffffff; font-family: __FONT__; line-height: 1.2; padding: 0px 10px 10px 10px;">'
        '<div style="font-size: 14px; line-height: 1.2; color: #ffffff; font-family: __FONT__; mso-line-height-alt: 17px;">'
        '<p style="font-size: 14px; line-height: 1.2; word-break: break-word; text-align: center; '
        'mso-line-height-alt: 17px; margin: 0; padding-bottom: 20px;"><strong><span style="font-size: 30px;">{{number}}</span></strong></p>'
        '</div></div>'
        '</div></div></div></div></div></div>'
        # white body
        '<div style="background-color: transparent;"><div class="block-grid" style="margin: 0 auto; '
        'min-width: 320px; max-width: 590px; overflow-wrap: break-word; word-wrap: break-word; '
        'word-break: break-word; background-color: #ffffff;">'
        '<div style="border-collapse: collapse; display: table; width: 100%; background-color: #ffffff;">'
        '<div class="col num12" style="min-width: 320px; max-width: 590px; display: table-cell; '
        'vertical-align: top; width: 590px;"><div style="width: 100% !important;">'
        '<div style="border: 0px solid transparent; padding: 20px 10px 10px 10px;">'
        '<div style="color: #000000; font-family: __FONT__; line-height: 1.6; padding: 10px;">'
        '<div style="font-size: 14px; line-height: 1.6; color: #000000; font-family: __FONT__; mso-line-height-alt: 22px;">'
        '__BODY__'
        '</div></div>'
        # centered button below the whole message
        '<div class="button-container" style="padding: 10px 10px 30px 10px;" align="center">'
        '<table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" '
        'style="margin: 0 auto;"><tbody><tr>'
        '<td align="center" bgcolor="__GREEN__" style="background-color: __GREEN__; border-radius: 25px; '
        'mso-padding-alt: 12px 30px;">'
        '<a style="-webkit-text-size-adjust: none; text-decoration: none; display: inline-block; '
        'color: #ffffff; background-color: __GREEN__; border-radius: 25px; padding: 12px 30px; '
        'font-family: __FONT__; font-size: 15px; font-weight: 600; text-align: center; word-break: keep-all;" '
        'href="__HREF__" target="_blank" rel="noopener">__BTN__</a>'
        '</td></tr></tbody></table></div>'
        '</div></div></div></div></div></div>'
        '</td></tr></tbody></table>'
    )
    return sub(tpl, LOGO=LOGO, GREEN=GREEN, FONT=FONT, HREF=BUTTON_HREF,
               HEADING=heading, BTN=button_label, BODY=body_html)


# ------------------------------------------------ shared item / screen builders
# Matches the functional ProcessMaker builder/API export format:
#  - config is a real JSON array (NOT a stringified blob)
#  - Rich Text uses component "FormHtmlViewer" with a per-item uuid and the full
#    8-entry inspector (incl. DeviceVisibility + EncryptedConfig)
#  - page carries an "order"; screen carries "projects"

def html_viewer_item(content, css_selector=None):
    cfg = {"icon": "fas fa-pencil-ruler", "name": "form_html_viewer", "label": None,
           "content": content, "interactive": True, "renderVarHtml": False}
    if css_selector:
        cfg["customCssSelector"] = css_selector
    return {
        "uuid": str(uuid.uuid4()),
        "label": "Rich Text",
        "config": cfg,
        "component": "FormHtmlViewer",
        "inspector": [
            {"type": "FormTextArea", "field": "content",
             "config": {"rows": 5, "label": "Content", "value": None, "helper": "The HTML text to display"}},
            {"type": "FormCheckbox", "field": "renderVarHtml",
             "config": {"label": "Render HTML from a Variable", "value": None, "helper": None}},
            {"type": "FormInput", "field": "conditionalHide",
             "config": {"label": "Visibility Rule", "helper": "This control is hidden until this expression is true"}},
            {"type": "DeviceVisibility", "field": "deviceVisibility",
             "config": {"label": "Device Visibility", "helper": "This control is hidden until this expression is true"}},
            {"type": "FormInput", "field": "customFormatter",
             "config": {"label": "Custom Format String",
                        "helper": "Use the Mask Pattern format <br> Date ##/##/#### <br> SSN ###-##-#### <br> Phone (###) ###-####",
                        "validation": None}},
            {"type": "FormInput", "field": "customCssSelector",
             "config": {"label": "CSS Selector Name", "helper": "Use this in your custom css rules",
                        "validation": "regex: [-?[_a-zA-Z]+[_-a-zA-Z0-9]*]"}},
            {"type": "FormInput", "field": "ariaLabel",
             "config": {"label": "Aria Label",
                        "helper": "Attribute designed to help assistive technology (e.g. screen readers) attach a label"}},
            {"type": "FormInput", "field": "tabindex",
             "config": {"label": "Tab Order",
                        "helper": "Order in which a user will move focus from one control to another by pressing the Tab key",
                        "validation": "regex: [0-9]*"}},
            {"type": "EncryptedConfig", "field": "encryptedConfig",
             "config": {"label": "Encrypted", "helper": None}},
        ],
        "editor-control": "FormHtmlEditor",
        "editor-component": "FormHtmlEditor",
    }


def make_screen(_id, title, stype, content, css_selector=None, custom_css=None):
    cfg = [{"name": title, "items": [html_viewer_item(content, css_selector)], "order": 1}]
    return {
        "id": _id, "uuid": str(uuid.uuid4()), "screen_category_id": "1",
        "title": title, "description": title, "type": stype,
        "config": cfg, "computed": [], "custom_css": custom_css,
        "created_at": "2026-06-09T00:00:00+00:00", "updated_at": "2026-06-09T00:00:00+00:00",
        "status": "ACTIVE", "key": None, "watchers": [], "translations": None,
        "is_template": 0, "asset_type": None, "is_default": 0, "projects": "[]",
    }


def make_email_screen(_id, title, content):
    return make_screen(_id, title, "EMAIL", content)


# -------------------------------------------------------------- DISPLAY builder
def row(label, var):
    tpl = (
        '<tr>'
        '<td style="padding:8px 12px; border-bottom:1px solid #e5e7eb; font-family:__FONT__; '
        'font-size:13px; color:#374151; font-weight:600; width:40%; vertical-align:top;">__LABEL__</td>'
        '<td style="padding:8px 12px; border-bottom:1px solid #e5e7eb; font-family:__FONT__; '
        'font-size:13px; color:#111827; vertical-align:top;">{{__VAR__}}</td>'
        '</tr>'
    )
    return sub(tpl, FONT=FONT, LABEL=label, VAR=var)


def section(title, rows_html):
    tpl = (
        '<div style="margin:0 0 18px 0; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">'
        '<div style="background-color:__GREEN__; color:#ffffff; font-family:__FONT__; font-size:14px; '
        'font-weight:700; padding:10px 14px;">__TITLE__</div>'
        '<table style="width:100%; border-collapse:collapse;"><tbody>__ROWS__</tbody></table>'
        '</div>'
    )
    return sub(tpl, GREEN=GREEN, FONT=FONT, TITLE=title, ROWS=rows_html)


def display_html():
    header = sub(
        '<div style="background-color:__GREEN__; color:#ffffff; font-family:__FONT__; text-align:center; '
        'padding:22px 16px; border-radius:8px; margin-bottom:20px;">'
        '<div style="font-size:22px; font-weight:700;">Request for Payment &mdash; Concessionaires</div>'
        '<div style="font-size:14px; opacity:0.9; margin-top:4px;">Request {{number}} '
        '&nbsp;&middot;&nbsp; {{requestDateDisplay}}</div>'
        '</div>', GREEN=GREEN, FONT=FONT)

    request_details = section("Request Details", "".join([
        row("Business Unit", "businessUnit"),
        row("Campus", "campusChoice"),
        row("Payment Terms", "paymentTerms"),
        row("Reference Number", "number"),
        row("Mode of Payment", "methodOfPayment"),
        row("Date", "requestDateDisplay"),
    ]))

    payment_info = section("Payment Information", "".join([
        row("Transaction Type", "transactionType"),
        row("Amount Requested", "amountRequested"),
        row("Currency", "transactionCurrency"),
        row("Invoice Type", "invoiceType"),
    ]))

    requester_info = section("Requester &amp; Payee Information", "".join([
        row("Payee Name", "payee"),
        row("Payee ID", "SUPPLIER_ID"),
        row("Payee Type", "SUPPLIER_TYPE"),
        row("Description", "description"),
        row("Requester's Department", "req_Department"),
        row("Approving Department", "userDept"),
        row("Notes", "notes"),
    ]))

    items_table = sub(
        '<div style="margin:0 0 18px 0; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden;">'
        '<div style="background-color:__GREEN__; color:#ffffff; font-family:__FONT__; font-size:14px; '
        'font-weight:700; padding:10px 14px;">Concessionaires</div>'
        '<table style="width:100%; border-collapse:collapse; font-family:__FONT__; font-size:13px;">'
        '<thead><tr style="background-color:#f3f4f6; color:#374151;">'
        '<th style="text-align:left; padding:8px 12px; border-bottom:1px solid #e5e7eb;">Date</th>'
        '<th style="text-align:left; padding:8px 12px; border-bottom:1px solid #e5e7eb;">Expense Name</th>'
        '<th style="text-align:left; padding:8px 12px; border-bottom:1px solid #e5e7eb;">Invoice #</th>'
        '<th style="text-align:right; padding:8px 12px; border-bottom:1px solid #e5e7eb;">Amount</th>'
        '<th style="text-align:left; padding:8px 12px; border-bottom:1px solid #e5e7eb;">GL Account</th>'
        '</tr></thead><tbody>'
        '{{#ITEM_recordList}}'
        '<tr>'
        '<td style="padding:8px 12px; border-bottom:1px solid #f0f0f0; color:#111827;">{{date}}</td>'
        '<td style="padding:8px 12px; border-bottom:1px solid #f0f0f0; color:#111827;">{{expenses}}</td>'
        '<td style="padding:8px 12px; border-bottom:1px solid #f0f0f0; color:#111827;">{{invoice}}</td>'
        '<td style="padding:8px 12px; border-bottom:1px solid #f0f0f0; color:#111827; text-align:right;">{{amount}}</td>'
        '<td style="padding:8px 12px; border-bottom:1px solid #f0f0f0; color:#111827;">{{glAccount}}</td>'
        '</tr>'
        '{{/ITEM_recordList}}'
        '<tr>'
        '<td colspan="3" style="padding:10px 12px; font-weight:700; color:#111827; text-align:right;">Total Amount</td>'
        '<td style="padding:10px 12px; font-weight:700; color:__GREEN__; text-align:right;">{{INVOICE_AMOUNT}}</td>'
        '<td style="border-top:2px solid #e5e7eb;"></td>'
        '</tr>'
        '</tbody></table></div>', GREEN=GREEN, FONT=FONT)

    return ('<div style="max-width:820px; margin:0 auto; padding:8px;">'
            + header + request_details + payment_info + requester_info + items_table + '</div>')


def make_display_screen(_id, title, content):
    return make_screen(_id, title, "DISPLAY", content,
                       css_selector="rfp-summary", custom_css=".rfp-summary { display:block; }\n")


# ----------------------------------------------------------------------- build
def write(name, obj):
    path = os.path.join(OUT_DIR, name)
    with open(path, "w", encoding="utf-8") as f:
        json.dump(obj, f, ensure_ascii=False, indent=1)
    # validate round-trip; config must be a real array of pages
    with open(path, encoding="utf-8") as f:
        d = json.load(f)
    assert isinstance(d["config"], list), "config must be a JSON array"
    assert d["config"][0]["items"][0]["component"] == "FormHtmlViewer"
    print("wrote", path, "(%d bytes) OK" % os.path.getsize(path))


e1 = make_email_screen(
    9001, "RFP_CON_PENDING_APPROVAL_EMAIL - CSS Updates",
    email_html("You have a Pending Request for your Approval",
               ["Hi,", "You have a pending request awaiting your approval.",
                "Approval Link: {{request_link}}", "Thank you!", "Best Regards,", "The BPMS Team"],
               "Review the Request"))

e2 = make_email_screen(
    9002, "RFP_CON_MORE_INFORMATION_EMAIL - CSS Updates",
    email_html("Request for More Information",
               ["Hi,", "The approver has requested more information regarding your request before it can proceed.",
                "Request Link: {{request_link}}", "Thank you!", "Best Regards,", "The BPMS Team"],
               "Update the Request"))

e3 = make_email_screen(
    9003, "RFP_CON_REJECTED_INFORM_EMAIL - CSS Updates",
    email_html("Your Request has been Rejected",
               ["Hi,", "This is to inform you that the {{number}} has been rejected by the approver.",
                "Request Link: {{request_link}}", "Best regards,", "The BPMS Team"],
               "Go to the Request"))

d1 = make_display_screen(9004, "RFP_CON_REQUEST_SUMMARY - CSS Updates", display_html())

write("RFP_CON_PENDING_APPROVAL_EMAIL_CSS_Updates.json", e1)
write("RFP_CON_MORE_INFORMATION_EMAIL_CSS_Updates.json", e2)
write("RFP_CON_REJECTED_INFORM_EMAIL_CSS_Updates.json", e3)
write("RFP_CON_REQUEST_SUMMARY_CSS_Updates.json", d1)
print("done")
