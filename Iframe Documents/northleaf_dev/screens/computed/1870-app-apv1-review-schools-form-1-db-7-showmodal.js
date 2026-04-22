let allData      = this.finalResponse;
const actionBtn  = this.modalAction;
const copyAction = this.copyAction;

const result = allData.find(({ listAllSchools }) => listAllSchools === true);
console.log(result);
if(result == undefined){
    return false;
}
else{
    return true;
}