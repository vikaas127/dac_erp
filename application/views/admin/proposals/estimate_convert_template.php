<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade proposal-convert-modal" id="convert_to_estimate" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-xxl" role="document">
        <?php echo form_open('admin/proposals/convert_to_estimate/' . $proposal->id, ['id' => 'proposal_convert_to_estimate_form', 'class' => '_transaction_form disable-on-submit']); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" onclick="close_modal_manually('#convert_to_estimate')" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="myModalLabel">
                    <span class="edit-title"><?php echo _l('proposal_convert_to_estimate'); ?></span>
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <?php $this->load->view('admin/estimates/estimate_template'); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" onclick="close_modal_manually('#convert_to_estimate')">
                    <?php echo _l('close'); ?>
                </button>
                <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
            </div>
        </div>
        <?php echo form_close(); ?>
    </div>
</div>
<?php $this->load->view('admin/invoice_items/item'); ?>
<script>
    init_ajax_search('customer','#clientid.ajax-search');
    init_ajax_search('items','#item_select.ajax-search',undefined,admin_url+'items/search');
    custom_fields_hyperlink();
    init_selectpicker();
    init_datepicker();
    init_color_pickers();
    init_items_sortable();
    init_tags_inputs();
    validate_estimate_form('#proposal_convert_to_estimate_form');
    <?php if ($proposal->assigned != 0) { ?>
    $('#convert_to_estimate #sale_agent').selectpicker('val',<?php echo e($proposal->assigned); ?>);
    <?php } ?>
    $('select[name="discount_type"]').selectpicker('val','<?php echo e($proposal->discount_type); ?>');
    $('input[name="discount_percent"]').val('<?php echo e($proposal->discount_percent); ?>');
    $('input[name="discount_total"]').val('<?php echo e($proposal->discount_total); ?>');
    <?php if (is_sale_discount($proposal, 'fixed')) { ?>
        $('.discount-total-type.discount-type-fixed').click();
    <?php } ?>
    $('input[name="adjustment"]').val('<?php echo e($proposal->adjustment); ?>');
         $('input[name="additional_discount"]').val('<?php echo e($proposal->additional_discount); ?>');
    $('input[name="show_quantity_as"][value="<?php echo e($proposal->show_quantity_as); ?>"]').prop('checked',true).change();
    <?php if (!isset($project_id) || !$project_id) { ?>
        $('#convert_to_estimate #clientid').change();
    <?php } else { ?>
        init_ajax_project_search_by_customer_id('select#project_id')
    <?php } ?>
    // Trigger item select width fix
   $('#convert_to_estimate').on('shown.bs.modal', function(){

    $('#item_select').trigger('change');

    // ⭐ NEW CODE — fetch customer group info on modal load
    setTimeout(function () {

        var customerId = $('#clientid').val();

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

                    updateSpecialDiscountOptions(data.group_id);

                    $('#quantity_discount_allowed').val(data.quantity_discount_allowed);
                    applyAdditionalDiscountPermission(data.additional_discount_allowed);
                },
                error: function() {
                    $('#customer_group_name').text('—');
                    $('#customer_group_discount').text('0%');
                    globalCustomerGroupDiscount = 0;

                    $('#basic_discount_percent').val(0).trigger('input');
                    $('#customer_group_id').val(null);
                    $('#quantity_discount_allowed').val(0);
                       applyAdditionalDiscountPermission(0);
                }
            });

        }

    }, 200); // delay so ajax-search/selectpicker is ready

});
function applyAdditionalDiscountPermission(isAllowed) {
    var $input = $('input[name="additional_discount"]');

    if (parseInt(isAllowed) === 1) {
        $input.prop('readonly', false);
        $('#additional_discount_allowed').val(1);
    } else {
        $input.val(0).prop('readonly', true);
        $('#additional_discount_allowed').val(0);
    }

    calculate_total();
}

function updateSpecialDiscountOptions(groupId) {

    const $dropdown = $('#special_discount_percent');

    // Get selected value:
    // 1) From data-selected (proposal convert)
    // 2) From existing dropdown (estimate edit)
    let selected = $dropdown.data('selected');
    if (selected === undefined || selected === null || selected === '') {
        selected = $dropdown.val();
    }
    selected = parseInt(selected);

    let optionsHtml = `<option value="">— Select —</option>`;

    let start = 0, end = 5;

    // Groups 4 and 7 allow 1–10
    if (groupId == 4 || groupId == 7) {
        start = 1;
        end = 10;
    }

    for (let i = start; i <= end; i++) {
        const isSelected = (i === selected) ? 'selected' : '';
        optionsHtml += `<option value="${i}" ${isSelected}>${i}%</option>`;
    }

    $dropdown.html(optionsHtml);

    // Restore selected value
    if (selected >= start && selected <= end) {
        $dropdown.val(selected);
    }

    // Recalculate totals
    if (typeof calculate_total !== 'undefined') {
        calculate_total();
    }
}

$('body').on('change', '#clientid', function () {

    var customerId = $(this).val();
    if (!customerId) {
        applyAdditionalDiscountPermission(0);
        return;
    }

    $.get(
        admin_url + 'estimates/get_customer_group_info/' + customerId,
        function (data) {
            applyAdditionalDiscountPermission(data.additional_discount_allowed);
        },
        'json'
    ).fail(function () {
        applyAdditionalDiscountPermission(0);
    });
});


</script>
