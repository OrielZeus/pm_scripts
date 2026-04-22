document.title = 'Dashboard';
document.querySelector('meta[name="datetime-format"]').setAttribute("content", "Y/m/d H:i");

var elements = document.querySelectorAll("meta[name=datetime-format]");
elements[0].value= 'Y/m/d H:i';
return true;