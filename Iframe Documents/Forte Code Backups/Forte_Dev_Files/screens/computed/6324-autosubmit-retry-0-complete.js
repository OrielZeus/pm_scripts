/**
 * 
 * by Cristian Ferrufino Sejas
 */
// Create an interval that executes the function every 100 milliseconds
const myIntervalreturn = setInterval(myTimerReturn, 100);

// Function executed on each interval tick
function myTimerReturn() {

    // Set a global flag to indicate the return action is in progress
    window.searchReturn = true;

    // Check if an element with name="complete_wait" exists in the DOM
    if (document.querySelectorAll('[name="RETRY"]').length > 0) {

        // Click the element automatically
        document.querySelector('[name="RETRY"]').click();

        // Stop the interval to prevent repeated clicks
        clearInterval(myIntervalreturn);
    }
}