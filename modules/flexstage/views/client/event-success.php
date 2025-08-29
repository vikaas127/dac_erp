<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php echo $header; ?>

<div class="panel_s">
    <div class="panel-body">
        <div class="row">
            <?= init_flexstage_event_header() ?>

            <div class="col-md-12">
                <div class="text-center">
                    <p class="flexstage-success-icon"><i class="fa fa-check-circle"></i> </p>
                    <h2 class="tw-font-semibold">
                        <?php echo _l("flexstage_your_registration_is_successful"); ?>
                    </h2>
                    <h4><?php echo _l("flexstage_thank_you_for_registring_for_event"); ?></h4>

                    <p><?php echo _l("flexstage_registration_success_note") ?></p>

                    <div class="mtop20">
                        <a href="<?php echo fs_get_event_url($event) ?>" class="btn btn-info">
                            <?php echo _l("flexstage_go_back_event_details"); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>