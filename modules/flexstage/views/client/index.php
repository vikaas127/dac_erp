<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php echo $header; ?>
<div class="panel_s no-border-radius flex-event-container">
    <div class="row">
        <div class="col-md-12">
            <div class="col-md-12">
                <h3 class="tw-font-semibold flex-event-main-title">
                    <?php echo ucwords($event['name']) ?>
                </h3>
                <?php echo $social_view; ?>
            </div>
        </div>
        <div class="col-md-8">
            <div class="row tw-m-0">
                <div class="col-md-12">
                    <?php echo $media_view; ?>
                </div>
                <?php echo $description_view; ?>
                <?php echo $speaker_view; ?>
            </div>
        </div>
        <div class="col-md-4">
            <div class="row tw-m-0">
                <div class="col-md-12">
                    <div class="">
                        <div class="panel_s flex-border-white flexstage-rhs-panel flexstage-is-flex">
                            <div>
                                <span><i class="fa fa-clock"></i> </span>
                                <span><?php echo flexstage_format_date($event['start_date']) ?> - <?php echo flexstage_format_date($event['end_date']) ?></span>
                            </div>
                        </div>
                        <?php echo flexstage_render_event_location('map2'); ?>
                    </div>
                    <?php
                    $z = 15;
                    $lng = $event['longitude'];
                    $lat = $event['latitude'];
                    $add = $event['location'];
                    ?>
                    <script>
                        function upInitMap() {
                            var map1, map2
                            var options = {
                                zoom: <?php echo $z; ?>,
                                center: {
                                    lat: Number(<?php echo $lat; ?>),
                                    lng: Number(<?php echo $lng; ?>)
                                }
                            }

                            //map1 = new google.maps.Map(document.getElementById('map1'), options);
                            map2 = new google.maps.Map(document.getElementById('map2'), options);
                            var geocoder = new google.maps.Geocoder();
                            //geocodeAddress(geocoder, map1);
                            geocodeAddress(geocoder, map2);
                        }
                        function geocodeAddress(geocoder, resultsMap) {
                            var address = '<?php echo json_encode($add) ?>';
                            geocoder.geocode({ 'address': address }, function (results, status) {
                                if (status === google.maps.GeocoderStatus.OK) {
                                    resultsMap.setCenter(results[0].geometry.location);
                                    var marker = new google.maps.Marker({
                                        map: resultsMap,
                                        position: results[0].geometry.location
                                    });
                                } else {
                                    console.log('something went wrong');
                                }
                            });
                        }
                    </script>
                    <?php $key = trim(get_option('google_api_key') != '') ? '&key=' . get_option('google_api_key') : '' ?>
                    <script async defer
                        src="https://maps.googleapis.com/maps/api/js?callback=upInitMap&v=3<?php echo $key ?>"></script>
                </div>
            </div>
        </div>
    </div>
</div>
<?php echo $footer; ?>

<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>