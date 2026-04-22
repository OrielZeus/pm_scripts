/*
 * by Diego Tapia
 */

var dealFolder = this.PE_DEAL_FOLDER;
var copyHTML = '<a onclick="navigator.clipboard.writeText(\'' + dealFolder + '\');" style="cursor:pointer"><br><img src="/public-files/copy.png" alt="Copy to Clipboard"> Copy </img></a>';
return copyHTML;