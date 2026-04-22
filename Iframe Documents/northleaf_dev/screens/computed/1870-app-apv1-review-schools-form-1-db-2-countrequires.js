countAux=0;
if (typeof this.finalResponse != "undefined") {
  CountFinRes2=this.finalResponse.length;
  for(let j = 0; j < CountFinRes2; j++) {
    if((this.finalResponse[j]['newInstitution'] == null || this.finalResponse[j]['newInstitution'] == "") && this.finalResponse[j]['InstitutionEqui2']==null && this.finalResponse[j]['InstitutionId']=="" && this.finalResponse[j]['deleteSchool']==false){
        countAux++;

    }
  }
}
return countAux;