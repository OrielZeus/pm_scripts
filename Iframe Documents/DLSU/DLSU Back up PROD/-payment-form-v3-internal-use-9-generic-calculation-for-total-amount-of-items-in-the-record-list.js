if(this.transactionTypeCode != undefined){
    var amount= 0;
    var type = this.transactionTypeCode;
    if(type == "LI"){
        if(this.LCA_recordList != undefined){
            for(var i = 0; i < this.LCA_recordList.length; i++){
                amount += parseFloat(this.LCA_recordList[i].amount);
            }
        }
    } else if(type == "PRP"){
        if(this.RFP_recordList != undefined){
            for(var i = 0; i < this.RFP_recordList.length; i++){
                amount += parseFloat(this.RFP_recordList[i].amount);
            }
        }
    } else if(type == "PCR"){
        if(this.PCR_recordList != undefined){
            for(var i = 0; i < this.PCR_recordList.length; i++){
                amount += parseFloat(this.PCR_recordList[i].amount);
            }
        }
    } else if(type == "RE"){
        if(this.REI_recordList != undefined){
            for(var i = 0; i < this.REI_recordList.length; i++){
                amount += parseFloat(this.REI_recordList[i].amount);
            }
        }
    } else if(type == "PLA"){
        if(this.PLA_recordList != undefined){
            for(var i = 0; i < this.PLA_recordList.length; i++){
                amount += parseFloat(this.PLA_recordList[i].amount);
            }
        }
    } else if(type == "STUDREF"){
        if(this.PSR_recordList != undefined){
            for(var i = 0; i < this.PSR_recordList.length; i++){
                amount += parseFloat(this.PSR_recordList[i].amount);
            }
        }
    } else if(type == "OTHERS"){
        if(this.RFO_recordList != undefined){
            for(var i = 0; i < this.RFO_recordList.length; i++){
                amount += parseFloat(this.RFO_recordList[i].amount);
            }
        }
    } else if(type == "HON"){
        if(this.HON_recordList != undefined){
            for(var i = 0; i < this.HON_recordList.length; i++){
                amount += parseFloat(this.HON_recordList[i].amount);
            }
        }
    } else if(type == "BDO1"){
        if(this.BDO1_recordList != undefined){
            for(var i = 0; i < this.BDO1_recordList.length; i++){
                amount += parseFloat(this.BDO1_recordList[i].amount);
            }
        }
    } else if(type == "BDO2"){
        if(this.BDO2_recordList != undefined){
            for(var i = 0; i < this.BDO2_recordList.length; i++){
                amount += parseFloat(this.BDO2_recordList[i].amount);
            }
        }
    } else if(type == "BDO3"){
        if(this.BDO3_recordList != undefined){
            for(var i = 0; i < this.BDO3_recordList.length; i++){
                amount += parseFloat(this.BDO3_recordList[i].amount);
            }
        }
    } else if(type == "BDODOL"){
        if(this.BDODOL_recordList != undefined){
            for(var i = 0; i < this.BDODOL_recordList.length; i++){
                amount += parseFloat(this.BDODOL_recordList[i].amount);
            }
        }
    } else if(type == "STI"){
        if(this.STI_recordList != undefined){
            for(var i = 0; i < this.STI_recordList.length; i++){
                amount += parseFloat(this.STI_recordList[i].amount);
            }
        }
    } else if(type == "INS"){
        if(this.INS_recordList != undefined){
            for(var i = 0; i < this.INS_recordList.length; i++){
                amount += parseFloat(this.INS_recordList[i].amount);
            }
        }
    } else if(type == "CON"){
        if(this.CON_recordList != undefined){
            for(var i = 0; i < this.CON_recordList.length; i++){
                amount += parseFloat(this.CON_recordList[i].amount);
            }
        }
    } else if(type == "BANK"){
        if(this.BANK_recordList != undefined){
            for(var i = 0; i < this.BANK_recordList.length; i++){
                amount += parseFloat(this.BANK_recordList[i].amount);
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
    }
} else {
    amount = 0;
}

var total = amount.toFixed(2);

return total;