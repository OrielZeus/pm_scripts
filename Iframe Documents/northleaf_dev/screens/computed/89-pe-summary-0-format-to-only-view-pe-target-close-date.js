let date = this.PE_TARGET_CLOSE_DATE;
if (date != null) {
    let date_format = date.split('-');
    return date_format[1] + '/' + date_format[2] + '/' + date_format[0];
}
return '';