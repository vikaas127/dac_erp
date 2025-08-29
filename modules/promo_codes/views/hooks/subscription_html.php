<div class="modal fade" id="promoCodeModal" tabindex="-1" role="dialog" aria-labelledby="promoCodeModalLabel">
    <div class="modal-dialog modal-fullscreen" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title pull-left"><?php echo _l('promo_codes_apply_to_subscription_heading'); ?></h4>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <div class="input-group">
                        <input type="text" id="promoCodeInput" class="form-control"
                            placeholder="<?= _l('promo_codes_promo_code_placeholder') ?>">
                        <span class="input-group-btn">
                            <a href="#" class="btn btn-default" id="addPromoCodeBtn">
                                <i class="fa fa-plus"></i>
                            </a>
                        </span>
                    </div>
                </div>

                <div id="promoCodeAlert" class="alert alert-danger tw-hidden"></div>

                <ul id="appliedCodesList" class="list-group tw-mt-3"></ul>
            </div>

            <div class="modal-footer">
                <div class="tw-flex tw-full tw-justify-between">
                    <button type="button" id="continueWithoutPromoBtn" class="btn btn-success">
                        <?php echo _l('subscribe'); ?>
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <?php echo _l('cancel'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
'use strict';
$(function() {
    if (!$('body').hasClass('subscriptionhtml')) return;

    // Toggle this to enable/disable single-code mode. 
    // Set to true as default since stripe allow only one coupon to a subscription
    const singleCodeMode = true;

    $('button#subscribe-button').removeClass('btn-success').addClass('btn-primary');

    const subscriptionText = $('.subscription-number').text();
    const idMatch = subscriptionText.match(/(\d+)\s*$/);
    if (!idMatch) return;

    const salesObjectType = 'subscription';
    const salesObjectId = idMatch[1];

    const $promoCodeInput = $('#promoCodeInput');
    const $promoCodeAlert = $('#promoCodeAlert');
    const $appliedCodesList = $('#appliedCodesList');
    const $sourceForm = $('#sourceForm');
    const $addPromoCodeBtn = $('#addPromoCodeBtn');
    const $inputGroup = $promoCodeInput.closest('.input-group');

    function showAppliedSubscriptionCodes(subscriptionId) {
        $.get(site_url + 'promo_codes/promo_codes_client/get_subscription_discounts/' + subscriptionId,
            function(response) {
                if (response.success) {
                    const $subscriptionDate = $('p.subscription-date');
                    $subscriptionDate.next('.applied-discounts').remove(); // remove existing if any

                    if (response.discounts.length) {
                        const discountsHtml =
                            response.discounts.map(d =>
                                `<p class="applied-discounts"><span class="tw-font-medium tw-text-neutral-700"><?= _l('promo_codes_discount'); ?></span>: ${d.code} - ${d.amount} (${d.duration})</p>`
                            ).join('');

                        $subscriptionDate.after(discountsHtml);
                    }
                } else {
                    alert(response.message || 'Failed to fetch promo codes.');
                }
            }, 'json');
    }
    showAppliedSubscriptionCodes(salesObjectId);

    function showAlert(message) {
        $promoCodeAlert.text(message).removeClass('tw-hidden');
    }

    function clearAlert() {
        $promoCodeAlert.addClass('tw-hidden').text('');
    }

    function toggleInputVisibility() {
        if (singleCodeMode && $appliedCodesList.children().length >= 1) {
            $inputGroup.hide();
        } else {
            $inputGroup.show();
        }
    }

    $promoCodeInput.on('input', clearAlert);

    $('#subscribe-button').on('click', function(e) {
        e.preventDefault();
        $('#promoCodeModal').modal('show');
    });

    $addPromoCodeBtn.on('click', function(e) {
        e.preventDefault();

        const code = $promoCodeInput.val().trim();
        if (code === '') {
            showAlert('<?= _l("promo_codes_empty_message") ?>');
            return;
        }

        const $btn = $(this);
        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

        $.post("<?php echo site_url('promo_codes/promo_codes_client/validate'); ?>", {
            code: code,
            sales_object_id: salesObjectId,
            sales_object_type: salesObjectType
        }, function(response) {
            if (response.success) {
                const item = $(
                    '<li class="list-group-item tw-flex tw-justify-between tw-items-center"></li>'
                );
                item.html(`<div>${response.html}</div>`);

                const removeBtn = $(
                    '<button type="button" class="btn btn-sm btn-danger"><i class="fa fa-times"></i></button>'
                );
                removeBtn.on('click', function() {
                    item.remove();
                    $sourceForm.find('input[value="' + code + '"]').remove();
                    toggleInputVisibility();
                });

                item.append(removeBtn);
                $appliedCodesList.append(item);

                $('<input>').attr({
                    type: 'hidden',
                    name: 'promo_codes[]',
                    value: code
                }).appendTo($sourceForm);

                $promoCodeInput.val('');
                clearAlert();
                toggleInputVisibility();
            } else {
                showAlert(response.message || '<?= _l("promo_codes_invalid_code") ?>');
            }
        }, 'json').fail(function() {
            showAlert('<?= _l("promo_codes_error_applying") ?>');
        }).always(function() {
            $btn.prop('disabled', false).html(originalHtml);
        });
    });

    $('#continueWithoutPromoBtn').on('click', function() {
        const appliedCount = $appliedCodesList.children().length;
        if (appliedCount === 0) {
            if (!confirm('<?= _l("promo_codes_confirm_continue_without_code") ?>')) {
                return;
            }
        }

        $('#promoCodeModal').modal('hide');
        $sourceForm.off('submit').submit();
    });

    // Run at start in case singleCodeMode disables inputs on open
    toggleInputVisibility();
});
</script>