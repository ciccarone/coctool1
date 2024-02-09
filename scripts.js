window.onload = function() {
    var form = document.getElementById('coc_connect');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(form);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/wp-admin/admin-ajax.php', true); // WordPress AJAX endpoint

        // Append the action to the form data
        formData.append('action', 'coc_ajax'); // The action that triggers your PHP function

        xhr.onload = function() {
            if (this.status == 200) {
                console.log(this.responseText);
            } else {
                console.error('Request failed.  Returned status of ' + this.status);
            }
        };

        xhr.send(formData);
    });
};