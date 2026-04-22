if(this.transactionTypeCode != undefined){
    var amount= 0;
    var type = this.transactionTypeCode;
    if(type == "LI"){
        if(this.LCA_recordList != undefined){
            for(var i = 0; i < this.LCA_recordList.length; i++){
                amount += parseFloat(this.LCA_recordList[i].amount);
            }
        }
    } else if(type == "PCR"){
        if(this.PCR_recordList != undefined){
            for(var i = 0; i < this.PCR_recordList.length; i++){
                amount += parseFloat(this.PCR_recordList[i].amount);
            }
        }
    } else if(type == "OTHERS"){
        if(this.RFO_recordList != undefined){
            for(var i = 0; i < this.RFO_recordList.length; i++){
                amount += parseFloat(this.RFO_recordList[i].amount);
            }
        }
    } else if(type == "STI"){
        if(this.STI_recordList != undefined){
            for(var i = 0; i < this.STI_recordList.length; i++){
                amount += parseFloat(this.STI_recordList[i].amount);
            }
        }
    } else if(type == "CON"){
        if(this.CON_recordList != undefined){
            for(var i = 0; i < this.CON_recordList.length; i++){
                amount += parseFloat(this.CON_recordList[i].amount);
            }
        }
    } else if(type == "DON"){
        if(this.DON_recordList != undefined){
            for(var i = 0; i < this.DON_recordList.length; i++){
                amount += parseFloat(this.DON_recordList[i].amount);
            }
        }
    } else if(type == "MES"){
        if(this.MES_recordList != undefined){
            for(var i = 0; i < this.MES_recordList.length; i++){
                amount += parseFloat(this.MES_recordList[i].amount);
            }
        }
    } else if(type == "RRI"){
        if(this.RRI_recordList != undefined){
            for(var i = 0; i < this.RRI_recordList.length; i++){
                amount += parseFloat(this.RRI_recordList[i].amount);
            }
        }
    }
} else {
    amount = 0;
}

var total = amount.toFixed(2);

return total;