// Dynamic variables with sample fallback values
const sumInsuredVessel = this.YQP_SUM_INSURED_VESSEL || 0;
const limitPI = this.YQP_LIMIT_PI || 0;
const personalEffectsLimit = this.YQP_PERSONAL_EFFECTS_LIMIT || 0;
const medicalPaymentsLimit = this.YQP_MEDICAL_PAYMENTS_LIMIT || 0;

// Validations to determine which rows to show
const showSumInsured = this.YQP_PRODUCT_HULL_VALIDATE === "YES";
const showLimitPI = this.YQP_PRODUCT_LIMIT_VALIDATE.SHOW === "YES";
const showPersonalEffectsLimit = this.YQP_PERSONAL_EFFECTS_LIMIT_VALIDATION === "YES";
const showMedicalPaymentsLimit = this.YQP_MEDICAL_PAYMENTS_LIMIT_VALIDATION === "YES";

// Number formatter for formatting values as "11,000.00" without the currency symbol
const numberFormatter = new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
    useGrouping: true
});

// Table structure
let tableHTML = "<table id='tableDeductiblesJs' width='70%' border='1'>" ;
    //+
   // "<tr style='background:#D9D9D9;'>" +
   // "<td style='padding: 5px;'><b>Coverage</b></td>" +
   // "<td style='padding: 5px;'><b>Deductible</b></td>" +
    //"</tr>";

// Row for Sum Insured Vessel
if (showSumInsured) {
    tableHTML += "<tr>" +
        "<td width='50%' style='text-align: left; padding: 5px;'>Sum Insured Vessel</td>" +
        `<td width='50%' style='text-align:right; padding: 5px;'>${numberFormatter.format(sumInsuredVessel)}</td>` +
        "</tr>";
}

// Row for Limit P&I
if (showLimitPI) {
    tableHTML += "<tr>" +
        "<td width='50%' style='text-align: left; padding: 5px;'>Limit P&I</td>" +
        `<td width='50%' style='text-align:right; padding: 5px;'>${numberFormatter.format(limitPI)}</td>` +
        "</tr>";
}

// Row for Personal Effects Limit
if (showPersonalEffectsLimit) {
    tableHTML += "<tr>" +
        "<td width='50%' style='text-align: left; padding: 5px;'>Personal Effects Limit</td>" +
        `<td width='50%' style='text-align:right; padding: 5px;'>${numberFormatter.format(personalEffectsLimit)}</td>` +
        "</tr>";
}

// Row for Medical Payments Limit
if (showMedicalPaymentsLimit) {
    tableHTML += "<tr>" +
        "<td width='50%' style='text-align: left; padding: 5px;'>Medical Payments Limit</td>" +
        `<td width='50%' style='text-align:right; padding: 5px;'>${numberFormatter.format(medicalPaymentsLimit)}</td>` +
        "</tr>";
}

// Close table
tableHTML += "</table>";
return tableHTML;