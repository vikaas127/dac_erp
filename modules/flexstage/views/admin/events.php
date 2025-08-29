<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
init_head();

$has_permission_edit = has_permission('flexstage', '', 'edit');
$has_permission_create = has_permission('flexstage', '', 'create');
$has_permission_view = has_permission('flexstage', '', 'view');
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="_buttons tw-mb-2 sm:tw-mb-4">
                    <?php if ($has_permission_create) { ?>
                        <a href="<?php echo admin_url('flexstage/add_event'); ?>"
                            class="btn btn-primary pull-left display-block">
                            <i class="fa-regular fa-plus tw-mr-1"></i>
                            <?php echo _l('flexstage_create_event'); ?>
                        </a>
                        <a href="<?php echo admin_url('flexstage/categories'); ?>"
                            class="btn btn-default pull-left display-block mleft5">
                            <i class="fa-solid fa-layer-group tw-mr-1"></i>
                            <?php echo _l('flexstage_categories'); ?>
                        </a>
                    <?php } ?>
                    <?php if ($has_permission_view) { ?>
                    <a href="<?php echo admin_url('flexstage/mail_lists'); ?>"
                        class="btn btn-default pull-left mleft5 display-block">
                        <i class="fa-solid fa-envelopes-bulk tw-mr-1"></i>
                        <?php echo _l('flexstage_mail_lists'); ?>
                    </a>
                    <?php } ?>
                    <div class="clearfix"></div>
                </div>

                <div class="panel_s">
                    <div class="panel-body ">
                        <?php if (count($events) > 0) { ?>
                            <div class="panel-table-full">
                                <table class="table dt-table">
                                    <thead>
                                    <tr>
                                        <th >
                                            <?php
                                            echo _l('flexstage_event_name');
                                            ?>
                                        </th>
                                        <th class="flex-40-percent">
                                            <?php
                                            echo _l('flexstage_event_summary');
                                            ?>
                                        </th>
                                        <th>
                                            <?php
                                            echo _l('flexstage_event_start_date');
                                            ?>
                                        </th>
                                        <th>
                                            <?php
                                            echo _l('flexstage_event_end_date');
                                            ?>
                                        </th>
                                        <th>
                                            <?php
                                            echo _l('flexstage_publish');
                                            ?>
                                        </th>
                                        <th>
                                            <?php
                                            echo _l('flexstage_autosync_attendees');
                                            ?>
                                        </th>
                                        <th>
                                            <?php
                                            echo _l('flexstage_autoadd_to_calendar');
                                            ?>
                                        </th>
                                        <th>
                                            <?php echo _l('flexstage_options_heading'); ?>
                                        </th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($events as $event) { ?>
                                            <tr class="has-row-options ">
                                                <td>
                                                    <?php echo $event['name']; ?>
                                                    <div class="row-options">
                                                        <a href="<?php echo flexstage_get_client_event_url($event['slug']) ?>" target="_blank"><?php echo _l("flexstage_view_event") ?></a>
                                                        | <a href="<?php echo admin_url('flexstage/event_details/' . $event['id']); ?>" target="_blank">Manage Event</a>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php echo $event['summary']; ?>
                                                </td>
                                                <td>
                                                    <?php echo _dt($event['start_date']); ?>
                                                </td>
                                                <td>
                                                    <?php echo _dt($event['end_date']); ?>
                                                </td>
                                                <td>
                                                    <?php $checked = $event['status'] == 1 ? 'checked' : ''; ?>
                                                    <div class="onoffswitch">
                                                        <input type="checkbox"
                                                            data-switch-url="<?php echo admin_url() . 'flexstage/change_event_status' ?>"
                                                            name="onoffswitch" class="onoffswitch-checkbox"
                                                            id="<?php echo 'c_' . $event['id'] ?>" data-id="<?php echo $event['id'] ?>" <?php echo $checked ?>>
                                                        <label class=" onoffswitch-label"
                                                            for="<?php echo 'c_' . $event['id'] ?>"></label>
                                                    </div>

                                                    <span class="hide">
                                                        <?php echo ($checked == 'checked' ?
                                                            _l('is_active_export') : _l('is_not_active_export')) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php $checked = $event['auto_sync_attendees'] == 1  ?>
                                                    <span class="<?php echo $checked ? 'text-success' : 'text-danger' ?>">
                                                        <?php echo $checked ? 'Yes' : 'No' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php $checked = $event['auto_add_to_calendar'] == 1  ?>
                                                    <span class="<?php echo $checked ? 'text-success' : 'text-danger' ?>">
                                                        <?php echo $checked ? 'Yes' : 'No' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="tw-flex tw-items-center tw-space-x-3">
                                                        <?php if (has_permission('flexstage', '', 'edit')) { ?>
                                                            <a href="<?php echo admin_url('flexstage/event_details/' . $event['id']); ?>"
                                                                class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700"
                                                                title="<?php echo _l('flexstage_manage') ?>">
                                                                <i class="fa fa-gear fa-lg"></i>
                                                            </a>
                                                        <?php } ?>
                                                        <?php if (has_permission('flexstage', '', 'delete')) { ?>
                                                            <a href="<?php echo admin_url('flexstage/delete_event/' . $event['id']); ?>"
                                                                class="tw-mt-px tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete"
                                                                title="<?php echo _l('flexstage_delete') ?>">
                                                                <i class="fa-regular fa-trash-can fa-lg"></i>
                                                            </a>
                                                        <?php } ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } else { ?>
                            <p class="no-margin">
                                <?php echo _l('flexstage_no_events_found'); ?>
                            </p>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php init_tail(); ?>
</body>

</html>