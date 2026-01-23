<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="panel-table-full">
                <div id="">
                    <?php $this->load->view('admin/estimates/list_template'); ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->load->view('admin/includes/modals/sales_attach_file'); ?>
<script>
var hidden_columns = [2, 5, 6, 8, 9];
</script>
<?php init_tail(); ?>
<script>
$(function() {
    init_estimate();
});
</script>





<script>
$('.table-estimates').on('preXhr.dt', function (e, settings, data) {
    data.filter_number = $('#filter_number').val();
    data.filter_production_assigned_to = $('#filter_production_assigned_to').val();
    data.filter_status = $('#filter_status').val();
    data.filter_customer = $('#filter_customer').val();
});
$('#filter_number, #filter_production_assigned_to, #filter_status, #filter_customer').on('change', function() {
    $('.table-estimates').DataTable().ajax.reload();
});
</script>




</body>

</html>