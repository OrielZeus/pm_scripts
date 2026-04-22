/**
 * Screen computed property: IframeInputJson
 * Optional JSON for Script tasks / variables when not using URL-only ?data=.
 */
var o = {
  ExampleTitle: this.ExampleTitle || ''
};
try {
  return JSON.stringify(o);
} catch (e) {
  return '{}';
}
