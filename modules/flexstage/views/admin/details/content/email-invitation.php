<div class="row">
    <div class="col-md-12">
        <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
            <?php echo $title; ?>
        </h4>

        <?php echo form_open('admin/flexstage/send_invitation/' . $event['id'] . '?key=' . $key); ?>

        <?php echo validation_errors('<div class="alert alert-danger alert-dismissible" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="right:0px"><span aria-hidden="true">&times;</span></button>', '</div>'); ?>

        <div class="panel_s">
            <div class="panel-body">

                <p class="mbot30 text-warning">
                    <?php echo _l('flexstage_send_mail_lists_note_logged_in'); ?>
                </p>
                <div class="form-group">
                    <div class="checkbox checkbox-primary">
                        <input type="checkbox" name="send_invitation_to[clients]" id="ml_clients">
                        <label for="ml_clients">
                            <?php echo _l('flexstage_send_mail_list_clients'); ?>
                        </label>
                    </div>

                    <div class="customer-groups" style="display:none;">

                        <div class="clearfix"></div>
                        <div class="checkbox checkbox-primary mleft10">
                            <input type="checkbox" checked name="ml_customers_all" id="ml_customers_all">
                            <label for="ml_customers_all">
                                <?php echo _l('flexstage_customers_all'); ?>
                            </label>
                        </div>
                        <hr class="hr-10" />
                        <?php foreach ($customers_groups as $group) { ?>
                            <div class="checkbox checkbox-primary mleft10 survey-customer-groups">
                                <input type="checkbox" name="customer_group[<?php echo $group['id']; ?>]"
                                    id="ml_customer_group_<?php echo $group['id']; ?>">
                                <label for="ml_customer_group_<?php echo $group['id']; ?>">
                                    <?php echo $group['name']; ?>
                                </label>
                            </div>
                        <?php } ?>
                        <?php
                        if (is_gdpr() && (get_option('gdpr_enable_consent_for_contacts') == '1')) { ?>
                            <select name="contacts_consent[]" title="<?php echo _l('gdpr_consent'); ?>" multiple="true"
                                id="contacts_consent" class="selectpicker" data-width="100%">
                                <?php foreach ($purposes as $purpose) { ?>
                                    <option value="<?php echo $purpose['id']; ?>">
                                        <?php echo $purpose['name']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        <?php } ?>
                    </div>
                    <hr />
                    <div class="checkbox checkbox-primary">
                        <input type="checkbox" name="send_invitation_to[leads]" id="ml_leads">
                        <label for="ml_leads">
                            <?php echo _l('flexstage_leads_label'); ?>
                        </label>
                    </div>
                    <div class="leads-statuses" style="display:none;">
                        <div class="clearfix"></div>
                        <div class="checkbox checkbox-primary mleft10">
                            <input type="checkbox" checked name="leads_all" id="ml_leads_all">
                            <label for="ml_leads_all">
                                <?php echo _l('flexstage_leads_all'); ?>
                            </label>
                        </div>
                        <hr class="hr-10" />

                        <?php foreach ($leads_statuses as $status) { ?>
                            <div class="checkbox checkbox-primary mleft10 survey-lead-status">
                                <input type="checkbox" name="leads_status[<?php echo $status['id']; ?>]"
                                    id="ml_leads_status_<?php echo $status['id']; ?>">
                                <label for="ml_leads_status_<?php echo $status['id']; ?>">
                                    <?php echo $status['name']; ?>
                                </label>
                            </div>
                        <?php } ?>
                        <?php
                        if (is_gdpr() && (get_option('gdpr_enable_consent_for_leads') == '1')) { ?>
                            <select name="leads_consent[]" title="<?php echo _l('gdpr_consent'); ?>" multiple="true"
                                id="leads_consent" class="selectpicker" data-width="100%">
                                <?php foreach ($purposes as $purpose) { ?>
                                    <option value="<?php echo $purpose['id']; ?>">
                                        <?php echo $purpose['name']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        <?php } ?>
                    </div>
                    <hr />
                    <div class="checkbox checkbox-primary">
                        <input type="checkbox" name="send_invitation_to[staff]" id="ml_staff">
                        <label for="ml_staff">
                            <?php echo _l('flexstage_send_mail_list_staff'); ?>
                        </label>
                    </div>
                    <?php if (count($mail_lists) > 0) { ?>
                        <hr />
                        <p class="bold">
                            <?php echo _l('flexstage_send_mail_lists_string'); ?>
                        </p>
                        <?php foreach ($mail_lists as $list) { ?>
                            <div class="checkbox checkbox-primary">
                                <input type="checkbox" id="ml_custom_<?php echo $list['listid']; ?>"
                                    name="send_invitation_to[<?php echo $list['listid']; ?>]">
                                <label for="ml_custom_<?php echo $list['listid']; ?>">
                                    <?php echo $list['name']; ?>
                                </label>
                            </div>
                        <?php } ?>
                    <?php } ?>


                    <?php if ($send_log_count > 0) { ?>
                        <p class="text-warning">
                            <?php echo _l('flexstage_send_notice'); ?>
                        </p>
                    <?php } ?>
                    <?php foreach ($send_log as $log) { ?>
                        <p>
                            <?php if (has_permission('flexstage', '', 'delete')) { ?>
                                <a href="<?php echo admin_url('flexstage/remove_survey_send/' . $log['id']); ?>"
                                    class="_delete text-danger"><i class="fa fa-remove"></i></a>
                            <?php } ?>
                            <?php echo _l('flexstage_added_to_queue', _dt($log['date'])); ?>
                            (
                            <?php echo ($log['iscronfinished'] == 0 ? _l('flexstage_send_till_now') . ' ' : '') ?>
                            <?php echo _l('flexstage_send_to_total', $log['total']); ?> )
                            <br />
                            <b class="bold">
                                <?php echo _l('flexstage_send_finished', ($log['iscronfinished'] == 1 ? _l('flexstage_settings_yes') : _l('flexstage_settings_no'))); ?>
                            </b>
                        </p>
                        <?php if (!empty($log['send_to_mail_lists'])) { ?>
                            <p>
                                <b>
                                    <?php echo _l('flexstage_send_to_lists'); ?>:
                                </b>
                                <?php
                                $send_lists = unserialize($log['send_to_mail_lists']);
                                foreach ($send_lists as $send_list) {
                                    $last = end($send_lists);
                                    echo _l($send_list, '', false) . ($last == $send_list ? '' : ',');
                                }
                                ?>
                            </p>
                        <?php } ?>
                        <hr />
                    <?php } ?>

                    <div class="panel-footer text-right">
                        <button type="submit" class="btn btn-primary">
                            <?php echo _l('flexstage_send_string'); ?>
                        </button>
                    </div>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>