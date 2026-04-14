//Define object variable
let variableClone = {
    requestId : "",
    processId : ""
};
//Set Value
variableClone.requestId = this._request.id;
variableClone.processId = this._request.process_id;
//Return Value
return variableClone;