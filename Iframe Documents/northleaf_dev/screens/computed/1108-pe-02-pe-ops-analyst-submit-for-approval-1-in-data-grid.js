let btnSubmit = this.SUBMIT_PE;
let btnClose  = this.SUBMIT_02_PE;
let btnSave  = this.SUBMIT_PE_H;

let dataIframe = $('#iframe-psTools').contents().find('#iframe-items').val() ?? '';
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