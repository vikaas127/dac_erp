<div class="row">
    <div class="col-md-12">
        <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
            <?php echo $title; ?>
        </h4>
        <h6>
            <?php echo _l('flexstage_ticket_subheader') ?>
        </h6>
        <?php echo validation_errors('<div class="alert alert-danger alert-dismissible" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="right:0px"><span aria-hidden="true">&times;</span></button>', '</div>'); ?>

        <?php $hidden = isset($ticket) ? ['ticket_id' => $ticket['id']] : []?>
        <?php echo form_open(current_url() . '?key=' . $key, ['id' => 'flex_tickets_form'], $hidden); ?>

        <div class="panel_s">
            <div class="panel-body">
                <?php $value = (isset($ticket) ? $ticket['name'] : set_value('name')); ?>
                <?php $attrs = ['placeholder' => _l('flexstage_ticket_name_placeholder')]; ?>
                <?php echo render_input('name', 'flexstage_ticket_name', $value, 'text', $attrs); ?>

                <?php $value = (isset($ticket) ? $ticket['status'] : set_value('status', 'open')); ?>
                <?php $statuses = flexstage_ticket_statuses(); ?>
                <?php echo render_select('status', $statuses, ['id', 'label'], 'flexstage_ticket_status', $value); ?>

                <?php $value = (isset($ticket) ? $ticket['quantity'] : set_value('quantity')); ?>
                <?php $attrs = ['placeholder' => _l('flexstage_ticket_quantity_placeholder'), 'min' => 1]; ?>
                <?php echo render_input('quantity', 'flexstage_ticket_quantity', $value, 'number', $attrs); ?>

                <?php $value = (isset($ticket) ? $ticket['paid'] : 0); ?>
                <p class="bold">
                    <?php echo _l('flexstage_ticket_price'); ?>
                </p>
                <div class="form-group">
                    <div class="btn btn-default">
                        <label class=""
                            onclick="return flexstage_toggle_view_type('.container-paid', '.flex-ticket-price-container')">
                            <input class="form-check-input" type="radio" name="paid" id="paid"
                                value="1" <?php echo set_radio('paid', 1, $value == 1); ?> />
                            <?php echo _l('flexstage_ticket_paid'); ?>
                        </label>
                    </div>
                    <div class="btn btn-default">
                        <label class=""
                            onclick="return flexstage_toggle_view_type('.container-free', '.flex-ticket-price-container')">
                            <input class="form-check-input" type="radio" name="paid" id="free"
                                value="0" <?php echo set_radio('paid', 0, $value == 0); ?> />
                            <?php echo _l('flexstage_ticket_free'); ?>
                        </label>
                    </div>
                </div>

                <div
            class="flex-ticket-price-container container-paid <?php echo set_value('paid', $value) == 1 ? '' : 'hidden'; ?>">
                    <?php
                    $currency_attr = ['disabled' => 'disabled', 'data-show-subtext' => true];

                    foreach ($currencies as $currency) {
                        if ($currency['isdefault'] == 1) {
                            $currency_attr['data-base'] = $currency['id'];
                            $selected = $currency['id'];
                        }
                    }
                    ?>
                    <div class="row">
                        <div class="col-md-3">
                            <div id="ticket_currency">
                                <?php echo render_select('currency', $currencies, ['id', 'name', 'symbol'], 'expense_currency', $selected, $currency_attr); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <?php $value = (isset($ticket) ? $ticket['price'] : set_value('price')); ?>
                            <?php $attrs = ['placeholder' => _l('flexstage_ticket_price_placeholder'), 'min' => 0, 'step' => '0.01']; ?>
                            <?php echo render_input('price', 'flexstage_ticket_amount', $value, 'number', $attrs); ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <?php $value = (isset($ticket) ? $ticket['min_buying_limit'] : set_value('min_buying_limit')); ?>
                        <?php $attrs = ['placeholder' => _l('flexstage_ticket_min_buying_limit_placeholder'), 'min' => 0]; ?>
                        <?php echo render_input('min_buying_limit', 'flexstage_ticket_min_buying_limit', $value, 'number', $attrs); ?>
                    </div>
                    <div class="col-md-6">
                        <?php $value = (isset($ticket) ? $ticket['max_buying_limit'] : set_value('max_buying_limit')); ?>
                        <?php $attrs = ['placeholder' => _l('flexstage_ticket_max_buying_limit_placeholder'), 'min' => 0]; ?>
                        <?php echo render_input('max_buying_limit', 'flexstage_ticket_max_buying_limit', $value, 'number', $attrs); ?>
                    </div>
                </div>

                <div class="row tw-mb-6">
                    <div class="col-md-6">
                        <?php $value = (isset($ticket) ? $ticket['sales_start_date'] : set_value('sales_start_date')); ?>
                        <?php $attrs = ['placeholder' => _l('flexstage_ticket_sales_start_date_placeholder')] ?>
                        <?php echo render_datetime_input('sales_start_date', 'flexstage_ticket_sales_start_date', $value, $attrs); ?>
        
                        <label class="">
                            <input class="form-check-input" type="checkbox" name="sales_start_date_now" />
                            <?php echo _l('flexstage_ticket_sales_start_date_now'); ?>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <?php $value = (isset($ticket) ? $ticket['sales_end_date'] : set_value('sales_end_date')); ?>
                        <?php $attrs = ['placeholder' => _l('flexstage_ticket_sales_end_date_placeholder')] ?>
                        <?php echo render_datetime_input('sales_end_date', 'flexstage_ticket_sales_end_date', $value, $attrs); ?>
        
                        <label class="">
                            <input class="form-check-input" type="checkbox" name="sales_end_date_event" />
                            <?php echo _l('flexstage_event_end_date'); ?>
                        </label>
                    </div>
                </div>

                <?php $value = (isset($ticket) ? $ticket['description'] : set_value('description')); ?>
                <?php echo render_textarea('description', 'flexstage_ticket_description', $value, [], [], '', 'tinymce-event-description'); ?>

                <div class="panel-footer text-right">
                    <button type="submit" class="btn btn-primary">
                        <?php echo strtoupper(_l('flexstage_save')); ?>
                    </button>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>

        <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
            <?php echo _l('flexstage_tickets'); ?>
        </h4>
        <div class="panel_s panel-table-full">
            <div class="panel-body">
                <?php if (count($tickets) > 0) { ?>
                    <table class="table dt-table">
                        <thead>
                            <tr>
                                <th>
                                    <?php echo _l('flexstage_name_heading'); ?>
                                </th>
                                <th>
                                    <?php echo _l('flexstage_quantity_heading'); ?>
                                </th>
                                <th>
                                    <?php echo _l('flexstage_price_heading'); ?>
                                </th>
                                <th>
                                    <?php echo _l('flexstage_ticket_status'); ?>
                                </th>
                                <th>
                                    <?php echo _l('flexstage_options_heading'); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket) { ?>
                                <tr>
                                    <td>
                                        <?php echo $ticket['name']; ?>
                                    </td>
                                    <td>
                                        <?php echo number_format($ticket['quantity']); ?>
                                    </td>
                                    <td>
                                        <?php echo $base_currency->symbol . number_format($ticket['price'], 2); ?>
                                    </td>
                                    <td>
                                        <?php echo $ticket['status']; ?>
                                    </td>
                                    <td>
                                        <div class="tw-flex tw-items-center tw-space-x-3">
                                            <?php if (has_permission('flexstage', '', 'edit')) { ?>
                                                <a href="<?php echo fs_get_admin_event_details_url($event['id'] , $key , $ticket['id']); ?>"
                                                    class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700"
                                                    title="<?php echo _l('flexstage_edit') ?>">
                                                    <i class="fa-regular fa-pen-to-square fa-lg"></i>
                                                </a>
                                            <?php } ?>
                                                    <?php if (has_permission('flexstage', '', 'delete')) { ?>
                                                        <a href="<?php echo admin_url('flexstage/remove_ticket/' . $event['id'] . '/' . $ticket['id'] . '?key=' . $key); ?>"
                                                            class="tw-mt-px tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete">
                                                            <i class="fa-regular fa-trash-can fa-lg"></i>
                                                        </a>
                                                    <?php } ?>
                                                </div>
                                            </td>
                                        </tr>
                                <?php
                            } ?>
                        </tbody>
                    </table>
                <?php } else { ?>
                    <p class="no-margin">
                        <?php echo _l('flexstage_no_tickets_found'); ?>
                    </p>
                <?php } ?>
            </div>
        </div>
    </div>