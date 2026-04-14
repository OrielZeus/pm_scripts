/****
 * Close right tab panel
 * By Daniel Aguilar
 */

let auxx = this.closetab;
let aux1 = this.copy;
if (auxx == undefined && aux1 == undefined) {
  setTimeout(() => {
    let elem = document.querySelector('.info-main.menu-open');
    if(elem != null){
      document.querySelector('.slide-control a').click()
    }
    return true;
  }, 1000);
}
else
  return false;