<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade proposal-convert-modal" id="convert_to_invoice" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-xxl" role="document">
        <?php echo form_open('admin/proposals/convert_to_invoice/' . $proposal->id, ['id' => 'proposal_convert_to_invoice_form', 'class' => '_transaction_form invoice-form']); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" onclick="close_modal_manually('#convert_to_invoice')" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="myModalLabel">
                    <span class="edit-title"><?php echo _l('proposal_convert_to_invoice'); ?></span>
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <?php $this->load->view('admin/invoices/invoice_template'); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default invoice-form-submit save-as-draft transaction-submit">
                    <?php echo _l('save_as_draft'); ?>
                </button>
                <button class="btn btn-primary invoice-form-submit transaction-submit">
                    <?php echo _l('submit'); ?>
                </button>
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
    init_tags_inputs();
    init_datepicker();
    init_color_pickers();
    init_items_sortable();
    validate_invoice_form('#proposal_convert_to_invoice_form');
    <?php if ($proposal->assigned != 0) { ?>
     $('#convert_to_invoice #sale_agent').selectpicker('val',<?php echo e($proposal->assigned); ?>);
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
        $('#convert_to_invoice #clientid').change();
    <?php } else { ?>
        $('#convert_to_invoice select#currency').val("<?php echo $proposal->currency ?>").trigger('change');
        init_ajax_project_search_by_customer_id('select#project_id');
    <?php } ?>
    // Trigger item select width fix
    $('#convert_to_invoice').on('shown.bs.modal', function(){
        $('#item_select').trigger('change')
    })
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
                      $('#additional_discount_allowed').val(data.additional_discount_allowed);
                       $('#special_discount_allowed').val(data.special_discount_allowed || 0);
                    $('#offer_discount_allowed').val(data.offer_discount_allowed || 0);
                    // =============================
                    // SPECIAL DISCOUNT CONTROL
                    // =============================
                    const $special = $('#special_discount_percent');

                    if (data.special_discount_allowed == 1) {
                        $special.prop('disabled', false);
                        $('#special_discount_area').removeClass('row-disabled');
                    } else {
                        $special.val(0).prop('disabled', true);
                        $('#special_discount_area').addClass('row-disabled');
                    }

                    // =============================
                    // OFFER DISCOUNT CONTROL
                    // =============================
                    const $offer = $('#offer_discount_input');

                    if (data.offer_discount_allowed == 1) {
                        $offer.prop('readonly', false);
                        $('#offer_discount_area').removeClass('row-disabled');
                    } else {
                        $offer.val(0).prop('readonly', true);
                        $('#offer_discount_area').addClass('row-disabled');
                    }

                    calculate_total();
                },
                error: function() {
                    $('#customer_group_name').text('—');
                    $('#customer_group_discount').text('0%');
                    globalCustomerGroupDiscount = 0;

                    $('#basic_discount_percent').val(0).trigger('input');
                    $('#customer_group_id').val(null);
                    $('#quantity_discount_allowed').val(0);
                        $('#additional_discount_allowed').val(0);
                           $('#special_discount_allowed').val(0);
                    $('#offer_discount_allowed').val(0);
                    // Disable special
                    $('#special_discount_percent')
                        .val(0)
                        .prop('disabled', true);
                    $('#special_discount_area').addClass('row-disabled');

                    // Disable offer
                    $('#offer_discount_input')
                        .val(0)
                        .prop('readonly', true);
                    $('#offer_discount_area').addClass('row-disabled');

                    calculate_total();
                }
            });

        }

    }, 200);
</script>
<script>
$(document).ready(function () {

    function copyBillingToShipping() {
        $('#shipping_street').val($('#billing_street').val());
        $('#shipping_city').val($('#billing_city').val());
        $('#shipping_state').val($('#billing_state').val());
        $('#shipping_zip').val($('#billing_zip').val());
        $('#shipping_country').val($('#billing_country').val()).change();
    }

    // When checkbox is checked
    $('#same_as_billing').on('change', function () {

        if ($(this).is(':checked')) {

            copyBillingToShipping();

            // Disable shipping fields
            $('#shipping_details')
                .find('input, textarea, select')
                .not('#same_as_billing, #show_shipping_on_estimate')
                .prop('readonly', true)
                .prop('disabled', true);

        } else {

            // Enable shipping fields
            $('#shipping_details')
                .find('input, textarea, select')
                .not('#same_as_billing, #show_shipping_on_estimate')
                .prop('readonly', false)
                .prop('disabled', false);
        }

    });

});
</script>