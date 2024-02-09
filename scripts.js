window.onload = function() {
    var form = document.getElementById('coc_connect');
    var loadingGraphic = document.getElementById('coc_loading'); // Assuming you have an element with id 'loading' for the loading graphic

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(form);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/wp-admin/admin-ajax.php', true);

        formData.append('action', 'coc_ajax');

        xhr.onload = function() {
            if (this.status == 200) {
                var response = JSON.parse(this.responseText);
                var message = response.choices[0].message.content;

                // Display the message on the page
                document.getElementById('coc_message').innerText = message; // Assuming you have an element with id 'message' to display the message

                // Hide the loading graphic
                loadingGraphic.style.display = 'none';
            } else {
                console.error('Request failed.  Returned status of ' + this.status);
            }
        };

        // Show the loading graphic
        loadingGraphic.style.display = 'block';

        xhr.send(formData);
    });
};