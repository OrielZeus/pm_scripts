/*****
 * Convert date from YYYY-MM-DD to Spanish format (DD de Month de YYYY)
 * By Adriana Centellas
 */

// Safely get the value
let dateStr = this.CQP_INCEPTION_DATE;

// Validate empty or null value
if (!dateStr) {
  return "";
}

// Manually parse the date to avoid timezone issues
let [year, month, day] = dateStr.split("-").map(Number);

// Create a Date object using local time (prevents UTC shifting)
let dateObj = new Date(year, month - 1, day);

// Validate invalid date
if (isNaN(dateObj.getTime())) {
  return "";
}

// Format to Spanish locale
let formattedDate = dateObj.toLocaleDateString('es-ES', {
  year: 'numeric',
  month: 'long',
  day: 'numeric'
});

// Optional: capitalize the month
formattedDate = formattedDate.charAt(0).toUpperCase() + formattedDate.slice(1);

return formattedDate;