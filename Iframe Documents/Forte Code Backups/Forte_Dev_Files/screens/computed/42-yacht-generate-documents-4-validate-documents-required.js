/*  
 * Validate Documents Required
 * by Helen Callisaya
 */

//Get length of array and loop
let requiredLen = 0;
if (this.YQP_ADOBE_WORKFLOW_DOCUMENTS &&
    typeof this.YQP_ADOBE_WORKFLOW_DOCUMENTS === 'object' &&
    this.YQP_ADOBE_WORKFLOW_DOCUMENTS.YQP_ADOBE_DOCUMENTS_REQUIRED !== undefined &&
    this.YQP_ADOBE_WORKFLOW_DOCUMENTS.YQP_ADOBE_DOCUMENTS_REQUIRED !== null &&
    (Array.isArray(this.YQP_ADOBE_WORKFLOW_DOCUMENTS.YQP_ADOBE_DOCUMENTS_REQUIRED) ||
     typeof this.YQP_ADOBE_WORKFLOW_DOCUMENTS.YQP_ADOBE_DOCUMENTS_REQUIRED === 'string')) {
    
    requiredLen = this.YQP_ADOBE_WORKFLOW_DOCUMENTS.YQP_ADOBE_DOCUMENTS_REQUIRED.length;
}

let loopRequestLen = this.YQP_ADOBE_WORKFLOW_LIST.length;

//Defines checkbox Manual signed document
var checkManual = this.YQP_VISIBLE_MANUAL_SIGNED_DOCUMENT;

//Defines an object to store values
var submitVariables = new Object()
submitVariables['YQP_MESSAGE_NULL_SHOW'] = 'NO';
submitVariables['YQP_MESSAGE_ATTACH_NULL_SHOW'] = 'NO';
submitVariables['YQP_MESSAGE_DUPLICATE_SHOW'] = 'NO';
submitVariables['YQP_MESSAGE_REQUIRED_SHOW'] = 'NO';
submitVariables['YQP_SUBMIT_SHOW'] = 'YES';
submitVariables['YQP_MESSAGE_DOCUMENT_REQUIRED'] = '';
submitVariables['YQP_MESSAGE_MANUAL_DOCUMENT_VISIBLE'] = 'NO';
//Set auxiliary array
var aRequerid = new Array();
var aDuplicate = new Array(); 
var aListRequerid = new Array();
//Generates a simple array of the required documents
for (let i = 0; i < requiredLen; i++) {
    //console.log(this.YQP_ADOBE_WORKFLOW_DOCUMENTS);
    aListRequerid.push(this.YQP_ADOBE_WORKFLOW_DOCUMENTS.YQP_ADOBE_DOCUMENTS_REQUIRED[i]['LABEL']); 
}
//Evaluate the document loop 
for (let i = 0; i < loopRequestLen; i++) {
    //Validates that the Name or attached field is null
    if (this.YQP_ADOBE_WORKFLOW_LIST[i]['YQP_ADOBE_DOCUMENTS_OPTIONS'] === null) {
        submitVariables['YQP_MESSAGE_NULL_SHOW'] = 'YES';
    } else {
        if (this.YQP_ADOBE_WORKFLOW_LIST[i]['YQP_ADOBE_WORKFLOW_DOCUMENTS_UPLOAD'] === null) {
            submitVariables['YQP_MESSAGE_NULL_SHOW'] = 'YES';
        } else {
            //Validate that there are no duplicate documents
            const validateDuplicate = aDuplicate.find(aDuplicate => aDuplicate.LABEL === this.YQP_ADOBE_WORKFLOW_LIST[i]['YQP_ADOBE_DOCUMENTS_OPTIONS']['LABEL']);
            if (validateDuplicate == undefined) {
                aDuplicate.push({LABEL: this.YQP_ADOBE_WORKFLOW_LIST[i]['YQP_ADOBE_DOCUMENTS_OPTIONS']['LABEL'], REQUERID: this.YQP_ADOBE_WORKFLOW_LIST[i]['YQP_ADOBE_DOCUMENTS_OPTIONS']['REQUIRED']});
                //Validates if the document is required to build a simple array
                if(this.YQP_ADOBE_WORKFLOW_LIST[i]['YQP_ADOBE_DOCUMENTS_OPTIONS']['REQUIRED'] == true) {
                    aRequerid.push(this.YQP_ADOBE_WORKFLOW_LIST[i]['YQP_ADOBE_DOCUMENTS_OPTIONS']['LABEL']);
                }
            } else {
                submitVariables['YQP_MESSAGE_DUPLICATE_SHOW'] = 'YES';
            }
        }
    }
}
//Evaluate the document attach loop
for (let i = 0; i < this.YQP_CONFIRMATION_DOCUMENTS.length; i++) {
    //Check that the Name is not null or empty
    if (this.YQP_CONFIRMATION_DOCUMENTS[i]['YQP_CONFIRMATION_ATTACHMENT_NAME'] === null || 
        this.YQP_CONFIRMATION_DOCUMENTS[i]['YQP_CONFIRMATION_ATTACHMENT_NAME'] == "" || 
        this.YQP_CONFIRMATION_DOCUMENTS[i]['YQP_CONFIRMATION_ATTACHMENT_NAME'] == undefined) {
        if (this.YQP_CONFIRMATION_DOCUMENTS[i]['YQP_CONFIRMATION_UPLOAD_DOCUMENT'] === null) {
            //Valid if length is 1
            if (this.YQP_CONFIRMATION_DOCUMENTS.length == 1) {
                submitVariables['YQP_MESSAGE_ATTACH_NULL_SHOW'] = 'NO';
            } else {
                submitVariables['YQP_MESSAGE_ATTACH_NULL_SHOW'] = 'YES';
            } 
        } else { 
            submitVariables['YQP_MESSAGE_ATTACH_NULL_SHOW'] = 'YES';          
        }
    } else {
        //Check that the Document is not null
        if (this.YQP_CONFIRMATION_DOCUMENTS[i]['YQP_CONFIRMATION_UPLOAD_DOCUMENT'] === null) {
            submitVariables['YQP_MESSAGE_ATTACH_NULL_SHOW'] = 'YES';
        } 
    }
}
//Compare the simple array of required documents
let missingRequired = new Array();
missingRequired  = aListRequerid.filter(element => !aRequerid.includes(element));
if(missingRequired.length != 0) {
    submitVariables['YQP_MESSAGE_REQUIRED_SHOW'] = 'YES';
    var messageDocumentRequired = missingRequired.toString();
    submitVariables['YQP_MESSAGE_DOCUMENT_REQUIRED'] = messageDocumentRequired.replace(/,/g,", ");
}
//Valid Manual Document
if (checkManual == 'true' || checkManual == true) {
	let manualDocument = this.YQP_MANUAL_SIGNED_DOCUMENT;
	if (this.YQP_MANUAL_SIGNED_DOCUMENT !== undefined && 
        this.YQP_MANUAL_SIGNED_DOCUMENT !== null &&
        this.YQP_MANUAL_SIGNED_DOCUMENT !== '') {
		submitVariables['YQP_SUBMIT_SHOW'] = 'MANUAL';
	} else {
		submitVariables['YQP_SUBMIT_SHOW'] = 'NO';
        submitVariables['YQP_MESSAGE_MANUAL_DOCUMENT_VISIBLE'] = 'YES';
	}
} else {
	//Valid if all conditions are met
	if (submitVariables['YQP_MESSAGE_NULL_SHOW'] == 'YES' || 
		submitVariables['YQP_MESSAGE_DUPLICATE_SHOW'] == 'YES' || 
		submitVariables['YQP_MESSAGE_REQUIRED_SHOW'] == 'YES' ||
		submitVariables['YQP_MESSAGE_ATTACH_NULL_SHOW'] == 'YES') {
		submitVariables['YQP_SUBMIT_SHOW'] = 'NO';
	}
}
console.log(submitVariables);
return submitVariables;