/**
 * 
 * By Favio Mollinedo
 * edited by Jhon Chacolla
 */
// $(document).ready(function() {
//     setTimeout(function() {
//         $("button[aria-label='PDF Invoice']").removeClass("btn-primary");
//         $("button[aria-label='PDF Invoice']").attr("class", "");
//         $("button[aria-label='PDF Invoice']").addClass("button-as-links");
//         $("button[aria-label='Excel Invoice']").removeClass("btn-primary");
//         $("button[aria-label='Excel Invoice']").attr("class", "");
//         $("button[aria-label='Excel Invoice']").addClass("button-as-links");
//     }, 800);
// });

async function checkElementExists(element, timeout = Infinity) {
  let startTime = Date.now();
  return new Promise((resolve) => {
    const intervalId = setInterval(() => {
      if (document.querySelector(element)) {
        clearInterval(intervalId);
        resolve(true);
      } else if (Date.now() - startTime >= timeout * 1000) {
        clearInterval(intervalId);
        resolve(false);
      }
    }, 100);
  });
}	  

checkElementExists("[aria-label='PDF Invoice']", 30)
.then((result) => {
  if (result) {
    $("button[aria-label='PDF Invoice']").removeClass("btn-primary");
    $("button[aria-label='PDF Invoice']").attr("class", "");
    $("button[aria-label='PDF Invoice']").addClass("button-as-links");
	console.log("The element exists!");
  }
});
checkElementExists("[aria-label='Excel Invoice']", 30)
.then((result) => {
  if (result) {
    $("button[aria-label='Excel Invoice']").removeClass("btn-primary");
    $("button[aria-label='Excel Invoice']").attr("class", "");
    $("button[aria-label='Excel Invoice']").addClass("button-as-links");
  } 
});