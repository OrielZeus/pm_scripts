/************************************
 * Set Link to download PDF IC 02
 * by Telmo Chiri
 ***********************************/
//Get current url
let currentUrl = window.location.href;
let environmentUrl = currentUrl.split("/")[0];
let requesID = this._request.id;
let url = environmentUrl + '/request/' + requesID + '/files';
return url;