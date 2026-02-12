<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-mb-2 sm:tw-mb-4">
                    <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#customer_group_modal">
                        <i class="fa-regular fa-plus tw-mr-1"></i>
                        <?php echo _l('new_customer_group'); ?>
                    </a>
                </div>

                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <?php render_datatable([
    _l('customer_group_name'),
    _l('default_discount'),
    _l('default_profit_margin'),
    _l('allow_item_level_override'),
    _l('allow_quantity_discount'),
        _l('allow_special_discount'),

            _l('allow_offer_discount'),

    _l('allow_additional_discount'),
    _l('options'),
], 'customer-groups'); ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->load->view('admin/clients/client_group'); ?>
<?php init_tail(); ?>
<script>
$(function() {
    initDataTable('.table-customer-groups', window.location.href, [8], [8]);
});
</script>
</body>

</html>