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
$(function() {

    console.log("Estimates Filters JS Loaded");

    // ---------------------------------------------
    //  FILTER CHANGE HANDLER
    // ---------------------------------------------
    $('[data-action="filter_estimates"]').on('change', function() {

        console.log("=== FILTER CHANGE EVENT ===");

        let numberVal      = $('[name="filter_number[]"]').val();
        let prodVal        = $('[name="filter_production_assigned[]"]').val();
        let statusVal      = $('[name="filter_status[]"]').val();
        let clientVal      = $('[name="filter_client[]"]').val();

        console.log("Selected Filters:", {
            number: numberVal,
            production: prodVal,
            status: statusVal,
            client: clientVal
        });

        // ---------------------------------------------
        //  UPDATE HIDDEN INPUTS (Perfex requirement)
        // ---------------------------------------------
        $('input[name="filter_number"]').val(numberVal ? numberVal.join(',') : '');
        $('input[name="filter_production_assigned"]').val(prodVal ? prodVal.join(',') : '');
        $('input[name="filter_status"]').val(statusVal ? statusVal.join(',') : '');
        $('input[name="filter_client"]').val(clientVal ? clientVal.join(',') : '');

        console.log("Hidden Inputs Updated:", {
            number: $('input[name="filter_number"]').val(),
            production: $('input[name="filter_production_assigned"]').val(),
            status: $('input[name="filter_status"]').val(),
            client: $('input[name="filter_client"]').val()
        });

        // ---------------------------------------------
        //  RELOAD TABLE
        // ---------------------------------------------
        console.log("Reloading Estimates DataTable...");
        $('.table-estimates').DataTable().ajax.reload();
    });


    // ---------------------------------------------
    // DEBUG: SHOW POST DATA SENT TO SERVER
    // ---------------------------------------------
    $('.table-estimates')
        .on('preXhr.dt', function (e, settings, data) {
            console.log("=== POST SENT TO SERVER ===");
            console.log(data);  // You MUST see your filters here!
        })
        .on('xhr.dt', function (e, settings, json) {
            console.log("=== SERVER RESPONSE ===");
            console.log(json);
        });

});
</script>




</body>

</html>