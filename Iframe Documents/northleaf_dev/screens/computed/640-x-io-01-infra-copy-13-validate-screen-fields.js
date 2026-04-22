/**
 * Validate Custom iFrame Grid
 * 
 * by Jhon Chacolla
 * modified by Adriana Centellas
 */
let isValid = true;
let errorMessage = [];
let submitterManager = this.IN_SUBMITTER_MANAGER;


// Regular expression allowing up to 60 alphanumeric characters and spaces
const regex = /^[a-zA-Z0-9\s.,!?@+#-]{0,60}$/;

/**
 * Validates input against the regex.
 * @param {string} input - The user input to validate.
 * @return {boolean} Returns true if the input is valid, otherwise false.
 */
function validateInput(input) {
    const isValid = regex.test(input);
    console.log(isValid ? "Valid input!" : "Invalid input. Maximum 60 characters allowed.");
    return isValid;
}

if (!this.IN_INVOICE_VENDOR) {
    isValid = isValid && false;
    errorMessage.push('A valid Vendor is required.');
} else {
    const vendor = this.PM_VENDOR_SOURCE.find(item => item.ID === this.IN_INVOICE_VENDOR);
    if (vendor && vendor.LABEL === "NEW VENDOR") {
        isValid = isValid && false;
        errorMessage.push('"New vendor" is not valid');
    }
}

if (!submitterManager || submitterManager.length <= 0) {
    isValid = isValid && false;
    errorMessage.push('A valid Manager is required.');
}

if (!validateInput(this.IN_INVOICE_TRANS_COMMENTS)){
    isValid = isValid && false;
    errorMessage.push('Invalid input. Maximum 60 characters allowed.');
}

if(!this.isCustomeGridValid || this.isCustomeGridValid == 'false') {
    isValid = isValid && false;
    errorMessage.push('Please complete the Expense Table.');
}

let errorMessageString = '';
errorMessageString += '<ul>';
errorMessage.forEach(message => {
    console.log("loop");
    errorMessageString += '<li>' + message + '</li>'
});
errorMessageString += '</ul>';
return {
    'isValid': isValid,
    'message': errorMessageString
};