var record_list = this.RFO_recordList[0];
var record_list = this.RFO_recordList[0]['rowid'];
var is_visible = "true";
if(record_list != null){
    is_visible = "false";
}
return is_visible;