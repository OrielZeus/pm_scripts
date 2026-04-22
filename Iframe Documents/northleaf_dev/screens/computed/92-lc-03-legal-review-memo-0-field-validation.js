/*****************************
 * Fields Validation
 * 
 * by Helen Callisaya
 * modified by Cinthia Romero
 ****************************/

function valueIsValid(value) {
    return (value !== null && value !== "" && value !== 0 && value !== [] && (typeof value !== "undefined"));
}

let fieldsValidation = {
    isValid: true,
	isValidDocument: true,
    messageValid: ""
};

//Validates only if the loop is visible
if (this.PE_LEGAL_REVIEW_NOT_APPLICABLE != true) {
	if (Array.isArray(this.PE_MEMO_DOCUMENT) && this.PE_MEMO_DOCUMENT.length <= 0) {
		fieldsValidation.isValidDocument = false;
		fieldsValidation.messageValid = "At least one document is required";
	} else {
		let i = 0;
		let allDocumentsExist = "YES";
		this.PE_MEMO_DOCUMENT.forEach(row => {
			if (!valueIsValid(row.PE_MEMO_UPLOAD)) {
				i++;
				fieldsValidation.isValidDocument = false;
				allDocumentsExist = "NO";
			}
		});
		if (allDocumentsExist == "NO") {
			fieldsValidation.messageValid = "Please upload a valid document to all created rows";
		}
	}
	fieldsValidation.isValid = fieldsValidation.isValidDocument;
}

return fieldsValidation;