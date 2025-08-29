<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php echo $header; ?>

<div class="panel_s">
    <div class="panel-body">
        <div class="row">
            <?= init_flexstage_event_header() ?>

            <?php if ($tickets_count > 0) { ?>
                <div class="col-md-12">
                    <h1>
                        <?= _l('flexstage_tickets') ?>
                    </h1>
                </div>

                <div class="col-md-12">
                    <div class="step-app" id="tickets">
                        <ul class="step-steps">
                            <li data-step-target="step1">
                                <?= _l('flexstage_select_tickets') ?>
                            </li>
                            <li data-step-target="step2">
                                <?= _l('flexstage_share_details') ?>
                            </li>
                        </ul>
                        <div class="step-content">
                            <div class="step-tab-panel" data-step="step1">
                                <div class="row" style="margin: 0px;">
                                    <div class="col-sm-7 col-md-8">
                                        <h3 class="tw-font-semibold">
                                            <?= _l('flexstage_choose_your_tickets') ?>
                                        </h3>
                                        <form id="ticket-form">
                                            <input id="currency-symbol" name="symbol" type="hidden"
                                                value="<?= $currency->symbol ?>">
                                            <?php foreach ($tickets as $ticket) { ?>
                                                <div class="row">
                                                    <hr>
                                                    <div class="col-md-8">
                                                        <h4 class="tw-font-semibold">
                                                            <?= $ticket['name'] ?>
                                                        </h4>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <strong>
                                                            <?= $ticket['price'] == 0 ? 'Free' : $currency->symbol . number_format($ticket['price'], 2) ?>
                                                        </strong>
                                                    </div>
                                                    <div class="col-md-8">
                                                        <?php echo _l("flexstage_sales_end_on") ?>
                                                        <strong>
                                                            <?= date('M d, Y', strtotime($ticket['sales_end_date'])) ?>
                                                        </strong>
                                                    </div>
                                                    <?php if ($ticket['quantity'] > $ticket_sales_quantity[$ticket['id']]['quantity']) { ?>
                                                        <?php $quantity_remaining = $ticket['quantity'] - $ticket_sales_quantity[$ticket['id']]['quantity'] ?>
                                                        <?php $max = $ticket['max_buying_limit'] > $quantity_remaining ? $quantity_remaining : $ticket['max_buying_limit'] ?>
                                                        <div class="col-md-4">
                                                            <div class="flex-quantity-wrapper">
                                                                <button type="button"
                                                                    class="flex-quantity-btn flex-quantity-btn--minus"
                                                                    data-id="<?php echo $ticket['id'] ?>"
                                                                    data-name="<?php echo $ticket['name'] ?>"
                                                                    data-price="<?php echo $ticket['price'] ?>"
                                                                    data-min="<?php echo $ticket['min_buying_limit'] ?>"
                                                                    data-max="<?php echo $max ?>">
                                                                    <i class="fa fa-minus"></i>
                                                                </button>
                                                                <input type="number" class="flex-quantity-input"
                                                                    id="quantity<?php echo $ticket['id'] ?>"
                                                                    name="quantity<?php echo $ticket['id'] ?>" value="0"
                                                                    min="<?php echo $ticket['min_buying_limit'] ?>"
                                                                    max="<?php echo $max ?>" data-id="<?php echo $ticket['id'] ?>"
                                                                    data-name="<?php echo $ticket['name'] ?>"
                                                                    data-price="<?php echo $ticket['price'] ?>" required />
                                                                <button type="button"
                                                                    class="flex-quantity-btn flex-quantity-btn--plus"
                                                                    data-id="<?php echo $ticket['id'] ?>"
                                                                    data-name="<?php echo $ticket['name'] ?>"
                                                                    data-price="<?php echo $ticket['price'] ?>"
                                                                    data-min="<?php echo $ticket['min_buying_limit'] ?>"
                                                                    data-max="<?php echo $max ?>">
                                                                    <i class="fa fa-plus"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    <?php } else { ?>
                                                        <div class="col-md-4">
                                                            <span class="badge bg-danger">
                                                                <?php echo _l('flexstage_sold_out') ?>
                                                            </span>
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                            <?php } ?>
                                        </form>
                                    </div>
                                    <?php echo init_flexstage_ticket_order() ?>
                                </div>
                            </div>
                            <div class="step-tab-panel" data-step="step2">
                                <div class="row" style="margin: 0px;">
                                    <div class="col-sm-7 col-md-8">
                                        <h3>
                                            <?= _l('flexstage_attendee_information') ?>
                                        </h3>

                                        <?php echo form_open(current_url(), ['id' => 'attendee-form']); ?>

                                        <div class="row">
                                            <hr>
                                            <div class="col-md-6">
                                                <?php echo render_input('attendee_name', 'flexstage_name_label', '', 'text', ['required' => 'required']); ?>
                                            </div>
                                            <div class="col-md-6">
                                                <?php echo render_input('attendee_email', 'flexstage_email_label', '', 'email', ['required' => 'required']); ?>
                                            </div>
                                            <div class="col-md-6">
                                                <?php echo render_input('attendee_mobile', 'flexstage_mobile', '', 'tel'); ?>
                                            </div>
                                            <div class="col-md-6">
                                                <?php echo render_input('attendee_company', 'flexstage_company_name', ''); ?>
                                            </div>
                                        </div>
                                        <?php $rel_id_custom_field = (isset($event) ? $event['id'] : false); ?>
                                        <?php echo render_custom_fields('flexstage', $rel_id_custom_field); ?>
                                        <?php echo form_close(); ?>

                                    </div>
                                    <?php echo init_flexstage_ticket_order() ?>
                                </div>
                            </div>
                        </div>
                        <div class="step-footer text-right">
                            <button data-step-action="prev" class="btn btn-secondary btn-lg">Previous</button>
                            <button data-step-action="next" class="btn btn-danger btn-lg">Next</button>
                            <button data-step-action="finish" class="btn btn-success btn-lg">Finish</button>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
<script>
    $(document).ready(function () {

        //control ticket quantity with buttons
        $(document).on('click', '.flex-quantity-btn', function () {
            const o = $(this);
            if ($(o).hasClass('flex-quantity-btn--minus')) {
                let input = $(o).next();
                let min = 0;
                let value = Number(input.val());
                if (value > min) {
                    input.val(value - 1);
                    input.trigger('change');
                }
            } else {
                let input = $(o).prev();
                let max = Number(input.attr('max'));
                let value = Number(input.val());
                if (value < max) {
                    input.val(value + 1);
                    input.trigger('change');
                }
            }
        });

        let tickets = [];
        let attendeeForm = $('#attendee-form');
        let attendeeFormValidator = attendeeForm.validate();
        const formatter = new Intl.NumberFormat('en-US', {
            style: 'decimal',
            minimumFractionDigits: 2,
        });


        function updateTotal() {
            let totalTickets = tickets.reduce((accumulator, item) => accumulator + item.quantity, 0);
            let totalPrice = tickets.reduce((accumulator, item) => accumulator + item.subTotal, 0);
            let symbol = $('#currency-symbol').val();

            totalPrice = (totalPrice == 0 && totalTickets == 0) ? '' : ((totalPrice == 0 && totalTickets > 0) ? 'Free' : symbol + formatter.format(totalPrice));
            $('.total-price').text(totalPrice);
        }
        $('form').on('change', 'input[name^="quantity"]', function (e) {
            let input = $(this)
            let max = Number(input.attr('max'));
            let min = Number(input.attr('min'));
            let value = Number(input.val());
            let name = input.data('name');
            let id = input.data('id');
            let symbol = $('#currency-symbol').val();
            let price = Number(input.data('price'));
            let orderHtml = '';
            let ticket = null;
            let ticketIndex = -1;

            if (tickets.length > 0) {
                ticketIndex = tickets.findIndex(element => element.id === id);
            }

            if (value < min) {
                input.val(0)
            } else if (value > max) {
                alert_float('danger', 'You can not purchase more than '+max+' tickets.');
                input.val(0)
            }

            if (value >= min && value <= max) {
                if (ticketIndex < 0) {
                    ticket = {
                        id: id,
                        name: name,
                        quantity: value,
                        price: price,
                        subTotal: (price * value)
                    }

                    tickets.push(ticket)

                    let formattedSubtotal = symbol + formatter.format(ticket.subTotal)

                    orderHtml += `
                        <div class="ticket-order-cart-row">
                            <hr/>
                            <div class="row">
                                <div class="col-xs-6 col-md-8 order-${ticket.id} details">
                                     ${ticket.name} x ${ticket.quantity}
                                </div>
                                <div class="col-xs-6 col-md-4 order-${ticket.id} price">
                                    ${ticket.subTotal > 0 ? formattedSubtotal : 'Free'}
                                </div>
                            </div>
                        </div>`;

                    let hiddenInput = `<input id="ticket-${ticket.id}" type="hidden" name="tickets[${ticket.id}]" value="${ticket.quantity}">`

                    $('.orders').append(orderHtml);
                    attendeeForm.append(hiddenInput)
                } else {
                    ticket = tickets[ticketIndex];
                    ticket.quantity = value;
                    ticket.subTotal = (price * value);

                    let formattedSubtotal = symbol + formatter.format(ticket.subTotal);

                    let detailsUpdate = `${ticket.name} x ${ticket.quantity}`;
                    let priceUpdate = `${ticket.subTotal > 0 ? formattedSubtotal : 'Free'}`;
                    $(`.order-${ticket.id}.details`).text(detailsUpdate);
                    $(`.order-${ticket.id}.price`).text(priceUpdate);
                    $(`#ticket-${ticket.id}`).val(ticket.quantity);
                }

                updateTotal()
            } else {
                if (ticketIndex >= 0) {
                    ticket = tickets[ticketIndex];
                    $(`.order-${ticket.id}`).closest('.ticket-order-cart-row').remove();
                    tickets.splice(ticketIndex, 1)
                    updateTotal()
                }
            }
        });

        $('#tickets').steps({
            onChange: function (currentIndex, newIndex, stepDirection) {
                // step2
                if (currentIndex === 0) {
                    if (stepDirection === 'forward') {
                        let ticketInputs = $('input[id^="ticket-"]')
                        if (ticketInputs.length == 0) {
                            alert_float('danger', 'You have to select a ticket');
                            return false;
                        }
                    }
                }
                return true;
            },
            onFinish: function () {
                attendeeForm.submit()
                // alert('Wizard Completed');
            }
        });
    });
</script>