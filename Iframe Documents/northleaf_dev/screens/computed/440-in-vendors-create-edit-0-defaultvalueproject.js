let defaultProject = this.EXPENSE_VENDOR_DEFAULT_PROJECT;
if (defaultProject !== null) {
    console.log('defaultProject:', defaultProject);
    return defaultProject;    
} else {
    console.log('this.defaultValueProject:', this.defaultValueProject);
    return this.defaultValueProject;
}
/*
if (this.EXPENSE_VENDOR_DEFAULT_PROJECT && this.defaultValueProject == null) {
    console.log("Start");
    setTimeout(() => {
        console.log("This message appears after 2 seconds.", defaultProject);
        return defaultProject;
        return this.EXPENSE_VENDOR_DEFAULT_PROJECT;
    }, 5000); // 2000 milliseconds = 2 seconds
    console.log("End");
}
console.log('defaultProject:', defaultProject);
return defaultProject;
return this.defaultProject;
//let defaultProject = this.EXPENSE_VENDOR_DEFAULT_PROJECT;
let extraParameters = this.EXTRA_PARAMETERS ?? false;
if (extraParameters) {
    console.log('extraParameters:', extraParameters);
}

*/