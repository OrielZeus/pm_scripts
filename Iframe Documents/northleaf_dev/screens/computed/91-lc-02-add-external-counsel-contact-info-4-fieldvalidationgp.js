/******************************
 * Fields Validation GP
 * 
 * by Adriana Centellas
 *****************************/

function valueIsValid(value) {
    return (value !== null && value !== "" && value !== 0 && value !== [] && (typeof value !== "undefined"));
}

let fieldsValidation = {
    isValid: true,
	isValidCounsel: true,
	messageCounsel: "",
};
//Validate Address tax requirements and engage outside counsel
if (!this.PE_GP_CONTACT_INFORMATION_NOT_APPLICABLE) {
	if (!valueIsValid(this.PE_GP_COUNSEL_COMPANY_NAME)) {
		fieldsValidation.isValidCounsel = false;
	}
	if (!valueIsValid(this.PE_GP_COUNSEL_CONTACT_NAME)) {
		fieldsValidation.isValidCounsel = false;
	}
	if (Array.isArray(this.PE_GP_COUNSEL_EMAIL_TABLE) && this.PE_GP_COUNSEL_EMAIL_TABLE.length <= 0) {
		fieldsValidation.isValidCounsel = false;
		fieldsValidation.messageCounsel = "At least one row is required";
	} else {
		let i = 0;
		let counselEmailOK = "YES";
		this.PE_GP_COUNSEL_EMAIL_TABLE.forEach(row => {
			i++;
			if (!valueIsValid(row.PE_GP_COUNSEL_EMAIL)) {				
				fieldsValidation.isValidCounsel = false;
				counselEmailOK = "NO";
			}
		});
		if (counselEmailOK == "NO") {
			fieldsValidation.messageCounsel = "Please enter a valid value in all email rows";
		}
	}
}
if (!fieldsValidation.isValidCounsel) {
	fieldsValidation.isValid = false;
} 
if (this.PE_SAVE_SUBMIT_LC2 == 'SAVE') {
	fieldsValidation.isValidCounsel = true;
}
return fieldsValidation;