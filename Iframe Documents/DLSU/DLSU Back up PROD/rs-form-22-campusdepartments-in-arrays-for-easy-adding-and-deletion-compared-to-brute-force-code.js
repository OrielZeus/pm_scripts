// campus codes
// Manila = MNL
// Laguna = LAG
// BGC = BGC
// Makati = MKT
// Sample Department number to use for checking: MNL-001
// just copy-paste this on the checking for level 1 approver, level 2 approver, etc.
/* var campusesAndDepartments = [
{
	"campusName": "Manila",
	"campusCode": "MNL",
	"departments": [
    	{
        	"departmentNo": "001",
        	"departmentName": "OFFICE OF THE PRESIDENT - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "002",
        	"departmentName": "OFFICE OF THE FORMER PRESIDENTS - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "003",
        	"departmentName": "ADVANCEMENT AND ALUMNI RELATIONS OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "004",
        	"departmentName": "STRATEGIC COMMUNICATIONS OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "005",
        	"departmentName": "RISK MANAGEMENT, COMPLIANCE, AND AUDIT OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "006",
        	"departmentName": "STRATEGIC MANAGEMENT AND QUALITY ASSURANCE OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "007",
        	"departmentName": "OFFICE OF THE LEGAL COUNSEL - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "008",
        	"departmentName": "THE MUSEUM - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "009",
        	"departmentName": "OFFICE OF THE CHIEF FINANCE OFFICER - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "010",
        	"departmentName": "FINANCIAL PLANNING AND ANALYSIS - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "011",
        	"departmentName": "TREASURY - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "012",
        	"departmentName": "OFFICE OF THE UNIVERSITY CONTROLLER - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "013",
        	"departmentName": "OFFICE OF THE VICE PRESIDENT FOR LASALLIAN MISSION - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "014",
        	"departmentName": "LASALLIAN PASTORAL OFFICE (LSPO) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "015",
        	"departmentName": "CENTER FOR SOCIAL CONCERN AND ACTION (COSCA) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "016",
        	"departmentName": "OFFICE OF THE DEAN OF STUDENT AFFAIRS - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "017",
        	"departmentName": "OFFICE OF THE ASSOCIATE DEAN OF STUDENT AFFAIRS - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "018",
        	"departmentName": "SPORTS DEVELOPMENT OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "019",
        	"departmentName": "STUDENT DISCIPLINE FORMATION OFFICE (SDFO) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "020",
        	"departmentName": "CULTURE AND ARTS OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "021",
        	"departmentName": "STUDENT LEADERSHIP INVOLVEMENT, FORMATION AND EMPOWERMENT OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "022",
        	"departmentName": "COUNSELING AND CAREER SERVICES OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "023",
        	"departmentName": "STUDENT MEDIA OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "024",
        	"departmentName": "NSTP AND FORMATION OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "025",
        	"departmentName": "OFFICE OF THE VICE PRESIDENT FOR EXTERNAL RELATIONS AND INTERNATIONALIZATION - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "026",
        	"departmentName": "OFFICE OF THE CHANCELLOR - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "027",
        	"departmentName": "INFORMATION TECHNOLOGY SERVICES OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "028",
        	"departmentName": "DATA PRIVACY OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "029",
        	"departmentName": "COMMUNITY, CULTURE AND HUMAN RESOURCE OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "030",
        	"departmentName": "CAMPUS SUSTAINABILITY OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "031",
        	"departmentName": "OFFICE OF THE SOCIETY OF FELLOWS - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "032",
        	"departmentName": "OFFICE OF THE ASSOCIATE VICE CHANCELLOR FOR ACADEMIC SERVICES - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "033",
        	"departmentName": "OFFICE OF UNIVERSITY REGISTRAR - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "034",
        	"departmentName": "INSTITUTIONAL TESTING AND EVALUATION OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "035",
        	"departmentName": "ADMISSIONS AND SCHOLARSHIPS OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "036",
        	"departmentName": "LIBRARIES - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "037",
        	"departmentName": "ENROLLMENT SERVICES HUB - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "038",
        	"departmentName": "OFFICE OF THE ASSOCIATE VICE CHANCELLOR FOR CAMPUS DEVELOPMENT - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "039",
        	"departmentName": "CAMPUS PLANNING OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "040",
        	"departmentName": "PROJECT PLANNING OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "041",
        	"departmentName": "OFFICE OF THE VICE CHANCELLOR FOR ACADEMICS - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "042",
        	"departmentName": "ACADEMIC SUPPORT FOR INSTRUCTIONAL SERVICES AND TECHNOLOGY - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "043",
        	"departmentName": "LASALLIAN CORE CURRICULUM OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "044",
        	"departmentName": "OFFICE OF THE DEAN (COLLEGE OF COMPUTER STUDIES) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "045",
        	"departmentName": "OFFICE OF THE ASSOCIATE DEAN (COLLEGE OF COMPUTER STUDIES) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "046",
        	"departmentName": "DEPARTMENT OF INFORMATION TECHNOLOGY - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "047",
        	"departmentName": "DEPARTMENT OF COMPUTER TECHNOLOGY - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "048",
        	"departmentName": "DEPARTMENT OF SOFTWARE TECHNOLOGY - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "049",
        	"departmentName": "CONSULTING & EDUCATION CENTER (CEC) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "050",
        	"departmentName": "OFFICE OF THE DEAN (BR. ANDREW GONZALEZ FSC COLLEGE OF EDUCATION) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "051",
        	"departmentName": "OFFICE OF THE ASSOCIATE DEAN (BR. ANDREW GONZALEZ FSC COLLEGE OF EDUCATION) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "052",
        	"departmentName": "DEPARTMENT OF COUNSELING AND EDUCATIONAL PSYCHOLOGY - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "053",
        	"departmentName": "DEPARTMENT OF ENGLISH & APPLIED LINGUISTICS (DEAL) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "054",
        	"departmentName": "DEPARTMENT OF EDUCATIONAL LEADERSHIP AND MANAGEMENT - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "055",
        	"departmentName": "DEPARTMENT OF PHYSICAL EDUCATION - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "056",
        	"departmentName": "DEPARTMENT OF SCIENCE EDUCATION - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "057",
        	"departmentName": "CENTER FOR LANGUAGE AND LIFELONG LEARNING (CELL) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "058",
        	"departmentName": "ST. LA SALLE PRESCHOOL (BAGCED) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "059",
        	"departmentName": "OFFICE OF THE DEAN (COLLEGE OF LIBERAL ARTS) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "060",
        	"departmentName": "OFFICE OF THE ASSOCIATE DEAN (COLLEGE OF LIBERAL ARTS) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "061",
        	"departmentName": "DEPARTMENT OF BEHAVIORAL SCIENCES - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "062",
        	"departmentName": "DEPARTMENT OF COMMUNICATION - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "063",
        	"departmentName": "DEPARTMENT OF FILIPINO - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "064",
        	"departmentName": "DEPARTMENT OF HISTORY - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "065",
        	"departmentName": "DEPARTMENT OF INTERNATIONAL STUDIES - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "066",
        	"departmentName": "DEPARTMENT OF LITERATURE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "067",
        	"departmentName": "DEPARTMENT OF PHILOSOPHY - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "068",
        	"departmentName": "DEPARTMENT OF POLITICAL SCIENCE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "069",
        	"departmentName": "DEPARTMENT OF PSYCHOLOGY - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "070",
        	"departmentName": "DEPARTMENT OF THEOLOGY AND RELIGIOUS EDUCATION - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "071",
        	"departmentName": "OFFICE OF THE DEAN (RAMON V. DEL ROSARIO COLLEGE OF BUSINESS) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "072",
        	"departmentName": "OFFICE OF THE ASSOCIATE DEAN (RAMON V. DEL ROSARIO COLLEGE OF BUSINESS) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "073",
        	"departmentName": "OFFICE OF THE ASSOCIATE DEAN FOR RESEARCH AND GRADUATE STUDIES (RVRCOB) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "074",
        	"departmentName": "DEPARTMENT OF ACCOUNTANCY - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "075",
        	"departmentName": "DEPARTMENT OF COMMERCIAL LAW - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "076",
        	"departmentName": "DEPARTMENT OF DECISION SCIENCES & INNOVATION - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "077",
        	"departmentName": "DEPARTMENT OF FINANCIAL MANAGEMENT - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "078",
        	"departmentName": "DEPARTMENT OF MARKETING AND ADVERTISING - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "079",
        	"departmentName": "DEPARTMENT OF MANAGEMENT AND ORGANIZATION - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "080",
        	"departmentName": "CENTER FOR PROFESSIONAL DEVELOPMENT IN BUSINESS - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "081",
        	"departmentName": "OFFICE OF THE DEAN (GOKONGWEI COLLEGE OF ENGINEERING) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "082",
        	"departmentName": "OFFICE OF THE ASSOCIATE DEAN (GOKONGWEI COLLEGE OF ENGINEERING) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "083",
        	"departmentName": "DEPARTMENT OF CHEMICAL ENGINEERING - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "084",
        	"departmentName": "DEPARTMENT OF CIVIL ENGINEERING - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "085",
        	"departmentName": "DEPARTMENT OF ELECTRONICS AND COMMUNICATIONS ENGINEERING - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "086",
        	"departmentName": "DEPARTMENT OF INDUSTRIAL ENGINEERING - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "087",
        	"departmentName": "DEPARTMENT OF MANUFACTURING ENGINEERING AND MANAGEMENT - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "088",
        	"departmentName": "DEPARTMENT OF MECHANICAL ENGINEERING - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "089",
        	"departmentName": "OFFICE OF THE DEAN (COLLEGE OF SCIENCE) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "090",
        	"departmentName": "OFFICE OF THE DEAN (COLLEGE OF SCIENCE) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "091",
        	"departmentName": "DEPARTMENT OF BIOLOGY - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "092",
        	"departmentName": "DEPARTMENT OF CHEMISTRY - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "093",
        	"departmentName": "DEPARTMENT OF MATHEMATICS AND STATISTICS - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "094",
        	"departmentName": "DEPARTMENT OF PHYSICS - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "095",
        	"departmentName": "OFFICE OF THE DEAN (SCHOOL OF ECONOMICS) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "096",
        	"departmentName": "OFFICE OF THE ASSOCIATE DEAN (SCHOOL OF ECONOMICS) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "097",
        	"departmentName": "DEPARTMENT OF ECONOMICS - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "098",
        	"departmentName": "OFFICE OF THE VICE CHANCELLOR FOR ADMINISTRATION - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "099",
        	"departmentName": "UNIVERSITY SAFETY OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "100",
        	"departmentName": "SECURITY OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "101",
        	"departmentName": "SUPPLY CHAIN MANAGEMENT OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "102",
        	"departmentName": "OFFICE OF THE ASSOCIATE VICE CHANCELLOR FOR FACILITIES MANAGEMENT - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "103",
        	"departmentName": "CIVIL AND SANITARY WORKS OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "104",
        	"departmentName": "BUILDING AND GROUNDS MAINTENANCE OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "105",
        	"departmentName": "MECHANICAL & ELECTRICAL WORKS OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "106",
        	"departmentName": "OFFICE OF THE ASSOCIATE VICE CHANCELLOR FOR CAMPUS SERVICES - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "107",
        	"departmentName": "SUPPORT SERVICES OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "108",
        	"departmentName": "HEALTH SERVICES OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "109",
        	"departmentName": "OFFICE OF THE VICE CHANCELLOR FOR RESEARCH AND INNOVATION - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "110",
        	"departmentName": "UNIVERSITY RESEARCH COORDINATION OFFICE (URCO) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "111",
        	"departmentName": "DLSU INTELLECTUAL PROPERTY OFFICE (DIPO) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "112",
        	"departmentName": "DE LA SALLE UNIVERSITY PUBLISHING HOUSE (DLSU PH) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "113",
        	"departmentName": "DLSU INNOVATION AND TECHNOLOGY OFFICE (DITO) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "114",
        	"departmentName": "UNIVERSITY RESEARCH ETHICS OFFICE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "115",
        	"departmentName": "ADVANCED RESEARCH INSTITUTE FOR INFORMATICS, COMPUTING, AND NETWORKING (ADRIC) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "116",
        	"departmentName": "ANGELO KING INSTITUTE (AKI) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "117",
        	"departmentName": "BR. ALFRED SHIELDS FSC OCEAN RESEARCH CENTER (SHORE) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "118",
        	"departmentName": "BIENVENIDO N. SANTOS CREATIVE WRITING CENTER - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "119",
        	"departmentName": "CENTER FOR BUSINESS RESEARCH AND DEVELOPMENT (CBRD) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "120",
        	"departmentName": "CENTER FOR NATURAL SCIENCES AND ENVIRONMENTAL RESEARCH (CENSER) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "121",
        	"departmentName": "CENTER FOR ENGINEERING AND SUSTAINABLE DEVELOPMENT RESEARCH (CESDR) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "122",
        	"departmentName": "JESSE M. ROBREDO INSTITUTE OF GOVERNANCE (JRIG) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "123",
        	"departmentName": "LASALLIAN INSTITUTE FOR DEVELOPMENT AND EDUCATIONAL RESEARCH (LIDER) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "124",
        	"departmentName": "SOCIAL DEVELOPMENT RESEARCH CENTER (SDRC) - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "125",
        	"departmentName": "FOOD AND WATER INSTITUTE - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "126",
        	"departmentName": "SOUTHEAST ASIA RESEARCH CENTER AND HUB - MANILA CAMPUS"
    	},
		{
        	"departmentNo": "127",
        	"departmentName": "INSTITUTE FOR BIOMEDICAL ENGINEERING AND HEALTH TECHNOLOGIES (IBEHT) - MANILA CAMPUS"
    	},
	]
},
{
	"campusName": "Laguna",
	"campusCode": "LAG",
	"departments": [
    	{
        	"departmentNo": "001",
        	"departmentName": "OFFICE OF THE PRESIDENT - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "002",
        	"departmentName": "ADVANCEMENT AND ALUMNI RELATIONS OFFICE - LAGUNA CAMPUS"
    	},
    	{
			"departmentNo": "003",
        	"departmentName": "STRATEGIC COMMUNICATIONS OFFICE - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "004",
        	"departmentName": "RISK MANAGEMENT, COMPLIANCE, AND AUDIT OFFICE - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "005",
        	"departmentName": "OFFICE OF THE CHIEF FINANCE OFFICER - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "006",
        	"departmentName": "OFFICE OF THE UNIVERSITY CONTROLLER - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "007",
        	"departmentName": "LASALLIAN MISSION OFFICE - LAGUNA CAMPUS - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "008",
        	"departmentName": "SPORTS DEVELOPMENT OFFICE - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "009",
        	"departmentName": "STUDENT DISCIPLINE FORMATION OFFICE (SDFO) - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "010",
        	"departmentName": "COUNSELING AND CAREER SERVICES OFFICE - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "011",
        	"departmentName": "STUDENT AFFAIRS OFFICE - LAGUNA CAMPUS - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "012",
        	"departmentName": "INFORMATION TECHNOLOGY SERVICES OFFICE - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "013",
        	"departmentName": "COMMUNITY, CULTURE AND HUMAN RESOURCE OFFICE - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "014",
        	"departmentName": "INSTITUTIONAL TESTING AND EVALUATION OFFICE - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "015",
        	"departmentName": "LIBRARIES - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "016",
        	"departmentName": "ENROLLMENT SERVICES HUB - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "017",
        	"departmentName": "OFFICE OF THE DEAN (COLLEGE OF COMPUTER STUDIES) - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "018",
        	"departmentName": "DEPARTMENT OF INFORMATION TECHNOLOGY - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "019",
        	"departmentName": "DEPARTMENT OF COMPUTER TECHNOLOGY - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "020",
        	"departmentName": "DEPARTMENT OF SOFTWARE TECHNOLOGY - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "021",
        	"departmentName": "OFFICE OF THE DEAN (BR. ANDREW GONZALEZ FSC COLLEGE OF EDUCATION) - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "022",
        	"departmentName": "DEPARTMENT OF COUNSELING AND EDUCATIONAL PSYCHOLOGY - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "023",
        	"departmentName": "DEPARTMENT OF ENGLISH & APPLIED LINGUISTICS (DEAL) - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "024",
        	"departmentName": "DEPARTMENT OF EDUCATIONAL LEADERSHIP AND MANAGEMENT - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "025",
        	"departmentName": "DEPARTMENT OF PHYSICAL EDUCATION - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "026",
        	"departmentName": "DEPARTMENT OF SCIENCE EDUCATION - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "027",
        	"departmentName": "OFFICE OF THE DEAN (COLLEGE OF LIBERAL ARTS) - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "028",
        	"departmentName": "DEPARTMENT OF BEHAVIORAL SCIENCES - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "029",
        	"departmentName": "DEPARTMENT OF COMMUNICATION - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "030",
        	"departmentName": "DEPARTMENT OF FILIPINO - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "031",
        	"departmentName": "DEPARTMENT OF HISTORY - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "032",
        	"departmentName": "DEPARTMENT OF INTERNATIONAL STUDIES - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "033",
        	"departmentName": "DEPARTMENT OF LITERATURE - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "034",
        	"departmentName": "DEPARTMENT OF PHILOSOPHY - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "035",
        	"departmentName": "DEPARTMENT OF POLITICAL SCIENCE - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "036",
        	"departmentName": "DEPARTMENT OF PSYCHOLOGY - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "037",
        	"departmentName": "DEPARTMENT OF THEOLOGY AND RELIGIOUS EDUCATION - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "038",
        	"departmentName": "OFFICE OF THE DEAN (RAMON V. DEL ROSARIO COLLEGE OF BUSINESS) - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "039",
        	"departmentName": "DEPARTMENT OF ACCOUNTANCY - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "040",
        	"departmentName": "DEPARTMENT OF COMMERCIAL LAW - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "041",
        	"departmentName": "DEPARTMENT OF DECISION SCIENCES & INNOVATION - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "042",
        	"departmentName": "DEPARTMENT OF FINANCIAL MANAGEMENT - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "043",
        	"departmentName": "DEPARTMENT OF MARKETING AND ADVERTISING - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "044",
        	"departmentName": "DEPARTMENT OF MANAGEMENT AND ORGANIZATION - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "045",
        	"departmentName": "OFFICE OF THE DEAN (GOKONGWEI COLLEGE OF ENGINEERING) - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "046",
        	"departmentName": "DEPARTMENT OF CHEMICAL ENGINEERING - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "047",
        	"departmentName": "DEPARTMENT OF CIVIL ENGINEERING - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "048",
        	"departmentName": "DEPARTMENT OF ELECTRONICS AND COMMUNICATIONS ENGINEERING - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "049",
        	"departmentName": "DEPARTMENT OF INDUSTRIAL ENGINEERING - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "050",
        	"departmentName": "DEPARTMENT OF MANUFACTURING ENGINEERING AND MANAGEMENT - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "051",
        	"departmentName": "DEPARTMENT OF MECHANICAL ENGINEERING - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "052",
        	"departmentName": "OFFICE OF THE DEAN (COLLEGE OF SCIENCE) - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "053",
        	"departmentName": "DEPARTMENT OF BIOLOGY - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "054",
        	"departmentName": "DEPARTMENT OF CHEMISTRY - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "055",
        	"departmentName": "DEPARTMENT OF MATHEMATICS AND STATISTICS - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "056",
        	"departmentName": "DEPARTMENT OF PHYSICS - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "057",
        	"departmentName": "OFFICE OF THE DEAN (SCHOOL OF ECONOMICS) - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "058",
        	"departmentName": "DEPARTMENT OF ECONOMICS - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "059",
        	"departmentName": "SUPPLY CHAIN MANAGEMENT OFFICE - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "060",
        	"departmentName": "OFFICE OF THE VICE CHANCELLOR FOR LAGUNA CAMPUS - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "061",
        	"departmentName": "ACADEMIC SERVICES FOR INTEGRATED SCHOOL OFFICE - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "062",
        	"departmentName": "CAMPUS SERVICES FOR LAGUNA CAMPUS - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "063",
        	"departmentName": "FACILITIES MANAGEMENT OFFICE FOR LAGUNA CAMPUS - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "064",
        	"departmentName": "OFFICE OF THE ACADEMICS DIRECTOR - LAGUNA CAMPUS - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "065",
        	"departmentName": "INTEGRATED SCHOOL (IS) OFFICE OF THE PRINCIPAL - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "066",
        	"departmentName": "INTEGRATED SCHOOL (IS) OFFICE OF THE ASSOCIATE PRINCIPAL FOR STUDENT AFFAIRS - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "067",
        	"departmentName": "INTEGRATED SCHOOL (IS) OFFICE OF THE ASSOCIATE PRINCIPAL FOR GRADE SCHOOL - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "068",
        	"departmentName": "INTEGRATED SCHOOL (IS) OFFICE OF THE ASSOCIATE PRINCIPAL FOR JUNIOR HIGH SCHOOL - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "069",
        	"departmentName": "INTEGRATED SCHOOL (IS) OFFICE OF THE ASSOCIATE PRINCIPAL FOR SENIOR HIGH SCHOOL - LAGUNA CAMPUS"
    	},
		{
			"departmentNo": "070",
        	"departmentName": "SENIOR HIGH SCHOOL OFFICE - TAFT - LAGUNA CAMPUS"
    	},
	]
},
{
	"campusName": "BGC",
	"campusCode": "BGC",
	"departments": [
    	{
        	"departmentNo": "001",
        	"departmentName": "OFFICE OF THE DEAN (COLLEGE OF LAW) - BGC CAMPUS"
    	},
		{
			"departmentNo": "002",
        	"departmentName": "OFFICE OF THE ASSOCIATE DEAN (COLLEGE OF LAW) - BGC CAMPUS"
    	},
    	{
			"departmentNo": "003",
        	"departmentName": "CLINICAL LEGAL EDUCATION - BGC CAMPUS"
    	},
		{
			"departmentNo": "004",
        	"departmentName": "PROFESSIONAL DEVELOPMENT CENTER - BGC CAMPUS"
    	},
		{
			"departmentNo": "005",
        	"departmentName": "DEPARTMENT OF CIVIL LAW - BGC CAMPUS"
    	},
		{
			"departmentNo": "006",
        	"departmentName": "DEPARTMENT OF CRIMINAL LAW - BGC CAMPUS"
    	},
		{
			"departmentNo": "007",
        	"departmentName": "DEPARTMENT OF LABOR LAW - BGC CAMPUS"
    	},
		{
			"departmentNo": "008",
        	"departmentName": "DEPARTMENT OF MERCANTILE LAW - BGC CAMPUS"
    	},
		{
			"departmentNo": "009",
        	"departmentName": "DEPARTMENT OF POLITICAL LAW - BGC CAMPUS"
    	},
		{
			"departmentNo": "010",
        	"departmentName": "DEPARTMENT OF REMEDIAL LAW - BGC CAMPUS"
    	},
		{
			"departmentNo": "011",
        	"departmentName": "DEPARTMENT OF SUBSTANTIVE LAW - BGC CAMPUS"
    	},
		{
			"departmentNo": "012",
        	"departmentName": "DEPARTMENT OF TAXATION LAW - BGC CAMPUS"
    	},
		{
			"departmentNo": "013",
        	"departmentName": "DEVELOPMENTAL LEGAL ADVOCACY CLINIC - BGC CAMPUS"
    	},
		{
			"departmentNo": "014",
        	"departmentName": "LEGAL AID CLINIC - BGC CAMPUS"
    	},
		{
			"departmentNo": "015",
        	"departmentName": "OFFICE OF THE VICE CHANCELLOR FOR ADMINISTRATION - BGC CAMPUS"
    	},
	]
},
{
	"campusName": "Makati",
	"campusCode": "MAK",
	"departments": [
    	{
        	"departmentNo": "001",
        	"departmentName": "OFFICE OF THE VICE CHANCELLOR FOR ADMINISTRATION - MAKATI CAMPUS"
    	}
	]
}
];


var selectedCampus = "Manila";
var selectedDepartment = "OFFICE OF THE PRESIDENT - MANILA CAMPUS";
var deptCode = ""; //this is where you build the department code


for(var ctr = 0; ctr < campusesAndDepartments.length; ctr++){
	//match campus first
	if(campusesAndDepartments[ctr].campusName == selectedCampus){
    	deptCode = deptCode + campusesAndDepartments[ctr].campusCode + "-";
   	 
    	//this will traverse the departments array
    	for(ctr2 = 0; ctr2 < campusesAndDepartments.departments.length; ctr2++){
        	var currentDepartment = campusesAndDepartments.departments[ctr2]; //current department being checked
       	 
        	//now match department
        	if (currentDepartment.departmentName == selectedDepartment){
            	//now add department number
            	deptCode = deptCode + currentDepartment.departmentNo;
        	}
    	}
	}
}

//after getting department code assign to person


*/