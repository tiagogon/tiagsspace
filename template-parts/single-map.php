<?php
/*
MAP template for single pages
*/

// get location array (coordinates and address)
$location = get_field('location'); ?>

<div id="cd-google-map" class="container-fluid">
    <!-- #google-container will contain the map  -->
    <div id="google-container" ></div>
    <!-- #cd-zoom-in and #zoom-out will be used to create our custom buttons for zooming-in/out -->
    <div id="cd-zoom-in" style="background-image:url('<?php bloginfo('template_url'); ?>/library/img/map/cd-icon-controller.svg');"></div>
    <div id="cd-zoom-out" style="background-image:url('<?php bloginfo('template_url'); ?>/library/img/map/cd-icon-controller.svg');"></div>
    <address><?php //echo $location['address']; ?></address>

</div>


<?php 
// get google maps with private API ?>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBXwsyR9pD0XobnqhdTcT6zneN1rVcc27I"></script>


<?php 
// get google maps with private API ?>

<?php /*<script src="<?php bloginfo('template_url'); ?>/library/js/map/main.js"></script> */?>

<script type="text/javascript">
    
        //set your google maps parameters
        var latitude = <?php echo $location['lat']; ?>,
            longitude = <?php echo $location['lng']; ?>,
            map_zoom = 16; 

        //google map custom marker icon - .png fallback for IE11
        var is_internetExplorer11= navigator.userAgent.toLowerCase().indexOf('trident') > -1;
        var marker_url = ( is_internetExplorer11 ) ? '<?php bloginfo('template_url'); ?>/library/img/map/cd-icon-location.png' : '<?php bloginfo('template_url'); ?>/library/img/map/cd-icon-location.svg';
            
        //define the basic color of your map, plus a value for saturation and brightness
        var main_color = '#42a5f5', // Google Blue-400
            saturation_value= -20,
            brightness_value= 5;

        //we define here the style of the map
        var style= [ 
            {
                //set saturation for the labels on the map
                elementType: "labels",
                stylers: [
                    {saturation: saturation_value}
                ]
            },  
            {   //poi stands for point of interest - don't show these lables on the map 
                featureType: "poi",
                elementType: "labels",
                stylers: [
                    {visibility: "off"}
                ]
            },
            {
                //don't show highways lables on the map
                featureType: 'road.highway',
                elementType: 'labels',
                stylers: [
                    {visibility: "off"}
                ]
            }, 
            {   
                //don't show local road lables on the map
                featureType: "road.local", 
                elementType: "labels.icon", 
                stylers: [
                    {visibility: "off"} 
                ] 
            },
            { 
                //don't show arterial road lables on the map
                featureType: "road.arterial", 
                elementType: "labels.icon", 
                stylers: [
                    {visibility: "off"}
                ] 
            },
            {
                //don't show road lables on the map
                featureType: "road",
                elementType: "geometry.stroke",
                stylers: [
                    {visibility: "off"}
                ]
            }, 
            //style different elements on the map
            { 
                featureType: "transit", 
                elementType: "geometry.fill", 
                stylers: [
                    { hue: main_color },
                    { visibility: "off" }, 
                    { lightness: brightness_value }, 
                    { saturation: saturation_value }
                ]
            }, 
            {
                featureType: "poi",
                elementType: "geometry.fill",
                stylers: [
                    { hue: main_color },
                    { visibility: "on" }, 
                    { lightness: brightness_value }, 
                    { saturation: saturation_value }
                ]
            },
            {
                featureType: "poi.government",
                elementType: "geometry.fill",
                stylers: [
                    { hue: main_color },
                    { visibility: "on" }, 
                    { lightness: brightness_value }, 
                    { saturation: saturation_value }
                ]
            },
            {
                featureType: "poi.sport_complex",
                elementType: "geometry.fill",
                stylers: [
                    { hue: main_color },
                    { visibility: "on" }, 
                    { lightness: brightness_value }, 
                    { saturation: saturation_value }
                ]
            },
            {
                featureType: "poi.attraction",
                elementType: "geometry.fill",
                stylers: [
                    { hue: main_color },
                    { visibility: "on" }, 
                    { lightness: brightness_value }, 
                    { saturation: saturation_value }
                ]
            },
            {
                featureType: "poi.business",
                elementType: "geometry.fill",
                stylers: [
                    { hue: main_color },
                    { visibility: "on" }, 
                    { lightness: brightness_value }, 
                    { saturation: saturation_value }
                ]
            },
            {
                featureType: "transit",
                elementType: "geometry.fill",
                stylers: [
                    { hue: main_color },
                    { visibility: "off" }, 
                    { lightness: brightness_value }, 
                    { saturation: saturation_value }
                ]
            },
            {
                featureType: "transit.station",
                elementType: "geometry.fill",
                stylers: [
                    { hue: main_color },
                    { visibility: "off" }, 
                    { lightness: brightness_value }, 
                    { saturation: saturation_value }
                ]
            },
            {
                featureType: "landscape",
                stylers: [
                    { hue: main_color },
                    { visibility: "on" }, 
                    { lightness: brightness_value }, 
                    { saturation: saturation_value }
                ]
                
            },
            {
                featureType: "road",
                elementType: "geometry.fill",
                stylers: [
                    { hue: main_color },
                    { visibility: "on" }, 
                    { lightness: brightness_value }, 
                    { saturation: saturation_value }
                ]
            },
            {
                featureType: "road.highway",
                elementType: "geometry.fill",
                stylers: [
                    { hue: main_color },
                    { visibility: "on" }, 
                    { lightness: brightness_value }, 
                    { saturation: saturation_value }
                ]
            }, 
            {
                featureType: "water",
                elementType: "geometry",
                stylers: [
                    { hue: main_color },
                    { visibility: "on" }, 
                    { lightness: brightness_value }, 
                    { saturation: saturation_value }
                ]
            }
        ];
            
        //set google map options
        var map_options = {
            center: new google.maps.LatLng(latitude, longitude),
            zoom: map_zoom,
            panControl: false,
            zoomControl: false,
            mapTypeControl: false,
            streetViewControl: false,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            scrollwheel: false,
            styles: style,
        }
        //inizialize the map
        var map = new google.maps.Map(document.getElementById('google-container'), map_options);
        //add a custom marker to the map                
        var marker = new google.maps.Marker({
            position: new google.maps.LatLng(latitude, longitude),
            map: map,
            visible: true,
            icon: marker_url,
        });

        //add custom buttons for the zoom-in/zoom-out on the map
        function CustomZoomControl(controlDiv, map) {
            //grap the zoom elements from the DOM and insert them in the map 
            var controlUIzoomIn= document.getElementById('cd-zoom-in'),
                controlUIzoomOut= document.getElementById('cd-zoom-out');
            controlDiv.appendChild(controlUIzoomIn);
            controlDiv.appendChild(controlUIzoomOut);

            // Setup the click event listeners and zoom-in or out according to the clicked element
            google.maps.event.addDomListener(controlUIzoomIn, 'click', function() {
                map.setZoom(map.getZoom()+1)
            });
            google.maps.event.addDomListener(controlUIzoomOut, 'click', function() {
                map.setZoom(map.getZoom()-1)
            });
        }

        var zoomControlDiv = document.createElement('div');
        var zoomControl = new CustomZoomControl(zoomControlDiv, map);

        //insert the zoom div on the top left of the map
        map.controls[google.maps.ControlPosition.LEFT_TOP].push(zoomControlDiv);


      
</script>


<?php 
/* NOTES:

http://codyhouse.co/gem/custom-google-map/
http://www.advancedcustomfields.com/resources/google-map/

*/ ?>