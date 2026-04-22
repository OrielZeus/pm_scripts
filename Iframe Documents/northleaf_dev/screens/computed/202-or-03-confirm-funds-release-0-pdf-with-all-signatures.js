let pdfSignature = this.FRA_PDF;    // Pdf Generate
if (this.PE_DOCUMENT_SIGNED == 'Yes') {
    pdfSignature = this.PE_UPLOAD_FUNDING_AUTHORIZATION;    // Pdf Uploaded
}
return pdfSignature;