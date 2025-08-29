<?php if($speakers): ?>
<div class="col-md-12" style="padding-bottom: 15px;">
    <h3 class="tw-font-semibold">
        <?php echo _l('flexstage_speakers') ?>
    </h3>
    <?php foreach ($speakers as $speaker) { ?>
        <div class="col-md-4 text-center">
            <div class="flex-image-container flexstage-speaker-image tw-mx-auto">
                <div style="background-image: url('<?php echo ($speaker['image']) ? fs_image_file_url($speaker['event_id'], $speaker['image'], 'speakers') : site_url("modules/flexstage/assets/images/speaker-icon.jpg") ?>'); background-size: cover;" class="flexstage-speaker-image img-circle">
                </div>
            </div>
            
            <h5 class="text-center">
                <?php echo $speaker['name'] ?>
            </h5>
            <div>
                <p class="text-left">
                    <?php echo $speaker['bio'] ?>
                </p>
            </div>
        </div>
    <?php } ?>
</div>
<?php endif; ?>