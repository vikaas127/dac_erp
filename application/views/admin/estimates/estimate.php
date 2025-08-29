<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <?php
            echo form_open($this->uri->uri_string(), ['id' => 'estimate-form', 'class' => '_transaction_form estimate-form']);
            if (isset($estimate)) {
                echo form_hidden('isedit');
            }
            ?>
            <div class="col-md-12">
                <h4
                    class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700 tw-flex tw-items-center tw-space-x-2">
                    <span>
                        <?php echo e( isset($estimate) ? format_estimate_number($estimate) : _l('create_new_estimate')); ?>
                    </span>
                    <?php echo isset($estimate) ? format_estimate_status($estimate->status) : ''; ?>
                </h4>
                <?php $this->load->view('admin/estimates/estimate_template'); ?>
            </div>
            <?php echo form_close(); ?>
            <?php $this->load->view('admin/invoice_items/item'); ?>
        </div>
    </div>
</div>
</div>
<?php init_tail(); ?>











<script>
$(function() {
    validate_estimate_form();
    // Init accountacy currency symbol
    init_currency();
    // Project ajax search
    init_ajax_project_search_by_customer_id();
    // Maybe items ajax search
    init_ajax_search('items', '#item_select.ajax-search', undefined, admin_url + 'items/search');
});
</script>

<script>
let globalCustomerGroupDiscount = 0;

jQuery(function($) {

    // 1. Fetch customer group discount and store globally
    $('#clientid').on('change', function() {
        var customerId = $(this).val();
        if (customerId) {
            $.ajax({
                url: admin_url + 'estimates/get_customer_group_info/' + customerId,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#customer_group_name').text(data.group_name);
                    $('#customer_group_discount').text(data.default_discount + '%');
                    globalCustomerGroupDiscount = parseFloat(data.default_discount) || 0;
                    $('#basic_discount_percent').val(globalCustomerGroupDiscount).trigger('input');
                    $('#customer_group_id').val(data.group_id);
                    $('#quantity_discount_allowed').val(data.quantity_discount_allowed);
                },
                error: function() {
                    $('#customer_group_name').text('—');
                    $('#customer_group_discount').text('0%');
                    globalCustomerGroupDiscount = 0;
                    $('#customer_group_id').val(null);
                    $('#quantity_discount_allowed').val(0);
                }
            });
        } else {
            $('#customer_group_name').text('—');
            $('#customer_group_discount').text('0%');
            globalCustomerGroupDiscount = 0;
            $('#basic_discount_percent').val(0).trigger('input');
            $('#customer_group_id').val(null);
            $('#quantity_discount_allowed').val(0);

            calculate_total();
        }
    });

    // 2. Apply discount on every newly added item row
    window.applyGroupDiscountToLastItemRow = function () {
        if (!globalCustomerGroupDiscount || globalCustomerGroupDiscount <= 0) return;

        const $lastItem = $('.estimate-items-table tbody tr.item').last();
        if ($lastItem.length === 0) return;

        const $rateInput = $lastItem.find('input[name*="[rate]"]');
        if (!$rateInput.length) return;

        const nameAttr = $rateInput.attr('name');
        const discountInputName = nameAttr.replace('[rate]', '[discount_percent]');

        // Avoid duplicate injection
        if ($lastItem.find(`input[name="${discountInputName}"]`).length === 0) {
            const $discountInput = $(`<input type="hidden" name="${discountInputName}" value="${globalCustomerGroupDiscount}">`);
            $lastItem.append($discountInput);
        }

        if (typeof calculate_total !== 'undefined') {
            calculate_total();
        }
    };

    // 3. Hook into item add
    const originalAddItemToTable = window.add_item_to_table;

    window.add_item_to_table = function(itemid, merge_invoice, is_existing) {
        originalAddItemToTable(itemid, merge_invoice, is_existing);
        setTimeout(() => {
            applyGroupDiscountToLastItemRow();
        }, 100); // Delay to ensure row is rendered
    };

    // Trigger customer group fetch on load
    $('#clientid').trigger('change');
});
</script>
</body>

</html>
