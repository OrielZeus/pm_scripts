var pmqlVariables = {
    "icApproversGroupId" : 6,
    "groupId" : 6,
    "icApproversGroupName" : "Portfolio Manager",
};
let jsonString = JSON.stringify(pmqlVariables);
jsonString = btoa(jsonString);
return jsonString.replace(/=/g, "|");