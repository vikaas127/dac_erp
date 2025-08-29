<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="clearfix"></div>

        <div class="row mbot20">
            <div class="col-md-12">
                <a class="btn btn-primary" href="<?php echo admin_url('promo_codes/create'); ?>">
                    <i class="fa-regular fa-plus tw-mr-1"></i> <?php echo _l('promo_codes_create_new'); ?>
                </a>
                <a class="btn btn-default tw-mr-2" href="<?php echo admin_url('promo_codes/clients_export'); ?>">
                    <i class="fa fa-file-export tw-mr-1"></i> <?php echo _l('promo_codes_export_clients'); ?>
                </a>
            </div>
        </div>

        <div class="panel_s">
            <div class="panel-body">
                <?php
                $table_data = [
                    _l('promo_codes_code'),
                    _l('promo_codes_type'),
                    _l('promo_codes_value'),
                    _l('promo_codes_usage_limit'), // this now includes "used"
                    _l('promo_codes_validity_period'),
                    _l('promo_codes_status'),
                    _l('promo_codes_actions'),
                ];
                render_datatable($table_data, 'promo-codes');
                ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>

<script>
$(function() {
    initDataTable('.table-promo-codes', admin_url + 'promo_codes/table', undefined, undefined);
});
</script>
</body>

</html>