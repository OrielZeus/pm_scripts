/**
 * Screen computed: IframeGridHtmlFieldsDirect
 * Publicar script PHP con slug example-vue-iframe-fields-direct
 */
var scriptSlug = 'example-vue-iframe-fields-direct';
var data = {
  PrefillFullName: (this.PrefillFullName || '') + '',
  PrefillEmail: (this.PrefillEmail || '') + ''
};
var jsonData = JSON.stringify(data);
var encodedData = encodeURIComponent(jsonData);
var screenHeight = 1200;
if (typeof top !== 'undefined' && typeof top.innerHeight !== 'undefined') {
  screenHeight = top.innerHeight - 50;
}
var code =
  '<div class="pm-iframe-grid-host" style="margin:0;padding:0;"><style>.pm-iframe-grid-host .iframe-container{background:url(/public-files/loadingIMG.gif) center center no-repeat;min-height:200px}</style><div class="iframe-container"><iframe id="iframe-psTools" src="/api/1.0/pstools/script/' +
  scriptSlug +
  '?data=' +
  encodedData +
  '" frameborder="0" style="overflow:hidden;overflow-x:hidden;overflow-y:hidden;height:' +
  screenHeight +
  'px;width:100%;border:0;"></iframe></div></div>';
return code;
