/**
 * Screen computed: IframeInputJsonFieldsDirect
 * JSON para Script / variable iframe (mismo payload que ?data= del IframeGrid).
 */
var o = {
  PrefillFullName: this.PrefillFullName || '',
  PrefillEmail: this.PrefillEmail || ''
};
try {
  return JSON.stringify(o);
} catch (e) {
  return '{}';
}
