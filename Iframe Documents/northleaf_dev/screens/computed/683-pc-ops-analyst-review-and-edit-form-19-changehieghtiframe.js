let statusScrren = this.readyScreen;
//if(statusScrren != null){
    setTimeout(() => {
        let sizemax = document.getElementById('iframe-psTools').contentWindow.document.body.scrollHeight;
        sizemax = sizemax + 30;
        document.getElementById("iframe-psTools").style.height = sizemax+'px';
    }, "500");
//}