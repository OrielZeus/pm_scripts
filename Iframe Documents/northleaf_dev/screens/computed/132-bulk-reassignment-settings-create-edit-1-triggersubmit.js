if (this.actionHelper) {
    $(document).on("click", "button[name='actionHelper']", function(e) {
        e.preventDefault();
        setTimeout(function(){
            $("button[name='actionSubmit']").trigger("click"); 
        }, 1500);
    });
}