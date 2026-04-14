/*
 * Calc Validate that only one option is selected
 * by Helen Callisaya
 * modified by Elmer orihuela
 */

const targetDate = new Date();
const list = this.YQP_REQUEST_LIST_TO_CLONE || [];
const len  = list.length;
let output = "";

// 1) If any selected BOUND record covers today — except within 3 months of its expiry — append an info banner
for (let i = 0; i < len; i++) {
    const item = list[i];
    if (
        item.RES_YQP_SELECT_CLONE === true &&
        item.YQP_STATUS === "BOUND" &&
        item.summaryData.YQP_PERIOD_FROM &&
        item.summaryData.YQP_PERIOD_TO
    ) {
        const from = new Date(item.summaryData.YQP_PERIOD_FROM);
        const to   = new Date(item.summaryData.YQP_PERIOD_TO);

        // calculate the date three months before expiry
        const threshold = new Date(to);
        threshold.setMonth(threshold.getMonth() - 3);

        // if today is within the coverage period but more than 3 months before expiry, block
        if (targetDate >= from && targetDate <= to && targetDate < threshold) {
            output += `
<div class="alert alert-info ui-message" role="alert">
  <i class="fa fa-info-circle" aria-hidden="true"></i>
  <strong>BOUND</strong>: The selected item has already been bound this year.
</div>`;
            break; // one banner is enough
        }
    }
}

// 2) Count how many options are selected
let flagSelect = 0;
for (let i = 0; i < len; i++) {
    if (list[i].RES_YQP_SELECT_CLONE === true) {
        flagSelect++;
    }
}

// 3) If more than one is selected, append an error banner
if (flagSelect > 1) {
    output += `
<div class="alert alert-danger ui-message" role="alert">
  <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
  You must select only one option.
</div>`;
}

// 4) Return all accumulated messages (or an empty string if none)
return output;