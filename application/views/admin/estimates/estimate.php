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
   // validate_estimate_form();
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

// jQuery(function($) {

//     // 1. Fetch customer group discount and store globally
//     $('#clientid').on('change', function() {
//         var customerId = $(this).val();
//         if (customerId) {
//             $.ajax({
//                 url: admin_url + 'estimates/get_customer_group_info/' + customerId,
//                 type: 'GET',
//                 dataType: 'json',
//                 success: function(data) {
//                     $('#customer_group_name').text(data.group_name);
//                     $('#customer_group_discount').text(data.default_discount + '%');
//                     globalCustomerGroupDiscount = parseFloat(data.default_discount) || 0;

//                     $('#customer_group_id').val(data.group_id);
//                     updateSpecialDiscountOptions(data.group_id);

//                     $('#quantity_discount_allowed').val(data.quantity_discount_allowed);
//                     $('#special_discount_allowed').val(data.special_discount_allowed);

//                     $('#offer_discount_allowed').val(data.offer_discount_allowed);

//                     $('#additional_discount_allowed').val(data.additional_discount_allowed);

//                     const $additionalInput = $('#additional_discount_input');

//                     if (data.additional_discount_allowed == 1) {
//                         // ✅ allow editing
//                         $additionalInput.prop('readonly', false);
//                     } else {
//                         // ❌ not allowed → clear & lock
//                         $additionalInput.val(0).prop('readonly', true);
//                         calculate_total();
//                     }

//                     // =============================
//                     // SPECIAL DISCOUNT CONTROL
//                     // =============================
//                     const $specialDropdown = $('#special_discount_percent');

//                     if (data.special_discount_allowed == 1) {
//                         $specialDropdown.prop('disabled', false);
//                         $('#special_discount_area').removeClass('row-disabled');
//                     } else {
//                         $specialDropdown.val(0).prop('disabled', true);
//                         $('#special_discount_area').addClass('row-disabled');
//                     }


//                     // =============================
//                     // OFFER DISCOUNT CONTROL
//                     // =============================
//                     const $offerInput = $('#offer_discount_input');

//                     if (data.offer_discount_allowed == 1) {
//                         $offerInput.prop('readonly', false);
//                         $('#offer_discount_area').removeClass('row-disabled');
//                     } else {
//                         $offerInput.val(0).prop('readonly', true);
//                         $('#offer_discount_area').addClass('row-disabled');
//                     }

//                     // Always recalculate after applying restrictions
//                     calculate_total();

//                 },

//                 error: function() {
//                     $('#customer_group_name').text('—');
//                     $('#customer_group_discount').text('0%');
//                     globalCustomerGroupDiscount = 0;
//                     $('#customer_group_id').val(null);
//                     $('#quantity_discount_allowed').val(0);
//                     $('#additional_discount_allowed').val(0);
//                     $('#special_discount_percent').val(0).prop('disabled', true);
//                     $('#special_discount_area').addClass('row-disabled');

//                     // Disable offer
//                     $('#offer_discount_input').val(0).prop('readonly', true);
//                     $('#offer_discount_area').addClass('row-disabled');

//                     calculate_total();



//                 }
//             });
//         }  else {
//             $('#customer_group_name').text('—');
//             $('#customer_group_discount').text('0%');
//             globalCustomerGroupDiscount = 0;

//             $('#customer_group_id').val(null);
//             $('#quantity_discount_allowed').val(0);

//             // Disable special discount
//             $('#special_discount_percent')
//                 .val(0)
//                 .prop('disabled', true);
//             $('#special_discount_area').addClass('row-disabled');

//             // Disable offer discount
//             $('#offer_discount_input')
//                 .val(0)
//                 .prop('readonly', true);
//             $('#offer_discount_area').addClass('row-disabled');


//             $('#additional_discount_input')
//                 .val(0)
//                 .prop('readonly', true);

//             calculate_total();
//         }

//     });
//     // Trigger customer group fetch on load
//     $('#clientid').trigger('change');
//     });
function updateSpecialDiscountOptions(groupId) {
    const $dropdown = $('#special_discount_percent');
    const selected = parseInt($dropdown.val()) || '';
    let optionsHtml = '';

    // Add first empty option
    optionsHtml += `<option value="">— Select —</option>`;

    // Default range
    let start = 0, end = 5;

    // For customer group 4 or 7 → 1–10
    if (groupId == 4 || groupId == 7) {
        start = 1;
        end = 10;
    }

    // Build options dynamically
    for (let i = start; i <= end; i++) {
        optionsHtml += `<option value="${i}" ${i == selected ? 'selected' : ''}>${i}%</option>`;
    }

    // Update dropdown
    $dropdown.html(optionsHtml);

    // Trigger total recalculation
    if (typeof calculate_total !== 'undefined') {
        calculate_total();
    }
}

function loadCustomerGroupData(customerId) {
    if (!customerId) return;

    $.ajax({
        url: admin_url + 'estimates/get_customer_group_info/' + customerId,
        type: 'GET',
        dataType: 'json',
        success: function(data) {

            $('#customer_group_name').text(data.group_name);
            $('#customer_group_discount').text(data.default_discount + '%');
            globalCustomerGroupDiscount = parseFloat(data.default_discount) || 0;

            $('#customer_group_id').val(data.group_id);
            updateSpecialDiscountOptions(data.group_id);

            $('#quantity_discount_allowed').val(data.quantity_discount_allowed);
            $('#special_discount_allowed').val(data.special_discount_allowed);
            $('#offer_discount_allowed').val(data.offer_discount_allowed);
            $('#additional_discount_allowed').val(data.additional_discount_allowed);

            const $additionalInput = $('#additional_discount_input');

            if (data.additional_discount_allowed == 1) {
                $additionalInput.prop('readonly', false);
            } else {
                $additionalInput.val(0).prop('readonly', true);
            }

            // Special discount
            const $specialDropdown = $('#special_discount_percent');
            if (data.special_discount_allowed == 1) {
                $specialDropdown.prop('disabled', false);
                $('#special_discount_area').removeClass('row-disabled');
            } else {
                $specialDropdown.val(0).prop('disabled', true);
                $('#special_discount_area').addClass('row-disabled');
            }

            // Offer discount
            const $offerInput = $('#offer_discount_input');
            if (data.offer_discount_allowed == 1) {
                $offerInput.prop('readonly', false);
                $('#offer_discount_area').removeClass('row-disabled');
            } else {
                $offerInput.val(0).prop('readonly', true);
                $('#offer_discount_area').addClass('row-disabled');
            }

            calculate_total();
        }
    });
}
$(document).ready(function () {

    var customerId = $('#clientid').val();

    if (customerId) {
        //  THIS loads discount WITHOUT clearing billing/shipping
        loadCustomerGroupData(customerId);
    }

});
$('#clientid').on('change', function() {
    loadCustomerGroupData($(this).val());
});
// function calculate_total() {
//     if ($("body").hasClass("no-calculate-total")) {
//         return !1
//     }
//     var rows = $(".table.has-calculations tbody tr.item");
//     var subtotal = 0;
//     var totalBeforeQty = 0;
//     var totalAfterQty = 0;
//     var specialDiscountTotal = 0;
//     var offerDiscountTotal = 0;
//     var quantityDiscountTotal = 0;
//     var specialPercent = parseFloat($("#special_discount_percent").val()) || 0;
//     var offerPercent = parseFloat($("#offer_discount_percent").val()) || 0;
//     var adjustment = parseFloat($('input[name="adjustment"]').val()) || 0;
//     $(".tax-area").remove();
//     rows.each(function() {
//         var row = $(this);
//         var qty = parseFloat(row.find("[data-quantity]").val()) || 1;
//         var rate = parseFloat(row.find("td.rate input").val()) || 0;
//         var base = qty * rate;
//         subtotal += base;
//         var originalPercent = parseFloat(row.find('input[name$="[item_original_discount_percent]"]').val()) || 0;
//         var afterOriginal = base * (1 - originalPercent / 100);
//         var specialAmt = afterOriginal * (specialPercent / 100);
//         var afterSpecial = afterOriginal - specialAmt;
//         var offerAmt = afterSpecial * (offerPercent / 100);
//         var afterOffer = afterSpecial - offerAmt;
//         specialDiscountTotal += specialAmt;
//         offerDiscountTotal += offerAmt;
//         totalBeforeQty += afterOffer;
//         row.data("base", base);
//         row.data("amountBeforeQty", afterOffer)
//     });
//     var quantityPercent = 0;
//     if ($("#quantity_discount_allowed").val() === "1") {
//         if (totalBeforeQty > 416800)
//             quantityPercent = 4;
//         else if (totalBeforeQty > 309300)
//             quantityPercent = 3;
//         else if (totalBeforeQty > 204100)
//             quantityPercent = 2
//     }
//     quantityDiscountTotal = totalBeforeQty * (quantityPercent / 100);
//     totalAfterQty = totalBeforeQty - quantityDiscountTotal + adjustment;
//     rows.each(function() {
//         var row = $(this);
//         var qty = parseFloat(row.find("[data-quantity]").val()) || 1;
//         var rate = parseFloat(row.find("td.rate input").val()) || 0;
//         var base = row.data("base");
//         var amountBeforeQty = row.data("amountBeforeQty");
//         var shareRatio = totalBeforeQty > 0 ? amountBeforeQty / totalBeforeQty : 0;
//         var qtyShare = quantityDiscountTotal * shareRatio;
//         var finalAmount = amountBeforeQty - qtyShare;
//         var effectivePercent = base > 0 ? 100 * (1 - finalAmount / base) : 0;
//         var roundedEffectivePercent = accounting.toFixed(effectivePercent, 2);
//         row.find('input[name$="[item_discount_percent]"]').val(roundedEffectivePercent);
//         var unitPrice = rate * (1 - roundedEffectivePercent / 100);
//         unitPrice = parseFloat(accounting.toFixed(unitPrice, 2));
//         row.find('input[name$="[item_price]"]').val(unitPrice);
//         var roundedAmount = parseFloat(accounting.toFixed(unitPrice * qty, 2));
//         row.find("td.amount").html(format_money(roundedAmount, !0))
//     });
//     $(".subtotal").html(format_money(subtotal, !0) + hidden_input("subtotal", accounting.toFixed(subtotal, 2)));
//     $(".total").html(format_money(totalAfterQty, !0) + hidden_input("total", accounting.toFixed(totalAfterQty, 2)));
//     $("#special_discount_area .discount-total").html("-" + format_money(specialDiscountTotal || 0, !0));
//     $("#offer_discount_area .discount-total").html("-" + format_money(offerDiscountTotal || 0, !0));
//     $("#special_discount_total").val(accounting.toFixed(specialDiscountTotal || 0, 2));
//     $("#offer_discount_total").val(accounting.toFixed(offerDiscountTotal || 0, 2));
//     $("#quantity_discount_percent").val(quantityPercent);
//     $("#quantity_discount_total").val(accounting.toFixed(quantityDiscountTotal || 0, 2));
//     $("#quantity_discount_area .discount-total").html("-" + format_money(quantityDiscountTotal || 0, !0));
//     $(".adjustment").html(format_money(adjustment));
//     $(document).trigger("sales-total-calculated")
// }
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

    $('#same_as_billing').on('change', function () {

        if ($(this).is(':checked')) {

            copyBillingToShipping();

            // Make shipping fields readonly (NOT disabled)
            $('#shipping_street, #shipping_city, #shipping_state, #shipping_zip')
                .prop('readonly', true);

            $('#shipping_country')
                .prop('readonly', true); // for select, readonly works visually

        } else {

            // Enable editing again
            $('#shipping_street, #shipping_city, #shipping_state, #shipping_zip')
                .prop('readonly', false);

            $('#shipping_country')
                .prop('readonly', false);
        }
    });

});
</script>
</body>

</html>
