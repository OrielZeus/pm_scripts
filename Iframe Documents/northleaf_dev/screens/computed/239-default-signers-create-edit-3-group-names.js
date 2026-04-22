let groupSelect = this.GROUP_LIST;
let idList = [];
if (typeof groupSelect != 'undefined') {
    groupSelect.forEach((item) => {
        idList.push(item.name);
    });
}
return idList;