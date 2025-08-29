<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="mtop15 pull-right">
    <strong><?= _l('promo_codes_applied_promo_codes'); ?></strong>
    <ul class="promo-codes-list tw-pl-0 tw-mt-1">
        <?php foreach ($applied as $entry) : ?>
        <li class="tw-flex tw-w-full tw-justify-between">
            <span class="badge badge-info">
                <?= html_escape($entry['code']); ?>
            </span>
            <span><?= ' - '; ?></span>
            <span>
                <?= ($entry['type'] === 'percentage' ? $entry['amount'] . '%' : app_format_money($entry['amount'], $currency)); ?>
            </span>
            <a href="#" class="_delete remove-applied-code text-danger mleft5" data-code="<?= $entry['code']; ?>"
                title="<?= _l('promo_codes_remove_this_code'); ?>">
                <i class="fa fa-times"></i>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</div>

<script>
'use strict';
document.addEventListener("DOMContentLoaded", function() {
    $(document).on('click', '.remove-applied-code', function(e) {
        e.preventDefault();
        let $btn = $(this);
        let code = $btn.data('code');

        const salesObjectType = '<?= $sales_object_type; ?>';
        const salesObjectId = '<?= $sales_object_id; ?>';

        // Prevent double-click and show loading spinner
        if ($btn.hasClass('disabled')) return;

        $btn.addClass('disabled');
        let originalIcon = $btn.find('i').attr('class');
        $btn.find('i').attr('class', 'fa fa-spinner fa-spin');

        $.post('<?= site_url("promo_codes/promo_codes_client/remove") ?>', {
            code: code,
            sales_object_id: salesObjectId,
            sales_object_type: salesObjectType
        }).done(function(response) {
            if (response.success) {
                let $li = $btn.closest('li');
                let $list = $li.closest('.promo-codes-list');
                window.location.reload();
            } else {
                alert(response.message || '<?= e(_l('promo_codes_failed_to_remove_code')); ?>');
                $btn.removeClass('disabled').find('i').attr('class', originalIcon);
            }
        }).fail(function() {
            alert('<?= e(_l('promo_codes_failed_to_remove_code')); ?>');
            $btn.removeClass('disabled').find('i').attr('class', originalIcon);
        });
    });
});
</script>