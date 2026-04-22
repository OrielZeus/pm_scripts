let input = $("[selector='selector-input']").children().children("input[inputmode='numeric']");
console.log(input);
console.log('====>', input.prevObject[1].__vue__.getCurrencyFormat())
console.log('val-->',input.val());
var ret = input.val().replace('AFN','TLX');
console.log('ret-->', ret);
document.querySelector("[selector='selector-input']").value = "My value";
//input.val(ret);
let realValue = this.AMOUNT_TEST;
console.log('realValue->', realValue);