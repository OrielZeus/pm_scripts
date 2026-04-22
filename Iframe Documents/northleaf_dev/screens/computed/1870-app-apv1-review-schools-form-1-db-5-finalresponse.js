let allData  = this.finalResponse;
const dataa  = this.newInstitution;

const actionBtn = this.modalAction;
const copyAction = this.copyAction;


if(actionBtn == 'Cancel' || (actionBtn == null && copyAction == 'Cancel')){
  for(let i = 0; i < allData.length; i++){
    allData[i].listAllSchools = false;
    document.querySelectorAll('button[name="modalAction"]')[0].click();
  }
}
if(actionBtn == 'Save'){
  for(let i = 0; i < allData.length; i++){
    if(allData[i].listAllSchools == true){
      if(dataa != undefined && dataa != null){
        allData[i].newInstitution = dataa;
        allData[i].listAllSchools = false;
        document.querySelectorAll('button[name="modalAction"]')[0].click();
        break;
      }
    }
    /*if(dataa != null)
      allData[i].listAllSchools = false;*/
  }
}


/*if(actionBtn == copyAction){
  //document.querySelectorAll('button[name="modalAction"]')[0].click();
  for(let i = 0; i < allData.length; i++){
     console.log("i : " + allData[i].searchAnother);
  }
}*/






/*
const dataa  = this.newInstitution;
const action = this.modalAction;
console.log(action);
console.log(dataa);
let allData  = this.finalResponse;
let aaa = this.listAllSchools;



if(action != '' && action != undefined && action != null){
  for(let i = 0; i < allData.length; i++){
    console.log(allData[i].listAllSchools);
    if(action != 'Cancel' && allData[i].listAllSchools == true){
      document.querySelectorAll('button[name="modalAction"]')[0].click();
      if(dataa != undefined && dataa != null){
        allData[i].newInstitution = dataa;
        document.querySelectorAll('button[name="modalAction"]')[0].click();
      }
    }
    if(dataa != null)
      allData[i].listAllSchools = false;
  }
}
*/
return allData;