var pmqlVariables = {
    "icApproversGroupId" : 20,
    "groupId" : 20,
    "icApproversGroupName" : "IC Approvers",
};
let jsonString = JSON.stringify(pmqlVariables);
jsonString = btoa(jsonString);
return jsonString.replace(/=/g, "|");