<div class="panel_s">
    <div class="panel-body">
        <?php $value = (isset($event) ? $event['name'] : set_value('name')); ?>
        <?php $attrs = (isset($event) ? [] : ['autofocus' => true]); ?>
        <?php echo render_input('name', 'flexstage_event_name', $value, 'text', $attrs); ?>


        <?php $value = (isset($event) ? $event['category_id'] : set_value('category_id')); ?>
        <?php echo render_select('category_id', $categories, ['id', 'name'], 'flexstage_event_category', $value); ?>

        <p class="bold">
            <?php echo _l('flexstage_short_description'); ?>
        </p>
        <?php $value = (isset($event) ? $event['summary'] : set_value('summary')); ?>
        <?php echo render_textarea('summary', '', $value, [], [], '', ''); ?>


        <p class="bold">
            <?php echo _l('flexstage_full_description'); ?>
        </p>
        <?php $value = (isset($event) ? $event['description'] : set_value('description')); ?>
        <?php echo render_textarea('description', '', $value, [], [], '', 'tinymce-event-description'); ?>


        <?php $typevalue = (isset($event) ? $event['type'] : 'location-based'); ?>
        <?php $types = flexstage_event_types(); ?>
        <p class="bold">
            <?php echo _l('flexstage_event_type'); ?>
        </p>
        <div class="form-group">
            <?php foreach ($types as $type) { ?>
                <div class="btn btn-default">
                    <label class=""
                        onclick="return flexstage_toggle_view_type('.container-<?php echo $type['id'] ?>','.flex-event-type-container')">
                        <input class="form-check-input" type="radio" name="type" id="<?php echo $type['id'] ?>"
                            value="<?php echo $type['id'] ?>" <?php echo set_radio('type', $type['id'], $typevalue == $type['id']); ?> />
                        <?php echo $type['label']; ?>
                    </label>
                </div>
            <?php } ?>
        </div>

        <div
            class="flex-event-type-container container-online <?php echo (set_value('type', $typevalue) == 'online' || set_value('type', $typevalue) == 'hybrid') ? '' : 'hidden'; ?>">
            <?php $value = (isset($event) ? $event['event_link'] : set_value('event_link')); ?>
            <?php echo render_input('event_link', 'flexstage_event_link', $value, 'text', $attrs); ?>
            <span class="tw-text-gray-600 tw-text-xs tw-italic">
                <?php echo _l('flexstage_event_link_help') ?>
            </span>
            <br />
            <br />
        </div>

        <div
            class="flex-event-type-container container-location-based <?php echo (set_value('type', $typevalue) == 'location-based' || set_value('type', $typevalue) == 'hybrid') ? '' : 'hidden'; ?>">
            <?php $value = (isset($event) ? $event['location'] : set_value('location')); ?>
            <?php echo render_input('location', 'flexstage_event_location', $value, 'text', $attrs); ?>
            <input type="hidden" id="latitude" name="latitude" value="<?php echo set_value('latitude') ?>" />
            <input type="hidden" id="longitude" name="longitude" value="<?php echo set_value('longitude') ?>" />
            <br />
        </div>


        <?php $value = (isset($event) ? _dt($event['start_date']) : _dt(set_value('start_date'))); ?>
        <?php echo render_datetime_input('start_date', 'flexstage_event_start_date', $value); ?>


        <?php $value = (isset($event) ? _dt($event['end_date']) : _dt(set_value('end_date'))); ?>
        <?php echo render_datetime_input('end_date', 'flexstage_event_end_date', $value); ?>

        <?php $value = (isset($event) ? $event['privacy'] : 'public'); ?>
        <?php $privacies = flexstage_event_privacy(); ?>
        <p class="bold">
            <?php echo _l('flexstage_event_privacy'); ?>
        </p>
        <div class="form-group">
            <?php foreach ($privacies as $privacy): ?>
                <div class="btn btn-default">
                    <label class=""
                        onclick="return flexstage_toggle_view_type('.privacy-<?php echo $privacy['id'] ?>','.flexstage-privacy')">
                        <input class="form-check-input" type="radio" name="privacy" id="<?php echo $privacy['id'] ?>"
                            value="<?php echo $privacy['id'] ?>" <?php echo set_radio('privacy', $privacy['id'], $value == $privacy['id']); ?> />
                        <?php echo $privacy['label']; ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>

        <?php $value = (isset($event) ? $event['tags'] : set_value('tags')); ?>
        <?php echo render_input('tags', 'flexstage_event_tags', $value, 'text', $attrs); ?>
        <span class="tw-text-gray-600 tw-text-xs tw-italic"><?php echo _l('flexstage_event_tags_help') ?></span>

        <div class="form-group">
            <?php
            $option_value = isset($event) ? $event['auto_sync_attendees'] : 0;
            $label = 'flexstage_autosync_attendees';
            ?>
            <div class="form-group">
                <label for="<?php echo $option_value; ?>" class="control-label clearfix">
                     <?php echo _l($label, '', false); ?>
                </label>
                <div class="radio radio-primary radio-inline">
                    <input type="radio" id="y_opt_1_<?php echo $label; ?>" name="auto_sync_attendees"
                           value="1" <?php if ($option_value == 1) {
                        echo 'checked';
                    } ?>>
                    <label for="y_opt_1_<?php echo $label; ?>">
                        <?php echo _l('settings_yes'); ?>
                    </label>
                </div>
                <div class="radio radio-primary radio-inline">
                    <input type="radio" id="y_opt_2_<?php echo $label; ?>" name="auto_sync_attendees"
                           value="0" <?php if ($option_value == 0) {
                        echo 'checked';
                    } ?>>
                    <label for="y_opt_2_<?php echo $label; ?>">
                        <?php echo _l('settings_no') ?>
                    </label>
                </div>
            </div>
        </div>
        <div class="form-group">
            <?php
            $option_value = isset($event) ? $event['auto_add_to_calendar'] : 0;
            $label = 'flexstage_autoadd_to_calendar';
            ?>
            <label for="calendar_option" class="control-label clearfix">
                    <?php echo _l($label, '', false); ?>
            </label>
            <div class="radio radio-primary radio-inline">
                <input type="radio" id="calendar_option1_<?php echo $label; ?>" name="auto_add_to_calendar"
                        value="1" <?php if ($option_value == 1) {
                    echo 'checked';
                } ?>>
                <label for="calendar_option1_<?php echo $label; ?>">
                    <?php echo _l('settings_yes'); ?>
                </label>
            </div>
            <div class="radio radio-primary radio-inline">
                <input type="radio" id="calendar_option2_<?php echo $label; ?>" name="auto_add_to_calendar"
                        value="0" <?php if ($option_value == 0) {
                    echo 'checked';
                } ?>>
                <label for="calendar_option2_<?php echo $label; ?>">
                    <?php echo _l('settings_no') ?>
                </label>
            </div>
        </div>

    </div>
    <div class="panel-footer text-right">
        <button type="submit" class="btn btn-primary">
            <?php echo strtoupper(_l('flexstage_save')); ?>
        </button>
    </div>
</div>

<script>
    function flexInitMapAutoCompleteMap() {
        var input = document.getElementById('location');
        var autocomplete = new google.maps.places.Autocomplete(input);
        autocomplete.addListener('place_changed', function () {
            var place = autocomplete.getPlace();
            $('#latitude').val(place.geometry.location.lat());
            $('#longitude').val(place.geometry.location.lng());
        });
    }
</script>
<?php $googleKey = get_option('google_api_key'); ?>
<script async
    src="https://maps.googleapis.com/maps/api/js?key=<?php echo $googleKey; ?>&libraries=places&callback=flexInitMapAutoCompleteMap">
    </script>