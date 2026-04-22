var utcMonth = new Date().getUTCMonth()+1;
var utcDate = new Date().getUTCDate();
var utcYear = new Date().getUTCFullYear();
var utcHour = new Date().getUTCHours();
var utcMinute = new Date().getUTCMinutes();
var utcSeconds = new Date().getUTCSeconds();

if(utcDate < 10){
  utcDate = "0" + utcDate;
}

if(utcMonth < 10){
  utcMonth = "0" + utcMonth;
}

if(utcHour < 10){
  utcHour = "0" + utcHour;
}

if(utcMinute < 10){
  utcMinute = "0" + utcMinute;
}

if(utcSeconds < 10){
  utcSeconds = "0" + utcSeconds;
}

return UTCDateTimeStamp = utcYear + "-" + utcMonth + "-" + utcDate + "T" + utcHour + ":" + utcMinute + ":" + utcSeconds + "+0000";