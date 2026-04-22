var data = this.MIA_APPROVER_USERS_OBJECT;
var concatenatedNames = "";
data.forEach(function (item) {
  concatenatedNames += item.fullname + ", ";
});
concatenatedNames = concatenatedNames.slice(0, -2);
return concatenatedNames;