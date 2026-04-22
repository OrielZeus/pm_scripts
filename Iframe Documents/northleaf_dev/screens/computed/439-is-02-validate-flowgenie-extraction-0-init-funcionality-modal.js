setTimeout(() => {
    if (typeof($('button:contains("Add New Vendor")')['0']) != "undefined") {
        $('button:contains("Add New Vendor")').on( "click", function() {
            localStorage.setItem("cleanVars", "EMPTY");
            setTimeout(() => {
                $('#buttonCancelPopUp').on("click", function() {
                    $(".modal-header").find("button").trigger("click");
                });
            }, 100);
        });
    } else {
        console.log("NUUUUUUU");
    }
}, 500);