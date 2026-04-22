/**
 * Screen computed: UserNameFromIframe — lee #iframe-USER_NAME dentro de iframe-psTools
 */
void this.IframeGridHtmlFieldsDirect;
void this.PrefillFullName;
void this.PrefillEmail;

function __pmResolveIframePsTools() {
  var roots = [];
  try {
    roots.push(document);
  } catch (e0) {}
  try {
    if (typeof window !== 'undefined' && window.parent && window.parent.document) {
      roots.push(window.parent.document);
    }
  } catch (e1) {}
  try {
    if (
      typeof window !== 'undefined' &&
      window.top &&
      window.top.document &&
      window.top.document !== document
    ) {
      roots.push(window.top.document);
    }
  } catch (e2) {}
  var id = 'iframe-psTools';
  for (var i = 0; i < roots.length; i++) {
    var d = roots[i];
    if (!d) {
      continue;
    }
    var f = d.getElementById(id);
    if (f && f.tagName === 'IFRAME') {
      return f;
    }
    if (d.querySelector) {
      var q = d.querySelector('iframe#' + id);
      if (q) {
        return q;
      }
    }
  }
  return null;
}

var raw = '';
var frame = __pmResolveIframePsTools();
try {
  if (frame) {
    var innerDoc = frame.contentDocument || (frame.contentWindow && frame.contentWindow.document);
    if (innerDoc) {
      var el = innerDoc.getElementById('iframe-USER_NAME');
      if (el) {
        var v = el.value;
        if (v !== undefined && v !== null) {
          raw = String(v);
        }
      }
    }
  }
} catch (e3) {}

if (!raw) {
  try {
    if (typeof $ !== 'undefined' && $('#iframe-psTools').length) {
      var jqVal = $('#iframe-psTools').contents().find('#iframe-USER_NAME').val();
      raw = jqVal != null ? String(jqVal) : '';
    }
  } catch (e4) {}
}

return raw;
