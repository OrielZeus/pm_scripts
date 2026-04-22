let userSelect = this.USER_LIST;
let idList = [];
if (typeof userSelect != 'undefined') {
    userSelect.forEach((item) => {
        idList.push(item.fullname);
    });
}
return idList;