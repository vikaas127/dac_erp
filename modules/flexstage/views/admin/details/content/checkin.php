<h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
    <?php echo _l('flexstage_checkin_attendees'); ?>
</h4>
<div class="panel_s panel-table-full">
    <div class="panel-body">
        <?php if (count($ticket_sales) > 0) { ?>
            <table class="table dt-table">
                <thead>
                    <tr>
                        <th>
                            <?php echo _l('flexstage_purchased_by'); ?>
                        </th>
                        <th>
                            <?php echo _l('flexstage_code'); ?>
                        </th>
                        <th>
                            <?php echo _l('flexstage_quantity_heading'); ?>
                        </th>
                        <th>
                            <?php echo _l('flexstage_checked_in'); ?>
                        </th>
                        <th>
                            <?php echo _l('flexstage_options_heading'); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ticket_sales as $ticket_sale) { ?>
                        <tr>
                            <td>
                                <?php echo flexstage_get_ticket_purchaser_name($ticket_sale['ticketorderid']); ?>
                            </td>
                            <td>
                                <?php echo $ticket_sale['reference_code']; ?>
                            </td>
                            <?php $quantity = $ticket_sale['quantity']; ?>
                            <td>
                                <?php echo $quantity; ?>
                            </td>
                            <?php $checked_in = $ticket_sale['checked_in']; ?>
                            <td>
                                <span class="<?php echo $checked_in == $quantity ? 'text-success' : ($checked_in > 0 ? 'text-warning' : 'text-danger'); ?>">
                                    <?php echo $checked_in . '/' . $quantity; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($checked_in < $quantity) { ?>
                                    <div class="tw-flex tw-items-center tw-space-x-3">
                                        <a href="<?php echo fs_get_admin_event_details_url($event['id'], $key) . "&check-in=1&ticketsale-id=$ticket_sale[id]"; ?>"
                                            class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 btn btn-default btn-with-tooltip tw-mt-2"
                                            title="<?php echo _l('flexstage_check_in') ?>">
                                            <i class="fa fa-check fa-lg"></i> <?php echo _l("flexstage_check_in") ?>
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
                <?php echo _l('flexstage_ticket_sale_not_found'); ?>
            </p>
        <?php } ?>
    </div>
</div>