const coord = this.COORDINATES_OUTPUT;
const array = coord ? coord.REAL_COORDS.split(",") : "";


return `<iframe 
  width="80%" 
  height="300" 
  frameborder="0" 
  scrolling="no" 
  marginheight="0" 
  marginwidth="0" 
  src="https://maps.google.com/maps?q=${array[0]},${array[1]}&hl=es&z=14&amp;output=embed"
 >
 </iframe>
 `