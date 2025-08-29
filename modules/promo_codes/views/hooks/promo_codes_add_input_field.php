<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="col-md-6 col-md-offset-6">
    <hr />
    <div class="tw-w-full promo-code-wrapper pull-right">
        <div class="tw-flex tw-justify-end tw-gap-2">
            <input type="text" id="promo-code-input" class="form-control"
                placeholder="<?= _l('promo_codes_promo_code_placeholder') ?>">
            <button type="button" class="btn btn-secondary" id="promo-code-apply-btn">
                <i class="fa fa-check" id="promo-code-btn-icon"></i>
                <?= _l('promo_codes_apply') ?>
            </button>
        </div>
        <div id="promo-code-feedback" class="mtop10"></div>
    </div>
</div>

<script>
'use strict';

document.addEventListener("DOMContentLoaded", function() {
    const $promoInput = $('#promo-code-input');
    const $applyBtn = $('#promo-code-apply-btn');
    const $btnIcon = $('#promo-code-btn-icon');
    const $feedback = $('#promo-code-feedback');

    const salesObjectType = '<?= e($sales_object_type); ?>';
    const salesObjectId = '<?= (int)$sales_object_id; ?>';

    /**
     * Toggle loading state for the apply button.
     * @param {boolean} isLoading
     */
    function setLoadingState(isLoading) {
        $applyBtn.prop('disabled', isLoading);
        $btnIcon
            .toggleClass('fa-check', !isLoading)
            .toggleClass('fa-spinner fa-spin', isLoading);
    }

    /**
     * Show feedback alert.
     * @param {'success' | 'danger' | 'warning'} type
     * @param {string} message
     */
    function showFeedback(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible">
                ${message}
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>`;
        $feedback.html(alertHtml);
    }

    /**
     * Apply promo code via AJAX.
     */
   /* function applyPromoCode() {
        const code = $promoInput.val().trim();
        if (!code) {
            showFeedback('warning', '<?= e(_l("promo_codes_empty_message")) ?>');
            return;
        }

        setLoadingState(true);
        $feedback.empty();

        $.post('<?= site_url("promo_codes/promo_codes_client/apply") ?>', {
            code: code,
            sales_object_id: salesObjectId,
            sales_object_type: salesObjectType
        }, function(res) {
            setLoadingState(false);

            if (res.success) {
                window.location.reload();
            } else {
                showFeedback('danger', res.message);
            }

            if (res.redirect && res.redirect.length) {
                setTimeout(() => {
                    window.location = res.redirect;
                }, 5000);
            }

        }, 'json').fail(function() {
            setLoadingState(false);
            showFeedback('danger', '<?= e(_l("promo_codes_error_applying")) ?>');
        });
    }*/
    function applyPromoCode() {
    const code = $promoInput.val().trim();

    // Log the code and target object before sending the request
    console.log('Applying promo code:', code,
                'for type:', salesObjectType,
                'id:', salesObjectId);

    if (!code) {
        showFeedback('warning', '<?= e(_l("promo_codes_empty_message")) ?>');
        return;
    }

    setLoadingState(true);
    $feedback.empty();

    $.post('<?= site_url("promo_codes/promo_codes_client/apply") ?>', {
        code: code,
        sales_object_id: salesObjectId,
        sales_object_type: salesObjectType
    }, function(res) {
        setLoadingState(false);

        // Log the response received from the server
        console.log('Apply promo code response:', res);

        if (res.success) {
            window.location.reload();
        } else {
            showFeedback('danger', res.message);
        }

        if (res.redirect && res.redirect.length) {
            setTimeout(() => {
                window.location = res.redirect;
            }, 5000);
        }
    }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
        setLoadingState(false);
        // Log AJAX errors as well
        console.error('Error applying promo code:', textStatus, errorThrown);
        showFeedback('danger', '<?= e(_l("promo_codes_error_applying")) ?>');
    });
}


    $applyBtn.on('click', applyPromoCode);

    $promoInput.on('keypress', function(e) {
        if (e.which === 13) {
            applyPromoCode();
        }
    });

    $promoInput.on('input', function() {
        $feedback.empty();
    });
});
</script>