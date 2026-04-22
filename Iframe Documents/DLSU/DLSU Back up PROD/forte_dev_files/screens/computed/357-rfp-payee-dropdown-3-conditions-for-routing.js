var choice = ""; // conditions == "1"

if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-02-03-016"){ // Ramon Trajano ~~ Non-Academic
	choice = "1";  
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-02-03-016"){
	choice = "2";
	
}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "1-02-03-016"){ // Ramon Trajano
	choice = "3";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "1-02-03-016"){
	choice = "4";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-02-34-311"){ // Ma Inores Palmes
	choice = "5";  
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-02-34-311"){
	choice = "6";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "1-02-34-311"){  // Ma Inores Palmes
	choice = "7";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "1-02-34-311"){
	choice = "8";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-02-03-017"){ // Ramon Trajano - Finance and Planning Analysis
	choice = "9";  
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-02-03-017"){
	choice = "10";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "3-12-33-167"){  // ARISTOTLE UBANDO - ADRAS - MC Campus
	choice = "11";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "3-12-33-167"){
	choice = "12";
// }else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-02-03-018"){  // Ramon Trajano - Treasury - MC Campus
// 	choice = "11";
// }else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-02-03-018"){
// 	choice = "12";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-13-24-336"){  // Cynthia Abangan
	choice = "13";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-13-24-336"){  
	choice = "14";
	
}else if (this.campusChoiceCode == "MKC" && this.amountRequested > 80000 && this.userDept == "2-13-21-179"){ // Gleanson Vines
	choice = "15";
}else if (this.campusChoiceCode == "MKC" && this.amountRequested <= 80000 && this.userDept == "2-13-21-179"){
	choice = "16";	
	
}else if (this.campusChoiceCode == "BC" && this.amountRequested > 80000 && this.userDept == "2-13-21-180"){ // Gleanson Vines
	choice = "17";
}else if (this.campusChoiceCode == "BC" && this.amountRequested <= 80000 && this.userDept == "2-13-21-180"){
	choice = "18";	

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-13-21-176"){ // Arnel Uy
	choice = "19";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-13-21-176"){
	choice = "20";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-13-22-191"){ // Antonio Maralit
	choice = "21";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-13-22-191"){
	choice = "22";	

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-13-22-192"){ // Rolando Oliva
	choice = "23";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-13-22-192"){
	choice = "24";	

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-13-22-193"){ // Mylene Grecia
	choice = "25";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-13-22-193"){
	choice = "26";	

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-13-22-194"){ // Nardley Jose
	choice = "27";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-13-22-194"){
	choice = "28";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-13-23-201"){ // Karen Hebron
	choice = "29";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-13-23-201"){
	choice = "30";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-13-23-204"){ // Katherene Arboleda
	choice = "31";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-13-23-204"){
	choice = "32";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-13-23-205"){ // Laarni Roque
	choice = "33";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-13-23-205"){
	choice = "34";	
	
}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-26-256"){ // Mary Abegail Pineda
	choice = "35";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-26-256"){
	choice = "36";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-14-33-211"){ // Dr. Raymond Tan ~~ Research
	choice = "37";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-211"){
	choice = "38";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-14-33-212"){ // Feorillo Demeterio
	choice = "39";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-212"){
	choice = "40";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-14-33-221"){ // Reynaldo Bautista
	choice = "41";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-221"){
	choice = "42";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-14-33-220"){ // Ronald Baytan
	choice = "43";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-220"){
	choice = "44";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-14-33-224"){ // Ador Torneo
	choice = "45";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-224"){
	choice = "46";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-14-33-226"){ // Melvin Jabar
	choice = "47";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-226"){
	choice = "48";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-14-33-217"){ // Judith Azcarraga -- ADRIC
	choice = "49";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-217"){
	choice = "50";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-14-33-219"){ // Wilfredo Licuanan
	choice = "51";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-219"){
	choice = "52";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-14-33-218"){ // Tereso Tullao
	choice = "53";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-218"){
	choice = "54";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-14-33-227"){ // Emmanuel Garcia
	choice = "55";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-227"){
	choice = "56";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "3-14-45-280"){ // Divina Amalin 
	choice = "57";
	// 2-14-33-222 CENSER
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "3-14-45-280"){
	choice = "58";	
	
}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-14-33-225"){ // Shirley Dita
	choice = "59";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-225"){
	choice = "60";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-14-33-216"){ // Nelson Arboleda
	choice = "61";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-216"){
	choice = "62";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-14-33-214"){ // David Bayot
	choice = "63";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-214"){
	choice = "64";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-14-33-213"){ // Christopher Cruz
	choice = "65";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-213"){
	choice = "66";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-14-33-215"){ // Christopher Cruz
	choice = "67";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-215"){
	choice = "68";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-14-33-230"){ // Fernando Santiago
	choice = "69";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-230"){
	choice = "70";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-14-33-231"){ // Nilo Bugtai ~~ Research
	choice = "71";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-231"){
	choice = "72";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-11-07-056"){ // Kai Fernandez 
	choice = "73";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-11-07-056"){
	choice = "74";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-11-07-057"){ // Nelson Marcos
	choice = "75";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-11-07-057"){
	choice = "76";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-11-07-058"){ // Carmelita Chua
	choice = "77";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-11-07-058"){
	choice = "78";	
	
}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-26-248"){ // Carmelita Chua
	choice = "79";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-26-248"){
	choice = "80";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-11-07-059"){ // Susan Gordola --> Grich Prado
	choice = "81";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-11-07-059"){
	choice = "82";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-11-22-075"){ // Christine Abrigo
	choice = "83";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-11-22-075"){
	choice = "84";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-11-07-061"){ // Ann Ancheta
	choice = "85";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-11-07-061"){
	choice = "86";	
	
}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-11-07-062"){ // Christine Abrigo
	choice = "87";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-11-07-062"){
	choice = "88";	
	
}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-11-07-061"){ // Ma Vina V Margallo
	choice = "89";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-11-07-061"){
	choice = "90";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-04-02-041"){ // Laurene Chua-Garcia 
	choice = "91";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-04-02-041"){
	choice = "92";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-01-01-005"){ // Edwin Reyes 
	choice = "93";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-01-01-005"){
	choice = "94";
	
}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "1-01-01-005"){ // Edwin Reyes 
	choice = "95";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "1-01-01-005"){
	choice = "96";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-01-01-007"){ // Antonio Servando 
	choice = "97";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-01-01-007"){
	choice = "98";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-01-01-006"){ // Johannes Badillo 
	choice = "99";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-01-01-006"){
	choice = "100";
	
}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "1-01-01-006"){ // Anne Alina 
	choice = "101";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "1-01-01-006"){
	choice = "102";
	
}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "1-01-01-007"){ // Antonio Servando 
	choice = "103";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "1-01-01-007"){
	choice = "104";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-01-01-010"){ // Rizalina Buncab
	choice = "105";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-01-01-010"){
	choice = "106";
	
}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-26-246"){ // Gil Santos ~~ Feb 22
	choice = "107";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-26-246"){
	choice = "108";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-11-38-326"){ // Allan Borra 
	choice = "109";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-11-38-326"){
	choice = "110";
	
}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-11-38-326"){ // Allan Borra 
	choice = "111";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-11-38-326"){
	choice = "112";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-11-06-050"){ // Joseph rosal 
	choice = "113";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-11-06-050"){
	choice = "114";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-01-01-008"){ // Gerardo Largoza 
	choice = "115";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-01-01-008"){
	choice = "116";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-11-37-321"){ // Joanne Mar 
	choice = "117";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-11-37-321"){
	choice = "118";
	
}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-11-37-321"){ // Joanne Mar 
	choice = "119";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-11-37-321"){
	choice = "120";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-11-08-066"){ // Maria Emilia Sevilla
	choice = "121";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-11-08-066"){
	choice = "122";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-11-06-051"){ // Josemari Calleja 
	choice = "123";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-11-06-051"){
	choice = "124";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-11-08-067"){ // Josemari Calleja 
	choice = "125";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-11-08-067"){
	choice = "126";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-11-08-068"){ // Josemari Calleja ~~ Non Academic
	choice = "127";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-11-08-068"){
	choice = "128";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-09-071"){ // Robert Roleda 
	choice = "129";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-09-071"){
	choice = "130";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-09-072"){ // Jasper Alontaga 
	choice = "131";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-09-072"){
	choice = "132";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-13-121"){ // Emilina Sarreal 
	choice = "133";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-13-121"){
	choice = "134";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-13-132"){ // Cynthia Cudia 
	choice = "135";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-13-132"){
	choice = "136";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-13-122"){ // Luz Suplico // changed to Liberty Patiu
	choice = "137";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-13-122"){
	choice = "138";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-13-123"){ // Joy Legaspi 
	choice = "139";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-13-123"){
	choice = "140";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-13-124"){ // James Heffron 
	choice = "141";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-13-124"){
	choice = "142";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-13-125"){ // Ruth Cruz 
	choice = "143";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-13-125"){
	choice = "144";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-13-126"){ // Liberty Patiu 
	choice = "145";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-13-126"){
	choice = "146";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-13-127"){ // Mary Julie Balarbar 
	choice = "147";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-13-127"){
	choice = "148";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-13-128"){ // Maria Paquita Diongon-Bonnet 
	choice = "149";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-13-128"){
	choice = "150";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-13-130"){ // Rodiel Ferrer 
	choice = "151";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-13-130"){
	choice = "152";
	
}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-28-285"){ // Emilina Sarreal 
	choice = "153";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-28-285"){
	choice = "154";
	
}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-13-123"){ // Joy Legaspi 
	choice = "155";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-13-123"){
	choice = "156";
	
}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-13-124"){ // James Heffron 
	choice = "157";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-13-124"){
	choice = "158";
	
}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-13-125"){ // Ruth Cruz 
	choice = "159";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-13-125"){
	choice = "160";
	
}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-13-126"){ // Liberty Patiu 
	choice = "161";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-13-126"){
	choice = "162";
	
}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-13-127"){ // Mary Julie Balarbar 
	choice = "163";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-13-127"){
	choice = "164";
	
}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-13-128"){ // Maria Paquita Diongon-Bonnet ~~ Academic 
	choice = "165";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-13-128"){
	choice = "166";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-12-101"){ // Rhoderick Nuncio ~~ Academic  // Feb 23 
	choice = "167";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-101"){
	choice = "168";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-12-117"){ // Ron Resurreccion 
	choice = "169";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-117"){
	choice = "170";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-12-102"){ // Myla Arcinas --> Mary Janet Arnado
	choice = "171";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-102"){
	choice = "172";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-12-103"){ // Maria Angeli Diaz
	choice = "173";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-103"){
	choice = "174";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-12-105"){ // Raquel Sison-Buban
	choice = "175";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-105"){
	choice = "176";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-12-106"){ // Ma Florina Orillos-Juan
	choice = "177";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-106"){
	choice = "178";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-12-107"){ // Elaine Tolentino
	choice = "179";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-107"){
	choice = "180";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-12-108"){ // Genevieve Asenjo
	choice = "181";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-108"){
	choice = "182";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-12-109"){ // Robert Boyles
	choice = "183";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-109"){
	choice = "184";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-12-110"){ // Sherwin Ona
	choice = "185";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-110"){
	choice = "186";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-12-111"){ // Rene Nob
	choice = "187";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-111"){
	choice = "188";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-12-115"){ // Fides Del Castillo
	choice = "189";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-115"){
	choice = "190";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-28-284"){ // Rhoderick Nuncio
	choice = "191";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-28-284"){
	choice = "192";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-12-102"){ // Myla Arcinas
	choice = "193";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-102"){
	choice = "194";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-12-103"){ // Maria Angeli Diaz
	choice = "195";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-103"){
	choice = "196";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-12-105"){ // Raquel Sison-Buban
	choice = "197";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-105"){
	choice = "198";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-12-106"){ // Ma Florina Orillos-Juan
	choice = "199";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-106"){
	choice = "200";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-12-107"){ // Elaine Tolentino
	choice = "201";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-107"){
	choice = "202";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-12-108"){ // Genevieve Asenjo
	choice = "203";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-108"){
	choice = "204";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-12-109"){ // Robert Boyles
	choice = "205";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-109"){
	choice = "206";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-12-110"){ // Sherwin Ona
	choice = "207";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-110"){
	choice = "208";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-12-111"){ // Rene Nob
	choice = "209";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-111"){
	choice = "210";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-12-115"){ // Fides Del Castillo
	choice = "211";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-115"){
	choice = "212";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-29-297"){ // Steve Dalumpines
	choice = "213";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-29-297"){
	choice = "214";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-26-258"){ // Roy Navea
	choice = "215";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-26-258"){
	choice = "216";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-27-261"){ // Raymund Endriga
	choice = "217";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-27-261"){
	choice = "218";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-27-262"){ // Maria Theresa Patricio
	choice = "219";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-27-262"){
	choice = "220";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-27-274"){ // Anna Quitco
	choice = "221";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-27-274"){
	choice = "222";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-27-275"){ // Imelda Onquit --> Perlita Padua
	choice = "223";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-27-275"){
	choice = "224";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-27-276"){ // Rembrandt Santos --> Engelbert Talunton
	choice = "225";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-27-276"){
	choice = "226";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-27-266"){ // Rembrandt Santos Taft
	choice = "227";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-27-266"){
	choice = "228";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-27-270"){ // Rembrandt Santos LC
	choice = "229";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-27-270"){
	choice = "230";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-14-33-223"){ // Alvin Culaba
	choice = "231";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-223"){
	choice = "232";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-14-33-225"){ // Shirley Dita
	choice = "233";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-225"){
	choice = "234";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-13-21-178"){ // Jose Billy Aguirre
	choice = "235";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-13-21-178"){
	choice = "236";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-01-01-009"){ // Christopher Cruz
	choice = "237";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-01-01-009"){
	choice = "238";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-17-171"){ // Marites Tiongco // Feb 24
	choice = "239";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-17-171"){
	choice = "240";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-17-172"){ // Mitzie Conchada
	choice = "241";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-17-172"){
	choice = "242";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-17-173"){ // Arlene Inocencio
	choice = "243";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-17-173"){
	choice = "244";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-28-288"){ // Marites Tiongco
	choice = "245";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-28-288"){
	choice = "246";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-17-173"){ // Arlene Inocencio
	choice = "247";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-17-173"){
	choice = "248";

}else if (this.campusChoiceCode == "BC" && this.amountRequested > 80000 && this.userDept == "2-12-15-151"){ // Virgilio Delos Reyes
	choice = "249";
}else if (this.campusChoiceCode == "BC" && this.amountRequested <= 80000 && this.userDept == "2-12-15-151"){
	choice = "250";

}else if (this.campusChoiceCode == "BC" && this.amountRequested > 80000 && this.userDept == "2-12-15-154"){ // Virgilio Delos Reyes
	choice = "251";
}else if (this.campusChoiceCode == "BC" && this.amountRequested <= 80000 && this.userDept == "2-12-15-154"){
	choice = "252";

}else if (this.campusChoiceCode == "BC" && this.amountRequested > 80000 && this.userDept == "2-12-15-346"){ // Virgilio Delos Reyes
	choice = "253";
}else if (this.campusChoiceCode == "BC" && this.amountRequested <= 80000 && this.userDept == "2-12-15-346"){
	choice = "254";

}else if (this.campusChoiceCode == "BC" && this.amountRequested > 80000 && this.userDept == "2-12-15-347"){ // Virgilio Delos Reyes
	choice = "255";
}else if (this.campusChoiceCode == "BC" && this.amountRequested <= 80000 && this.userDept == "2-12-15-347"){
	choice = "256";

}else if (this.campusChoiceCode == "BC" && this.amountRequested > 80000 && this.userDept == "2-12-15-350"){ // Virgilio Delos Reyes
	choice = "257";
}else if (this.campusChoiceCode == "BC" && this.amountRequested <= 80000 && this.userDept == "2-12-15-350"){
	choice = "258";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-10-076"){ // Dr Rafael Cabredo
	choice = "259";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-10-076"){
	choice = "260";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-10-077"){ // Estefanie Bertumen
	choice = "261";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-10-077"){
	choice = "262";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-10-078"){ // Jocelynn Cu 
	choice = "263";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-10-078"){
	choice = "264";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-10-079"){ // Ryan Dimaunahan 
	choice = "265";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-10-079"){
	choice = "266";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-10-076"){ // Rafael Cabredo
	choice = "267";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-10-076"){
	choice = "268";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-10-083"){ // Charibeth Cheng
	choice = "269";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-10-083"){
	choice = "270";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-10-077"){ // Estefanie Bertumen
	choice = "271";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-10-077"){
	choice = "272";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-10-078"){ // Jocelynn Cu
	choice = "273";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-10-078"){
	choice = "274";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-10-079"){ // Ryan Dimaunahan
	choice = "275";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-10-079"){
	choice = "276";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-10-082"){ // Jocelyn Sales --> Ma. Rowena Caguiat --> Dr. Rafael Cabredo 
	choice = "277";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-10-082"){
	choice = "278";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-16-156"){ // Glenn Alea
	choice = "279";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-16-156"){
	choice = "280";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-16-167"){ // Glenn Alea
	choice = "281";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-16-167"){
	choice = "282";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-16-157"){ // Mary Jane Flores
	choice = "283";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-16-157"){
	choice = "284";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-16-159"){ // Jaime Janario
	choice = "285";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-16-159"){
	choice = "286";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-16-160"){ // Jose Tristan Reyes
	choice = "287";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-16-160"){
	choice = "288";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-16-161"){ // Maria Carla Manzano
	choice = "289";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-16-161"){
	choice = "290";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-28-287"){ // Glenn Alea
	choice = "291";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-28-287"){
	choice = "292";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-16-157"){ // Mary Jane Flores
	choice = "293";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-16-157"){
	choice = "294";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-16-159"){ // Jaime Janario
	choice = "295";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-16-159"){
	choice = "296";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-16-160"){ // Jose Tristan Reyes
	choice = "297";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-16-160"){
	choice = "298";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-16-161"){ // Maria Carla Manzano
	choice = "299";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-16-161"){
	choice = "300";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-14-136"){ // Jonathan Dungca
	choice = "301";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-14-136"){
	choice = "302";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-14-144"){ // Jonathan Dungca
	choice = "303";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-14-144"){
	choice = "304";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-14-138"){ // Mary Ann Adajar
	choice = "305";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-14-138"){
	choice = "306";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-14-139"){ // Argel Bandala
	choice = "307";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-14-139"){
	choice = "308";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-14-140"){ // Willy Zalatar
	choice = "309";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-14-140"){
	choice = "310";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-14-141"){ // Elmer Dadios //Ryan Vicerra
	choice = "311";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-14-141"){
	choice = "312";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-14-142"){ // Alvin Chua
	choice = "313";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-14-142"){
	choice = "314";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-28-286"){ // Jonathan Dungca
	choice = "315";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-28-286"){
	choice = "316";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-14-138"){ // Mary Ann Adajar
	choice = "317";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-14-138"){
	choice = "318";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-14-139"){ // Argel Bandala
	choice = "319";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-14-139"){
	choice = "320";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-14-140"){ // Willy Zalatar
	choice = "321";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-14-140"){
	choice = "322";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-14-141"){ // Elmer Dadios // Ryan Vicerra 
	choice = "323";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-14-141"){
	choice = "324";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-14-142"){ // Alvin Chua
	choice = "325";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-14-142"){
	choice = "326";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-03-04-021"){ // Fritzie De Vera  Feb 26
	choice = "327";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-03-04-021"){
	choice = "328";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-03-04-022"){ // James Laxa
	choice = "329";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-03-04-022"){
	choice = "330";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-03-04-023"){ // Neil Penullar
	choice = "331";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-03-04-023"){
	choice = "332";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "1-03-04-024"){ // Margarita Perdido
	choice = "333";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "1-03-04-024"){
	choice = "334";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-26-250"){ // Alexander Depante --> Emmanuel Calanog
	choice = "335";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-26-250"){
	choice = "336";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-26-251"){ // Leandro Loyola --> Leonardo Villena
	choice = "337";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-26-251"){
	choice = "338";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "22-15-26-252"){ // Elaine Aranda
	choice = "339";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-26-252"){
	choice = "340";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-26-253"){ // Leandro Loyola
	choice = "341";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-26-253"){
	choice = "342";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-03-05-028"){ // Emmanuel Calanog
	choice = "343";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-03-05-028"){
	choice = "344";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-03-05-029"){ // Michael Millanes
	choice = "345";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-03-05-029"){
	choice = "346";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-03-05-032"){ // Elaine Aranda
	choice = "347";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-03-05-032"){
	choice = "348";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "1-03-05-036"){ // Leandro Loyola
	choice = "349";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "1-03-05-036"){
	choice = "350";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-03-05-033"){ // Franz Santos
	choice = "351";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-03-05-033"){
	choice = "352";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-03-05-034"){ // Carl Fernandez
	choice = "353";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-03-05-034"){
	choice = "354";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-03-05-031"){ // Christopher Villanueva
	choice = "355";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-03-05-031"){
	choice = "356";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-28-283"){ // Raymund Sison // Feb 26 afternoon
	choice = "357";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-28-283"){
	choice = "358";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-11-088"){ // Estesa Legaspi
	choice = "359";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-11-088"){
	choice = "360";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-11-089"){ // Rochelle Lucas
	choice = "361";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-11-089"){
	choice = "362";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-11-091"){ // Anne Ramos
	choice = "363";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-11-091"){
	choice = "364";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-11-092"){ // Ma Socorro Cordova
	choice = "365";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-11-092"){
	choice = "366";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-11-093"){ // Minie Lapinid
	choice = "367";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-11-093"){
	choice = "368";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-11-086"){ // Raymund Sison
	choice = "369";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-11-086"){
	choice = "370";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-11-087"){ // Aireen Arnuco
	choice = "371";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-11-087"){
	choice = "372";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-11-088"){ // Estesa Legaspi
	choice = "373";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-11-088"){
	choice = "374";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-11-089"){ // Rochelle Lucas
	choice = "375";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-11-089"){
	choice = "376";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-11-091"){ // Anne Ramos
	choice = "377";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-11-091"){
	choice = "378";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-11-092"){ // Ma Socorro Cordova
	choice = "379";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-11-092"){
	choice = "380";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-11-093"){ // Minie Lapinid
	choice = "381";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-11-093"){
	choice = "382";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-11-097"){ // Rosanna Valerio
	choice = "383";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-11-097"){
	choice = "384";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-11-098"){ // Raymund Sison
	choice = "385";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-11-098"){
	choice = "386";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-14-137"){ // Dr Aileen Orbecido // Chem Eng late upload
	choice = "387";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-14-137"){
	choice = "388";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-14-137"){ // Dr Aileen Orbecido // Chem Eng late upload
	choice = "389";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-14-137"){
	choice = "390";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 1000000 && this.userDept == "1-01-01-001"){ // Bro Bernard Oca // Office of the President
	choice = "391";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 1000000 && this.userDept == "1-01-01-001"){
	choice = "392";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 1000000 && this.userDept == "2-15-26-247"){ // Elenita Esteban // Academic Services For Integrated School Office //DEPARTMENTS. SOURCE
	choice = "393";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 1000000 && this.userDept == "2-15-26-247"){
	choice = "394";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-13-129"){ // Mary Julie Balarbar // Advertising Management Department 
	choice = "395";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-13-129"){
	choice = "396";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-13-23-202"){ // Cynthia Abangan // Asset Management Office (AMO)	
	choice = "397";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-13-23-202"){
	choice = "398";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-13-24-337"){ // Cynthia Abangan // Asset Management Office (AMO) 
	choice = "399";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-13-24-337"){
	choice = "400";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-11-094"){ // Raymund Sison // BAGCED EXTERNAL AFFAIRS Office 
	choice = "401";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-11-094"){
	choice = "402";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-11-096"){ // Raymund Sison // BAGCED FOR PERSONAL EFFECTIVENESS (PERSEF)
	choice = "403";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-11-096"){
	choice = "404";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-11-095"){ // Raymund Sison // BAGCED RESEARCH AND ADVANCE STUDIES
	choice = "405";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-11-095"){
	choice = "406";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-16-158"){ // Mary Jane Flores // BIOLOGY LABORATORY
	choice = "407";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-16-158"){
	choice = "408";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-01-01-002"){ // Antonio Servando // BOARD OF TRUSTEES (BOT)
	choice = "409";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-01-01-002"){
	choice = "410";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-10-080"){ // Rafael Cabredo // CCS EXTERNAL AFFAIRS OFFICE
	choice = "411";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-10-080"){
	choice = "412";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-10-081"){ // Rafael Cabredo // CCS RESEARCH ADVANCE STUDIES
	choice = "413";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-10-081"){
	choice = "414";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-14-33-229"){ // Raymond Tan --> Drexel Camacho // CENTRAL INSTRUMENTATION FACILITY
	choice = "415";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-229"){
	choice = "416";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-16-166"){ // Arlene Inocencio // CHEMISTRY LABORATORY
	choice = "417";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-16-166"){
	choice = "418";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-12-116"){ // Rhoderick Nuncio --> Myla Arcinas // CLA EXTERNAL AFFAIRS OFFICE
	choice = "419";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-116"){
	choice = "420";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-15-153"){ // Virgilio Delos Reyes // CLINICAL LEGAL EDUCATION
	choice = "421";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-15-153"){
	choice = "422";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-12-113"){ // Estesa Legaspi // COGNITIVE PSYCHOLOGY LABORATORY
	choice = "423";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-113"){
	choice = "424";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-16-164"){ // Mary Jane Flores // COS EXTERNAL AFFAIRS OFFICE
	choice = "425";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-16-164"){
	choice = "426";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-16-165"){ // Mary Jane Flores // COS RESEARCH ADVANCE STUDIES
	choice = "427";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-16-165"){
	choice = "428";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-03-05-030"){ // Glorife Samodio // CULTURAL AND ARTS OFFICE
	choice = "429";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-03-05-030"){
	choice = "430";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-09-073"){ // Macario Cordel II // DATA SCIENCE INSTITUTE (DSI)
	choice = "431";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-09-073"){
	choice = "432";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-15-341"){ // Virgilio Delos Reyes // DEPARTMENT OF CIVIL LAW
	choice = "433";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-15-341"){
	choice = "434";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-12-104"){ // Maria Angeli Diaz // DEPARTMENT OF COMMUNICATIONS LABORATORY
	choice = "435";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-104"){
	choice = "436";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-15-342"){ // Virgilio Delos Reyes // DEPARTMENT OF CRIMINAL LAW
	choice = "437";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-15-342"){
	choice = "438";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-15-343"){ // Virgilio Delos Reyes // DEPARTMENT OF LABOR LAW
	choice = "439";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-15-343"){
	choice = "440";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-15-344"){ // Virgilio Delos Reyes // DEPARTMENT OF MERCANTILE LAW
	choice = "441";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-15-344"){
	choice = "442";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-15-345"){ // Virgilio Delos Reyes // DEPARTMENT OF POLITICAL LAW
	choice = "443";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-15-345"){
	choice = "444";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-15-348"){ // Virgilio Delos Reyes // DEPARTMENT OF TAXATION LAW
	choice = "445";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-15-348"){
	choice = "446";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-15-349"){ // Virgilio Delos Reyes // DEVELOPMENTAL LEGAL ADVOCACY CLINIC
	choice = "447";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-15-349"){
	choice = "448";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-14-143"){ // Jonathan Dungca // ENGINEERING AND TECHNICAL LABORATORY
	choice = "449";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-14-143"){
	choice = "450";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-11-090"){ // Raymund Sison // ENGLISH LANGUAGE LABORATORY
	choice = "451";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-11-090"){
	choice = "452";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-09-074"){ // Rodiel Ferrer --> Jose Bienvenido Manuel Biona // ENRIQUE RAZON LOGISTICS INSTITUTE (ERLI)
	choice = "453";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-09-074"){
	choice = "454";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-12-112"){ // Estesa Legaspi // GRAUDATE PSYCHOLOGY Department
	choice = "455";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-112"){
	choice = "456";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-15-27-264"){ // Raymund Endriga // GUIDANCE OFFICE - IS
	choice = "457";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-15-27-264"){
	choice = "458";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-27-272"){ // Rembrandt Santos // GUIDANCE OFFICE - SHS LC
	choice = "459";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-27-272"){
	choice = "460";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-15-27-268"){ // Rembrandt Santos // GUIDANCE OFFICE - SHS Taft
	choice = "461";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-15-27-268"){
	choice = "462";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-29-301"){ // Mary Abegail Pineda // HEALTH SERVICES UNIT - LC CAMPUS
	choice = "463";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-29-301"){
	choice = "464";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-14-33-228"){ // Maria Carla Manzano // High Performance Computing LABORATORY
	choice = "465";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-228"){
	choice = "466";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-15-29-302"){ // Cynthia Abangan // Inventory Management OFFICE (IMO) - LC CAMPUS
	choice = "467";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-15-29-302"){
	choice = "468";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-09-075"){ // Voltaire Mistades // Lasallian Core Curriculum Office 
	choice = "469";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-09-075"){
	choice = "470";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-15-29-298"){ // Cynthia Abangan // Logistics Office - LC Office
	choice = "471";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-15-29-298"){
	choice = "472";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-12-114"){ // Rene Nob // Mental Health Service Center - Psychology Department
	choice = "473";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-114"){
	choice = "474";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-16-163"){ // Divina Amalin // Molecular Science Laboratory
	choice = "475";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-16-163"){
	choice = "476";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-26-255"){ // Elenita Esteban // Office of Academic Services for IS - SHS LC
	choice = "477";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-26-255"){
	choice = "478";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-15-26-254"){ // Elenita Esteban // Office of Academic Services for IS - SHS Taft
	choice = "479";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-15-26-254"){
	choice = "480";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-03-05-027"){ // Cyril Lituanas // Office of The Associate Dean of Student Affairs
	choice = "481";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-03-05-027"){
	choice = "482";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-11-06-046"){ // Bernard Oca // Office of The Chancellor
	choice = "483";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-11-06-046"){
	choice = "484";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-03-05-026"){ // Christine Ballada // Office of The Dean of Student Affairs
	choice = "485";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-03-05-026"){
	choice = "486";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-01-01-003"){ // Armin Luistro // Office of The Former Presidents
	choice = "487";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-01-01-003"){
	choice = "488";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-11-06-053"){ // Raymundo Suplido // Office of The Society of Fellows
	choice = "489";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-11-06-053"){
	choice = "490";

}else if ((this.campusChoiceCode == "LC" || this.campusChoiceCode == "MC") && this.amountRequested > 80000 && this.userDept == "2-13-21-182"){ // Gleanson Vines // Office Satellite Campus Facilities Administration - Charles Huang
	choice = "491";
}else if ((this.campusChoiceCode == "LC" || this.campusChoiceCode == "MC") && this.amountRequested <= 80000 && this.userDept == "2-13-21-182"){
	choice = "492";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-13-21-181"){ // Gleanson Vines // Office Satellite Campus Facilities Administration - Lian
	choice = "493";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-13-21-181"){
	choice = "494";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-16-162"){ // Jose Tristan Reyes // Physics Laboratory
	choice = "495";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-16-162"){
	choice = "496";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-13-23-203"){ // Cynthia Abangan // Procurement Office 
	choice = "497";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-13-23-203"){
	choice = "498";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-13-24-338"){ // Cynthia Abangan // Procurement Office 
	choice = "499";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-13-24-338"){
	choice = "500";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-29-300"){ // Cynthia Abangan // Procurement Office - LC Campus
	choice = "501";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-29-300"){
	choice = "502";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-15-155"){ // Virgilio Delos Reyes // Professional Development Center
	choice = "503";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-15-155"){
	choice = "504";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-13-131"){ // Emilina Sarreal // RVRCOB External Affairs Office
	choice = "505";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-13-131"){
	choice = "506";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-29-299"){ // Jose Billy Aguirre // Safety and Security Office - LC Campus
	choice = "507";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-29-299"){
	choice = "508";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-03-05-035"){ // Christopher Villanueva // Student Council
	choice = "509";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-03-05-035"){
	choice = "510";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-15-27-263"){ // Elenita Esteban // Student Welfare and Discipline Office - IS
	choice = "511";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-15-27-263"){
	choice = "512";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-27-271"){ // Rembrandt Santos // Student Welfare and Discipline Office - SHS LC
	choice = "513";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-27-271"){
	choice = "514";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-15-27-267"){ // Rembrandt Santos // Student Welfare and Discipline Office - SHS Taft
	choice = "515";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-15-27-267"){
	choice = "516";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-13-21-177"){ // Ronald Dabu // University Safety Office
	choice = "517";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-13-21-177"){
	choice = "518";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-26-257"){ // Steve Dalumpines // Facilities Management Office for LC Campus
	choice = "519";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-26-257"){
	choice = "520";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-02-34-312"){ // STUDENT ACCOUNTS AND SERVICES OFFICE - Elvie Tang 
	choice = "521";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-02-34-312"){
	choice = "522";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "1-02-34-312"){ // STUDENT ACCOUNTS AND SERVICES OFFICE - Elvie Tang 
	choice = "523";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "1-02-34-312"){
	choice = "524";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-11-07-060"){ // Christine Abrigo
	choice = "525";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-11-07-060"){
	choice = "526";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-11-06-048"){ // Danny Cheng - Data Privacy Office 
	choice = "527";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-11-06-048"){
	choice = "528";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-02-34-314"){ // Keanu Dominado FOR TESTING ONLY ~~ Cashiering
	choice = "529";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-02-34-314"){
	choice = "530";	
	
}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-14-33-231"){ // Nilo Bugtai ~~ Research
	choice = "531";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-231"){
	choice = "532";	

}else if (this.campusChoiceCode == "BGC" && this.amountRequested > 1000000 && this.userDept == "1-01-01-001"){ // Bro Bernard Oca // Office of the President
	choice = "533";
}else if (this.campusChoiceCode == "BGC" && this.amountRequested <= 1000000 && this.userDept == "1-01-01-001"){
	choice = "534";
	
}else if (this.campusChoiceCode == "MKT" && this.amountRequested > 1000000 && this.userDept == "1-01-01-001"){ // Bro Bernard Oca // Office of the President
	choice = "535";
}else if (this.campusChoiceCode == "MKT" && this.amountRequested <= 1000000 && this.userDept == "1-01-01-001"){
	choice = "536";
	
}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-13-22-192"){ // Rolando Oliva
	choice = "537";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-13-22-192"){
	choice = "538";	

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 1000000 && this.userDept == "1-01-01-001"){ // Bro Bernard Oca // Office of the President
	choice = "539";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 1000000 && this.userDept == "1-01-01-001"){
	choice = "540";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-14-33-229"){ // Dr Drexel Camacho // Central Instrumentation Facility
	choice = "541";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-229"){
	choice = "542";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-11-06-052"){ // Marco Rey Macatangay // Project Management Office
	choice = "543";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-11-06-052"){
	choice = "544";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-14-33-211"){ // Dr. Raymond Tan // Vice Chancellor for Research and Innovation
	choice = "545";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-211"){
	choice = "546";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-15-27-276"){ // Engelbert Talunton
	choice = "547";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-15-27-276"){
	choice = "548";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-14-33-235"){ // LIDER - DOST-PCIEERD
	choice = "549";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-235"){
	choice = "550";
	
}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-14-33-235"){ // LIDER - DOST-PCIEERD
	choice = "551";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-235"){
	choice = "552";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "5-12-13-130"){ // School of Lifelong Learning - Rodiel Ferrer
	choice = "553";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "5-12-13-130"){
	choice = "554";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "5-12-13-131"){ // Lasallian Social Enterprise for Economic Development
	choice = "555";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "5-12-13-131"){
	choice = "556";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "5-12-13-132"){ // Lasallian Center for Inclusion, Diversity, and Wellbeing
	choice = "557";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "5-12-13-132"){
	choice = "558";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "5-99-13-132"){ // Robert Roleda -- OFFICE OF THE PROVOST
	choice = "559";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "5-99-13-132"){
	choice = "560";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-13-24-336"){  // Cynthia Abangan
	choice = "561";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-13-24-336"){  
	choice = "562";
	
}else if (this.campusChoiceCode == "MKY" && this.amountRequested > 80000 && this.userDept == "2-99-21-180"){  // Gleanson Vines
	choice = "563";
}else if (this.campusChoiceCode == "MKY" && this.amountRequested <= 80000 && this.userDept == "2-99-21-180"){  
	choice = "564";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-99-21-181"){  // Joel Ilao
	choice = "565";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-99-21-181"){  
	choice = "566";
}
else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-11-06-050"){ // Maria Elemos 
	choice = "567";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-11-06-050"){
	choice = "568";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "6-14-33-212"){ // Ma Inores Palmes 
	choice = "569";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "6-14-33-212"){
	choice = "570";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-02-34-315"){ // Christie Villavicencio 
	choice = "571";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-02-34-315"){
	choice = "572";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-02-34-313"){ // Lapurisima Paras
	choice = "573";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-02-34-313"){
	choice = "574";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "4-16-52-067"){ // OFFICE OF THE SENIOR VICE PRESIDENT FOR FINANCE AND ADMIN -- Dr. Rufo Mendoza
	choice = "575";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "4-16-52-067"){
	choice = "576";
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "4-02-06-021"){ // OFFICE OF THE VICE PRESIDENT FOR FINANCE -- Dr. Rufo Mendoza
	choice = "577";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "4-02-06-021"){
	choice = "578";
}
else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-13-121-PHINMA"){ // OFFICE OF THE VICE PRESIDENT FOR FINANCE -- Dr. Rufo Mendoza
	choice = "579";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-13-121-PHINMA"){
	choice = "580";	

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-27-265"){ // PATRICIA TRIVINO -- OASIS LAGUNA
	choice = "581";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-27-265"){
	choice = "582";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-15-27-265"){ // PATRICIA TRIVINO -- OASIS LAGUNA
	choice = "583";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-15-27-265"){
	choice = "584";
}	

//2-12-10-076-TheAcademy // Dr. Ethel Ong	
else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-10-076-TheAcademy"){ 
	choice = "585";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-10-076-TheAcademy"){
	choice = "586";	
}

else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-14-33-222"){ // Angelyn Lao
	choice = "587";
	// 2-14-33-222 CENSER
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-222"){
	choice = "588";	
	
}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-14-33-222"){ // Angelyn Lao
	choice = "589";
	// 2-14-33-222 CENSER
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-222"){
	choice = "590";	

}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "5-13-21-182"){ // Office Satellite Campus Facilities Administration - MANILA CAMPUS 
	choice = "591";
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "5-13-21-182"){ // Dr. Glen Vines
	choice = "592";

}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "4-02-06-021"){ // OFFICE OF THE VICE PRESIDENT FOR FINANCE -- Mr. Jirk Jansen Miranda
	choice = "593";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "4-02-06-021"){
	choice = "594";

	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-12-12-117-SCC"){ // Ron Resurreccion 
	choice = "169";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-12-12-117-SCC"){
	choice = "170";
}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-15-27-264"){ // Josiecar Miro-Riglos // GUIDANCE OFFICE - IS
	choice = "457";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-15-27-264"){
	choice = "458";

}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-14-33-215-IMPACT NXT"){ // PETER IMMANUEL TENEDO
	choice = "596";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-14-33-215-IMPACT NXT"){
	choice = "597";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "1-01-01-006-QAO"){ // Dr. Rosemary R. Seva
	choice = "598";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "1-01-01-006-QAO"){
	choice = "599";	
	
}else if (this.campusChoiceCode == "MC" && this.amountRequested > 80000 && this.userDept == "2-11-06-050-USO"){ // Mr. Antonio Maralit
	choice = "600";
}else if (this.campusChoiceCode == "MC" && this.amountRequested <= 80000 && this.userDept == "2-11-06-050-USO"){
	choice = "601";	
	
}else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "1-01-01-008"){ // Mr. Roy Monarch Sy
	choice = "602";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "1-01-01-008"){
	choice = "603";
}
//2-12-10-076-TheAcademy // Dr. Ethel Ong	
else if (this.campusChoiceCode == "LC" && this.amountRequested > 80000 && this.userDept == "2-12-10-076-TheAcademy"){ 
	choice = "604";
}else if (this.campusChoiceCode == "LC" && this.amountRequested <= 80000 && this.userDept == "2-12-10-076-TheAcademy"){
	choice = "605";	
}
	return choice;