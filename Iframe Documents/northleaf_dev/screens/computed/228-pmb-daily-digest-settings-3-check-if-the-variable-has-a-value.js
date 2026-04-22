// Check if the variable has a value
if (this.DD_HEADER_FONT_COLOR) {
    // Check if the value is a hexadecimal color
    if (/^#[0-9A-F]{6}$/i.test(this.DD_HEADER_FONT_COLOR)) {
        return true;
    } else {
        return false;
    }
} else {
    return true;
}