let btnSubmit = this.SUBMIT_CO_FLAG;
let btnClose  = this.SUBMIT_CO;

let dataIframe = $('#iframe-psTools').contents().find('#iframe-items_2').val() ?? '';
if (dataIframe !='') {
    dataIframe = dataIframe;
}
let dataJson   = JSON.parse(dataIframe);
let dataString = JSON.stringify(dataJson);
let dataSerial = btoa(encodeURIComponent(dataString).replace(/%([0-9A-F]{2})/g,
        function toSolidBytes(match, p1) {
          return String.fromCharCode('0x' + p1);
        }));
return dataSerial;