<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">

            <!-- FILTER BAR -->
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">

                        <div class="row mbot15">

                            <!-- Quick Date -->
                            <div class="col-md-3 mbot10">
                             <select id="quick_date" name="quick_date" class="form-control">
    <option value="">All Time</option>
    <option value="today">Today</option>
    <option value="yesterday">Yesterday</option>
    <option value="week">Last 7 Days</option>
    <option value="month">Last 1 Month</option>
    <option value="year">Last 1 Year</option>
    <option value="custom">Custom Date</option>
</select>

                            </div>

                           
                          <!-- Date Range -->
<div class="col-md-4 mbot10" id="customDateRange" style="display:none;">
    <div class="input-group">
        <input type="date" name="from_date" id="from_date" class="form-control">
        <span class="input-group-addon">to</span>
        <input type="date" name="to_date" id="to_date" class="form-control">
    </div>
</div>


                            <!-- Item -->
                            <div class="col-md-3 mbot10">
                                <select id="item_id" name="item_id" class="form-control selectpicker" data-live-search="true">
                                    <option value="">All Items</option>
                                    <?php foreach ($items as $item) { ?>
                                        <option value="<?= $item['itemid']; ?>">
                                            <?= $item['description']; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-3 mbot10 ">
    <select id="sale_agent" name="sale_agent" class="form-control selectpicker" data-live-search="true">
        <option value="">All Sales Agents</option>
        <?php foreach ($staff as $agent) { ?>
            <option value="<?= $agent['staffid']; ?>">
                <?= $agent['firstname'] . ' ' . $agent['lastname']; ?>
            </option>
        <?php } ?>
    </select>
</div>
<div class="col-md-3 mbot10">
    <select id="production_filter" name="production_filter" class="form-control">
        <option value="">All Production</option>
        <option value="not_started">Not Started</option>
        <option value="assigned">Assigned</option>
        <option value="completed">Completed</option>
    </select>
</div>
<div class="col-md-3 mbot10">
    <select id="stock_filter" name="stock_filter" class="form-control">
        <option value="">All Stock</option>
        <option value="in_stock">In Stock</option>
        <option value="short">Short</option>
    </select>
</div>


                            <!-- Apply -->
                            <div class="col-md-2">
                                <button id="applyFilters" class="btn btn-primary btn-block">
                                    Apply
                                </button>
                            </div>

                        </div>
                       <div class="row mbot15" id="dashboardCards">

    <div class="col-md-2">
        <div class="panel_s text-center">
            <div class="panel-body">
                <h4 id="cardOrdered">0</h4>
                <span class="text-muted">Ordered</span>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="panel_s text-center">
            <div class="panel-body">
                <h4 id="cardDelivered">0</h4>
                <span class="text-muted">Delivered</span>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="panel_s text-center">
            <div class="panel-body">
                <h4 id="cardRemaining">0</h4>
                <span class="text-muted">Remaining</span>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="panel_s text-center">
            <div class="panel-body">
                <h4 id="cardInProduction">0</h4>
                <span class="text-muted">In Production</span>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="panel_s text-center">
            <div class="panel-body">
                <h4 id="cardAvailable">0</h4>
                <span class="text-muted">Available</span>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="panel_s text-center">
            <div class="panel-body">
                <h4 id="cardNeedAssign">0</h4>
                <span class="text-muted">Need to Assign</span>
            </div>
        </div>
    </div>

</div>


                        <?php
                        render_datatable([
                            'Estimate No',
                            'Date',
                            'Status',  
                            'Item',
                            'Order Qty',
                            'Delivered Qty',
                            'Available Qty',
                            
                                    
     
                            'Stock Status',
                            'Production Status',
                            'Sales Agent',
                        ], 'sales-orders-dashboard');
                        ?>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php init_tail(); ?>
<script>
$(function () {

  var serverParams = {
    quick_date: '[name="quick_date"]',
    from_date:  '[name="from_date"]',
    to_date:    '[name="to_date"]',
    item_id:    '[name="item_id"]',
    sale_agent: '[name="sale_agent"]',        
    production_filter: '[name="production_filter"]',
        stock_filter: '[name="stock_filter"]'
};


   let table = initDataTable(
    '.table-sales-orders-dashboard',
    admin_url + 'estimates/sales_orders_dashboard',
    undefined,
    undefined,
    serverParams
);

// Apply default order AFTER init (safe)
table.order([0, 'desc']).draw();


   table.on('xhr.dt', function (e, settings, json) {
    if (!json || !json.cards) return;

    $('#cardOrdered').text(json.cards.ordered);
    $('#cardDelivered').text(json.cards.delivered);
    $('#cardRemaining').text(json.cards.remaining);
    $('#cardInProduction').text(json.cards.in_production);
    $('#cardAvailable').text(json.cards.available);
    $('#cardNeedAssign').text(json.cards.need_assign);
});


    $('#quick_date').on('change', function () {
        if ($(this).val() === 'custom') {
            $('#customDateRange').slideDown(150);
        } else {
            $('#customDateRange').slideUp(150);
            $('#from_date, #to_date').val('');
        }
    });

    $('#from_date, #to_date').on('change', function () {
        $('#quick_date').val('custom');
        $('#customDateRange').slideDown(150);
    });

    $('#applyFilters').on('click', function () {
        table.ajax.reload();
    });

});


</script>
