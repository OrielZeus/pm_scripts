/************************************
 * Set urls of parent case documents 
 * 
 * by Cinthia Romero
 * modified by Adriana Centellas
 ***********************************/

// Get current URL
var currentUrl = window.location.href;
var environmentUrl = currentUrl.split("/")[0];
//var parentRequestID = this.PE_PARENT_REQUEST_ID;
var parentRequestID = this._request.id;
var documentsUrl = {};

// Function to generate download links if files exist
function getDocumentLinks(fileArray) {
    return fileArray.map(fileObj => 
        "<a href='" + environmentUrl + "/request/" + parentRequestID + "/files/" + fileObj.file + "'>Download</a>"
    ).join("<br>"); // Change to ", " if you prefer comma-separated links
}

// Validate and generate links for each document type
if (Array.isArray(this._parent.PE_UPLOAD_IC_PRESENTATION) && this._parent.PE_UPLOAD_IC_PRESENTATION.length > 0) {
    documentsUrl["IC_PRESENTATION_DOCUMENT"] = getDocumentLinks(this._parent.PE_UPLOAD_IC_PRESENTATION);
}
if (this.PE_UPLOAD_DD_REC_NA !== true && Array.isArray(this._parent.PE_UPLOAD_DD_REC) && this._parent.PE_UPLOAD_DD_REC.length > 0) {
    documentsUrl["DD_REC_DOCUMENT"] = getDocumentLinks(this._parent.PE_UPLOAD_DD_REC);
}
if (this.PE_UPLOAD_BEAT_UP_NA !== true && Array.isArray(this._parent.PE_UPLOAD_BEAT_UP) && this._parent.PE_UPLOAD_BEAT_UP.length > 0) {
    documentsUrl["BEAT_UP_DOCUMENT"] = getDocumentLinks(this._parent.PE_UPLOAD_BEAT_UP);
}
if (this.PE_UPLOAD_BLACK_HAT_NA !== true && Array.isArray(this._parent.PE_UPLOAD_BLACK_HAT) && this._parent.PE_UPLOAD_BLACK_HAT.length > 0) {
    documentsUrl["BLACK_HAT_DOCUMENT"] = getDocumentLinks(this._parent.PE_UPLOAD_BLACK_HAT);
}

return documentsUrl;