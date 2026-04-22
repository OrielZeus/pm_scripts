let filePrev = '';

if(this.file_upload) {
    let fileExt = this.file_upload.split('.').pop();

    if(fileExt === "pdf") {
        filePrev = '<iframe id="pdfViewer" src="/vendor/processmaker/packages/package-files/ViewerJS/?title=W2-Tax-Document#/storage/tmp/' + this.file_upload + '" allowfullscreen="allowfullscreen" webkitallowfullscreen=""></iframe>';
    } else {
        filePrev = '<img src="/storage/tmp/' + this.file_upload + '" />';
    }
}

return filePrev;