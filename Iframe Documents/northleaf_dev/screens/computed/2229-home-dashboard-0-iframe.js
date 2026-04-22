/********************
 * Dashboard iframe
 *
 * by Telmo Chiri
 *******************/
const currentUser = document.querySelector("meta[name='user-id']").content;
const data = {
    currentUserId: currentUser,
};
const jsonData = JSON.stringify(data);
//Encode data to make it secure
const encodedData = encodeURIComponent(jsonData);
//Get window height
const screenHeight = $(document).height() - 120;
const code = `<body style="margin:0px;padding:0px;overflow:hidden;width:` + screenHeight + `px">
    <iframe src="/api/1.0/pstools/script/dynamic-home-dashboard-iframe?data=` + encodedData + `" frameborder="0" 
            style="overflow:hidden;overflow-x:hidden;overflow-y:hidden;height:` + screenHeight + `px;width:100%;top:110px;left:0px;right:0px;bottom:0px" 
    ></iframe>
</body>
<div id="my-modal" class="modal fade" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="my-modal-title"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <div class="modal-body">
            <div id="my-modal-body"></div>
        </div>
    </div>
</div>`;
//Open Modal from iframe
window.addEventListener('message', function(event) {
    if (typeof event.data === 'string') {
        let data = JSON.parse(event.data);
        if (data.action === 'OpenModal') {
            // show content
            $('#my-modal-title').html(data.content);
            $('#my-modal-body').html(`<div class="d-flex justify-content-center">
                                        <div class="spinner-grow text-secondary" role="status">
                                            <span class="sr-only">Loading...</span>
                                        </div>
                                    </div>`);
            $('#my-modal').modal('show');
        }
        if (data.action === 'modalContent') {
            // show content
            $('#my-modal-body').html(data.content);
        }
    }
});
return code;