<div role="tabpanel" class="tab-pane" id="event-invitations">
 <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1" data-toggle="tooltip" data-title="<?php echo _l('flexstage_settings_send_emails_per_cron_run_tooltip'); ?>"></i>
  <?php echo render_input('settings[flexstage_send_emails_per_cron_run]', 'flexstage_settings_send_emails_per_cron_run', get_option('flexstage_send_emails_per_cron_run'), 'number'); ?>
</div>
