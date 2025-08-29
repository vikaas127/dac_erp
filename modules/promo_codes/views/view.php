<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="panel_s">
            <div class="panel-body">
                <h4><?php echo _l('promo_codes_details'); ?></h4>
                <hr>
                <p><strong><?php echo _l('promo_codes_code'); ?>:</strong> <?php echo $code->code; ?></p>
                <p><strong><?php echo _l('promo_codes_type'); ?>:</strong> <?php echo $code->type; ?></p>
                <p><strong><?php echo _l('promo_codes_value'); ?>:</strong>
                    <?php echo $code->amount . ($code->type == 'percentage' ? '%' : '$'); ?></p>
                <p><strong><?php echo _l('promo_codes_usage_limit'); ?>:</strong> <?php echo $code->usage_limit; ?></p>
                <p><strong><?php echo _l('promo_codes_validity_period'); ?>:</strong>
                    <?php echo _d($code->start_date) . ' - ' . _d($code->end_date); ?></p>

                <h4 class="mtop30"><?php echo _l('promo_codes_usage_history'); ?></h4>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th><?php echo _l('client'); ?></th>
                            <th><?php echo _l('promo_codes_type'); ?></th>
                            <th><?php echo _l('promo_codes_usage_value'); ?></th>
                            <th><?php echo _l('promo_codes_used_at'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usage as $u) : ?>
                        <tr>
                            <td><?php echo $u->company ?: $u->userid; ?></td>
                            <td>
                                <a
                                    href="<?php echo base_url('promo_codes/view_sales/' . $u->rel_type . '/' . $u->rel_id); ?>">
                                    <?php echo _l('promo_codes_applicable_' . $u->rel_type); ?>
                                </a>
                            </td>
                            <td><?php echo app_format_money($u->value, $u); ?> </td>
                            <td><?php echo _dt($u->used_at); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($usage)) : ?>
                        <tr>
                            <td colspan="2"><?php echo _l('promo_codes_no_usage'); ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <a href="<?php echo admin_url('promo_codes'); ?>"
                    class="btn btn-default"><?php echo _l('go_back'); ?></a>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>

</html>