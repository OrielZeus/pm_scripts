let fxAmount = this.IN_FX_TOTAL_AMOUNT;
let total    = this.IN_TOTAL_TOTAL;
let result   = Number(fxAmount) / Number(total); 
result = isNaN(result) ? 0.0000 : result.toFixed(4);
return result