<?php if ($event['type'] == 'location-based' || $event['type'] == 'hybrid') { ?>
    <div class="panel_s flex-border-white flexstage-rhs-panel flexstage-is-flex">
        <div class="">
            <span><i class="fa fa-location-dot"></i></span>
            <span><?php echo $event['location'] ?></span>
        </div>
        <div id="<?php echo $map_id ?>" class="map"></div>
    </div>
<?php } ?>