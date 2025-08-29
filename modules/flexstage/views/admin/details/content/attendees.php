<h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
    <?php echo _l('flexstage_attendees'); ?>
</h4>
<div class="panel_s panel-table-full">
    <div class="panel-body">
        <?php if (count($ticket_orders) > 0) { ?>
            <table class="table dt-table">
                <thead>
                    <tr>
                        <th>
                            <?php echo _l('flexstage_name_heading'); ?>
                        </th>
                        <th>
                            <?php echo _l('flexstage_email_label'); ?>
                        </th>
                        <th>
                            <?php echo _l('flexstage_total_amount'); ?>
                        </th>
                        <th>
                            <?php echo _l('flexstage_paid'); ?>
                        </th>
                        <th>
                            <?php echo _l('flexstage_in_leads'); ?>
                        </th>
                        <?php foreach ($custom_fields as $custom_field) { ?>
                            <th data-type="<?php echo $custom_field['type'] ?>" data-custom-field='1'>
                                <?php echo $custom_field['name']; ?>
                            </th>
                        <?php } ?>
                        <th>
                            <?php echo _l('flexstage_options_heading'); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ticket_orders as $ticket_order) { ?>
                        <tr>
                            <td>
                                <?php echo $ticket_order['attendee_name']; ?>
                            </td>
                            <td>
                                <?php echo $ticket_order['attendee_email']; ?>
                            </td>
                            <td>
                                <?php echo $currency->symbol . number_format($ticket_order['total_amount']); ?>
                            </td>
                            <?php $paid = flexstage_is_paid_ticketorder($ticket_order['id']); ?>
                            <td>
                                <span class="<?php echo $paid ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $paid ? 'Yes' : 'No'; ?>
                                </span>
                            </td>
                            <?php $in_leads = $ticket_order['in_leads'] == 1; ?>
                            <td>
                                <span class="<?php echo $in_leads ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $in_leads ? 'Yes' : 'No'; ?>
                                </span>
                            </td>

                            <?php foreach($custom_fields as $custom_field){ ?>
                                <td>
                                    <?php echo get_custom_field_value($ticket_order['id'], $custom_field['slug'], FLEXSTAGE_FIELD_TO) ?>
                                </td>
                            <?php } ?>
                            <td>
                                <?php if ($paid) { ?>
                                    <div class="tw-flex tw-items-center tw-space-x-3">
                                        <a href="<?php echo fs_get_admin_event_details_url($event['id'], $key) . "&send-tickets=1&ticketorder-id=$ticket_order[id]"; ?>"
                                            class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 btn btn-default btn-with-tooltip"
                                            title="<?php echo _l('flexstage_send_tickets') ?>">
                                            <i class="fa fa-paper-plane fa-lg"></i> <?php echo _l("flexstage_resend_ticket") ?>
                                        </a>
                                    </div>
                                <?php } ?>
                                <?php if (!$in_leads) { ?>
                                    <div class="tw-flex tw-items-center tw-space-x-3">
                                        <a href="<?php echo fs_get_admin_event_details_url($event['id'], $key) . "&sync-lead=1&ticketorder-id=$ticket_order[id]"; ?>"
                                            class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 btn btn-default btn-with-tooltip tw-mt-2"
                                            title="<?php echo _l('flexstage_sync_lead') ?>">
                                            <i class="fa fa-recycle fa-lg"></i> <?php echo _l("flexstage_sync_lead") ?>
                                        </a>
                                    </div>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php
                    } ?>
                </tbody>
            </table>
        <?php } else { ?>
            <p class="no-margin">
                <?php echo _l('flexstage_no_attendees_found'); ?>
            </p>
        <?php } ?>
    </div>
</div>