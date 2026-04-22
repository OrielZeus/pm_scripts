countAux=0;
if (typeof this.finalResponse != "undefined") {
  CountFinRes2=this.finalResponse.length;
  for(let j = 0; j < CountFinRes2; j++) {
    if(this.finalResponse[j]['deleteSchool']==true){
      countAux++;

    }
  }
}
else CountFinRes2=0;
CountFinRes2=CountFinRes2-countAux;
return CountFinRes2;