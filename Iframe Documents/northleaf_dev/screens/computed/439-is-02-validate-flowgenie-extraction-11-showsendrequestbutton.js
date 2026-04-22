let visible = false;
const regex = /^[a-zA-Z0-9\s'.,;:!?()\-#%@\$]+$/;
if (this.IN_REQUEST_NEW_VENDOR_MESSAGE != "" &&
    regex.test(this.IN_REQUEST_NEW_VENDOR_MESSAGE)) {
    visible = true;
}
return visible;