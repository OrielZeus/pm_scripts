var glAccount = "";

var campusWcode = this.campusWcode;
var deptWcode = this.deptWcode;
var programWcode = this.programWcode;
var project = this.project.PROJ_COD;
var typeWcode = this.project.FUT_COD;
var fundWcode = this.project.FUC_COD;
var natWcode = this.natWcode;
var glWcode = this.glWcode;

if((campusWcode != undefined) && (deptWcode != undefined) && (programWcode != undefined) && (project != undefined) && (typeWcode != undefined) && (fundWcode != undefined) && (natWcode != undefined) && (glWcode != undefined)){
    glAccount = campusWcode + "-" + deptWcode + "-" + programWcode + "-" + project + "-" + typeWcode + "-" + fundWcode + "-" + natWcode + "-" + glWcode + "-00-0000-0000";     
}

return glAccount;
// Sample is: 01-0-00-00-000-00000-00000000-1-11-00-511001005-00-0000-0000
// {{campusWcode}}-{{deptWcode}}-{{programWcode}}-{{project}}-{{typeWcode}}-{{fundWcode}}-{{natWcode}}-{{glWcode}}-00-0000-0000