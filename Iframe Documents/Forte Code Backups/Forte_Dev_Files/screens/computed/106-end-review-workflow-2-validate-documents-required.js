/*  
 * Validate Documents Required
 * by Helen Callisaya
 */
 
//Get length of array and loop
let requiredLen = this.YQP_ADOBE_WORKFLOW_DOCUMENTS.YQP_ADOBE_DOCUMENTS_REQUIRED.length;
let loopRequestLen = this.YQP_ADOBE_WORKFLOW_LIST.length;

//Defines an object to store values
var submitVariables = new Object()
submitVariables['YQP_MESSAGE_NULL_SHOW'] = 'NO';
submitVariables['YQP_MESSAGE_DUPLICATE_SHOW'] = 'NO';
submitVariables['YQP_MESSAGE_REQUIRED_SHOW'] = 'NO';
submitVariables['YQP_SUBMIT_SHOW'] = 'YES';
submitVariables['YQP_MESSAGE_DOCUMENT_REQUIRED'] = '';
//Set auxiliary array
var aRequerid = new Array();
var aDuplicate = new Array(); 
var aListRequerid = new Array();
//Generates a simple array of the required documents
for (let i = 0; i < requiredLen; i++) {
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
//Compare the simple array of required documents
let missingRequired = new Array();
missingRequired  = aListRequerid.filter(element => !aRequerid.includes(element));
if(missingRequired.length != 0) {
    submitVariables['YQP_MESSAGE_REQUIRED_SHOW'] = 'YES';
	var messageDocumentRequired = missingRequired.toString();
    submitVariables['YQP_MESSAGE_DOCUMENT_REQUIRED'] = messageDocumentRequired.replace(/,/g,", ");    
}
//Valid if all conditions are met
if (submitVariables['YQP_MESSAGE_NULL_SHOW'] == 'YES' || 
    submitVariables['YQP_MESSAGE_DUPLICATE_SHOW'] == 'YES' || 
    submitVariables['YQP_MESSAGE_REQUIRED_SHOW'] == 'YES') {
    submitVariables['YQP_SUBMIT_SHOW'] = 'NO';
}
return submitVariables;