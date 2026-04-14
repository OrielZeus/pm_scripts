/*
 * Calc Validate that only one option is selected
 * by Helen Callisaya
 * modified by Elmer orihuela
 */

// Configurable custom flag for bound-block or “too many” selections
const CUSTOM_FLAG = "AA";

// Use today as the reference date
const targetDate = new Date();

// Safely grab your list and its length
const list = this.YQP_REQUEST_LIST_TO_CLONE || [];
const len  = list.length;

// 1) “Bound” block: if an item is BOUND, selected, and today is more than 3 months before its expiry, immediately return CUSTOM_FLAG
for (let i = 0; i < len; i++) {
  const item = list[i];
  
  if (
    item.RES_YQP_SELECT_CLONE === true &&
    item.YQP_STATUS             === "BOUND" &&
    item.summaryData.YQP_PERIOD_FROM &&
    item.summaryData.YQP_PERIOD_TO
  ) {
    const from      = new Date(item.summaryData.YQP_PERIOD_FROM);
    const to        = new Date(item.summaryData.YQP_PERIOD_TO);
    const threshold = new Date(to);
    threshold.setMonth(to.getMonth() - 3); // 3 months before expiry

    // block only if today is within the coverage period AND BEFORE the 3-month window  
    if (targetDate >= from && targetDate < threshold) {
      return CUSTOM_FLAG;
    }
    // if today is within the last 3 months (>= threshold && <= to), we skip the block
  }
}

// 2) Count how many options are selected
let flagSelect = 0;
for (let i = 0; i < len; i++) {
  if (list[i].RES_YQP_SELECT_CLONE === true) {
    flagSelect++;
  }
}

// 3) Return the appropriate flag
if (flagSelect === 0) {
  return "NO";
}
if (flagSelect === 1) {
  return "YES";
}
if (flagSelect === 2) {
  return "MORE";
}
// 4+) Any other case (e.g. >2), return the custom flag
return CUSTOM_FLAG;