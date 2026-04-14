/*
* Calc Age with Year field
* by Ana Castillo
*/
if (this.YQP_YEAR != "" && this.YQP_YEAR != null) {
    var currentYear = new Date().getFullYear();
    var year = this.YQP_YEAR;
    var age = currentYear - year;
    return age;
}