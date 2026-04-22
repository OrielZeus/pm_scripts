// Construct the new text
var PE_IC_MEETING_DATE = this.PE_IC_MEETING_DATE;
let newText = "The undersigned has reviewed the Investment Committee Presentation dated";

if (PE_IC_MEETING_DATE) {
    let [yyyy, mm, dd] = PE_IC_MEETING_DATE.split('-');
    let formattedDate = `${mm}/${dd}/${yyyy}`;
    newText += ' ' + formattedDate + ' ';
}

newText += "and the approval of the Investment Committee regarding the investment to be made by:";

return newText;