window.onload = function() {
    var form = document.getElementById('coc_connect');
    var loadingMessage = document.getElementById('coc_loading'); // Assuming you have an element with id 'loading_message' for the loading message

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
                var messageElement = document.getElementById('coc_message');
                var p = document.createElement('p');
                p.innerText = message;
                messageElement.innerHTML = ''; // Clear the existing content
                messageElement.appendChild(p); // Append the new paragraph element
                
                // Refresh the image
                var imageElement = document.querySelector('.coc-result__address__qr img');
                var imageUrl = new URL(imageElement.src);
                imageUrl.searchParams.set('nocache', new Date().getTime());
                imageElement.src = imageUrl.href;
                // Hide the loading message
                loadingMessage.innerText = '';
            } else {
                console.error('Request failed.  Returned status of ' + this.status);
            }
        };

        // Show the loading message
        loadingMessage.innerText = 'Contacting OpenAPI servers..';

        xhr.send(formData);
    });
};

(function( $ ) {

    /**
     * initMap
     *
     * Renders a Google Map onto the selected jQuery element
     *
     * @date    22/10/19
     * @since   5.8.6
     *
     * @param   jQuery $el The jQuery element.
     * @return  object The map instance.
     */
    function initMap( $el ) {
    
        // Find marker elements within map.
        var $markers = $el.find('.marker');
    
        // Create gerenic map.
        var mapArgs = {
            zoom        : $el.data('zoom') || 16,
            mapTypeId   : google.maps.MapTypeId.ROADMAP
        };
        var map = new google.maps.Map( $el[0], mapArgs );
    
        // Add markers.
        map.markers = [];
        $markers.each(function(){
            initMarker( $(this), map );
        });
    
        // Center map based on markers.
        centerMap( map );
    
        // Return map instance.
        return map;
    }
    
    /**
     * initMarker
     *
     * Creates a marker for the given jQuery element and map.
     *
     * @date    22/10/19
     * @since   5.8.6
     *
     * @param   jQuery $el The jQuery element.
     * @param   object The map instance.
     * @return  object The marker instance.
     */
    function initMarker( $marker, map ) {
    
        // Get position from marker.
        var lat = $marker.data('lat');
        var lng = $marker.data('lng');
        var latLng = {
            lat: parseFloat( lat ),
            lng: parseFloat( lng )
        };
    
        // Create marker instance.
        var marker = new google.maps.Marker({
            position : latLng,
            map: map
        });
    
        // Append to reference for later use.
        map.markers.push( marker );
    
        // If marker contains HTML, add it to an infoWindow.
        if( $marker.html() ){
    
            // Create info window.
            var infowindow = new google.maps.InfoWindow({
                content: $marker.html()
            });
    
            // Show info window when marker is clicked.
            google.maps.event.addListener(marker, 'click', function() {
                infowindow.open( map, marker );
            });
        }
    }
    
    /**
     * centerMap
     *
     * Centers the map showing all markers in view.
     *
     * @date    22/10/19
     * @since   5.8.6
     *
     * @param   object The map instance.
     * @return  void
     */
    function centerMap( map ) {
    
        // Create map boundaries from all map markers.
        var bounds = new google.maps.LatLngBounds();
        map.markers.forEach(function( marker ){
            bounds.extend({
                lat: marker.position.lat(),
                lng: marker.position.lng()
            });
        });
    
        // Case: Single marker.
        if( map.markers.length == 1 ){
            map.setCenter( bounds.getCenter() );
    
        // Case: Multiple markers.
        } else{
            map.fitBounds( bounds );
        }
    }
    
    // Render maps on page load.
    $(document).ready(function(){
        $('.acf-map').each(function(){
            var map = initMap( $(this) );
        });
    });
    
    })(jQuery);