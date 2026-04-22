var string = "";
var choice = this.campusChoice;

if(choice != undefined){
    if(choice == "Manila"){
        string = "11-0-00-00-000-00000-11000101-1-11-00-210101001-00-0000-0000";
    } else if(choice == "Laguna"){
        string = "21-0-00-00-000-00000-11000101-1-11-00-210101001-00-0000-0000";
    } else if(choice == "BGC"){
        string = "31-0-00-00-000-00000-11000101-1-11-00-210101001-00-0000-0000";
    } else if(choice == "Makati"){
        string = "12-0-00-00-000-00000-11000101-1-11-00-210101001-00-0000-0000";  
    }
}

return string;