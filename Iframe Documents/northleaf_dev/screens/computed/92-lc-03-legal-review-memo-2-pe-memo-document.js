let PE_MEMO_DOCUMENT = [
    {
      "PE_MEMO_UPLOAD": null,
      "PE_MEMO_DESCRIPTION": ""
    }
];

if (this.PE_MEMO_DOCUMENT_MULTIPLE_FILES) {
    PE_MEMO_DOCUMENT = [];
    this.PE_MEMO_DOCUMENT_MULTIPLE_FILES.forEach((files)=>{
        let memoFile = {
            "PE_MEMO_UPLOAD": files.file,
            "PE_MEMO_DESCRIPTION": this.PE_MEMO_DESCRIPTION
        };
        PE_MEMO_DOCUMENT.push(memoFile);
    });
}

return PE_MEMO_DOCUMENT;