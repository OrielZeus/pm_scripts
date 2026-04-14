// Uses showVehicleTab / showTrailerTab / showBobcatTab to know which groups
// the user is actually filling in this case.
//
// Returns:
//   "OK"       => all visible tabs have every row.status answered (no blanks)
//   "PENDING"  => at least one visible tab has a blank status
//   "EMPTY"    => no tabs visible at all (nothing to validate)

// helper: are all rows in this checklist answered?
function checklistComplete(list) {
  if (!Array.isArray(list) || list.length === 0) {
    // if the tab is visible but its array is somehow empty,
    // treat that as NOT complete
    return false;
  }

  for (const row of list) {
    const s = String(row.status || "").trim();
    if (s === "") {
      return false; // found unanswered item
    }
  
  }
  return true; // all rows have a status
}

// determine which tabs are actually active in this inspection
const activeTabs = [];
if (this.showVehicleTab)  activeTabs.push("vehicle");
if (this.showTrailerTab)  activeTabs.push("trailer");
if (this.showBobcatTab)   activeTabs.push("bobcat");

// if no tabs are visible, nothing to validate
if (activeTabs.length === 0) {
  return "EMPTY";
}

// check each visible tab
if (this.showVehicleTab) {
  if (!checklistComplete(this.checklistVehicle)) {
    return "PENDING";
  }
}
if (this.showTrailerTab) {
  if (!checklistComplete(this.checklistTrailer)) {
    return "PENDING";
  }
}
if (this.showBobcatTab) {
  if (!checklistComplete(this.checklistBobcat)) {
    return "PENDING";
  }
}

// if we got here, every visible tab is fully answered
return "OK";