<?php init_head(); ?>

<div id="wrapper">
<div class="content">
    <ul class="nav nav-tabs mbot20" role="tablist">
    <li class="active">
        <a href="#dashboard_tab" role="tab" data-toggle="tab">Dashboard</a>
    </li>
    <li>
        <a href="#tables_tab" role="tab" data-toggle="tab">Tables</a>
    </li>
</ul>
<div class="tab-content">

    <!-- ================= TAB 1 (Dashboard) ================= -->
    <div class="tab-pane active" id="dashboard_tab">

<h3 class="mbot20">Staff Performance Dashboard</h3>

<!-- ================= FILTER ================= -->

<form method="get" class="row mbot20">

<div class="col-md-2">
<select name="filter_type" class="form-control">
    <option value="monthly" <?= ($_GET['filter_type'] ?? '')=='monthly'?'selected':'' ?>>Monthly</option>
    <option value="weekly" <?= ($_GET['filter_type'] ?? '')=='weekly'?'selected':'' ?>>Weekly</option>
    <option value="yearly" <?= ($_GET['filter_type'] ?? '')=='yearly'?'selected':'' ?>>Yearly</option>
    <option value="custom" <?= ($_GET['filter_type'] ?? '')=='custom'?'selected':'' ?>>Custom</option>
</select>
</div>

<div class="col-md-2">
<input type="date" name="from" class="form-control" value="<?= $_GET['from'] ?? '' ?>">
</div>

<div class="col-md-2">
<input type="date" name="to" class="form-control" value="<?= $_GET['to'] ?? '' ?>">
</div>

<div class="col-md-2">
<button class="btn btn-primary">Apply</button>
</div>

</form>

<!-- ================= KPI CARDS ================= -->

<div class="row">

<div class="col-md-3">
<div class="panel_s"><div class="panel-body text-center">
<h4>Total Leads Assigned</h4>
<h2><?= $total_staff_leads ?? 0 ?></h2>
</div></div>
</div>

<div class="col-md-3">
<div class="panel_s"><div class="panel-body text-center">
<h4>Deals Won</h4>
<h2><?= $deals_won ?? 0 ?></h2>
</div></div>
</div>

<div class="col-md-3">
<div class="panel_s"><div class="panel-body text-center">
<h4>Revenue Generated</h4>
<h2>₹<?= number_format($staff_revenue ?? 0) ?></h2>
</div></div>
</div>

<div class="col-md-3">
<div class="panel_s"><div class="panel-body text-center">
<h4>Conversion Rate</h4>
<h2><?= $staff_conversion ?? 0 ?>%</h2>
</div></div>
</div>

</div>

<!-- ================= CHARTS ================= -->

<div class="row">

<div class="col-md-6">
<div class="panel_s">
<div class="panel-body">
<h4>Sales Leaderboard</h4>
<div id="leaderboardChart"></div>
</div>
</div>
</div>

<div class="col-md-6">
<div class="panel_s">
<div class="panel-body">
<h4>Monthly Staff Performance</h4>
<div id="staffTrendChart"></div>
</div>
</div>
</div>

</div>

<div class="panel_s">
<div class="panel-body">
<h4>Monthly Lead Status by Staff</h4>
<div id="staffStatusChart"></div>
</div>
</div>
</div>
<div class="tab-pane" id="tables_tab">

<!-- ================= DYNAMIC TABLE ================= -->
<form id="tableFilters" class="row mbot15">

    <!-- Staff -->
    <div class="col-md-3">
        <label>Staff</label>

        <select id="filter_staff" name="filter_staff" class="form-control">
            <option value="">All Staff</option>

            <?php foreach($members as $t){ 
                $name = trim(($t['firstname'] ?? '') . ' ' . ($t['lastname'] ?? ''));
                if(!$name) continue;
            ?>
                <option value="<?= $t['staffid'] ?>">
                    <?= $name ?>
                </option>
            <?php } ?>

        </select>
    </div>

    <!-- Accepted Date -->
    <div class="col-md-2">
        <label>Accepted From</label>
        <input type="date" id="accepted_from" name="accepted_from" class="form-control">
    </div>

    <div class="col-md-2">
        <label>Accepted To</label>
        <input type="date" id="accepted_to" name="accepted_to" class="form-control">
    </div>

    <!-- Delivered Date -->
    <div class="col-md-2">
        <label>Delivered From</label>
        <input type="date" id="delivered_from" name="delivered_from" class="form-control">
    </div>

    <div class="col-md-2">
        <label>Delivered To</label>
        <input type="date" id="delivered_to" name="delivered_to" class="form-control">
    </div>

    <!-- Button -->
    <div class="col-md-1">
                <label></label>

        <button type="button" id="applyFilters" class="btn btn-primary btn-block">
            Go
        </button>
    </div>

</form>

<div class="panel_s">
<div class="panel-body">


<h4>Staff Activity Breakdown</h4>

<div style="overflow-x:auto;">


<?php
$table_data = ['Staff Name','Leads'];

foreach($statuses as $status){
    $table_data[] = ucwords($status['name']);
}

$table_data[] = 'Won';
$table_data[] = 'Revenue';

render_datatable(
    $table_data,
    'staff-performance',
    [],
    ['id'=>'table-staff-performance']
);
?>


</div>


</div>
</div>
<div class="panel_s">
<div class="panel-body">
<h4>Estimate Wise Staff Performance</h4>
<?php
$table_data = [
    'Lead Added',
    'SO Accepted',
    'Company',
    'Contact Name',
    'Phone',
    'Location',
    'Sales Person',
   
    'Amount',
    'SO Number',
    'Delivered',
    'Source'
];

render_datatable($table_data, 'lead-estimate-report');
?>
</div>
</div>
</div>
</div>
</div>
</div>
<?php init_tail(); ?>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>

/* ================= LEADERBOARD ================= */

var leaderboardData = <?= json_encode($team ?? []) ?>;

var names=[], leads=[];

leaderboardData.forEach(function(s){
names.push((s.firstname ?? '') + " " + (s.lastname ?? ''));
leads.push(parseInt(s.leads ?? 0));
});

new ApexCharts(document.querySelector("#leaderboardChart"),{
chart:{type:'bar',height:320},
series:[{name:'Leads',data:leads}],
xaxis:{categories:names}
}).render();


/* ================= TREND ================= */

var trendData = <?= json_encode($staff_status ?? []) ?>;

var months=[], totals=[];

trendData.forEach(function(r){
var m="Month "+r.month;
if(!months.includes(m)) months.push(m);
totals.push(r.total);
});

new ApexCharts(document.querySelector("#staffTrendChart"),{
chart:{type:'line',height:320},
series:[{name:'Leads',data:totals}],
xaxis:{categories:months}
}).render();


/* ================= STATUS CHART ================= */

var staffData = <?= json_encode($staff_status ?? []) ?>;

var months=[], seriesMap={};

staffData.forEach(function(row){

var m="Month "+row.month;
if(!months.includes(m)) months.push(m);

var key=row.staff+" - "+row.status;

if(!seriesMap[key]) seriesMap[key]={};

seriesMap[key][m]=row.total;

});

var series=[];

for(var k in seriesMap){

var data=[];

months.forEach(function(m){
data.push(seriesMap[k][m]||0);
});

series.push({name:k,data:data});
}

new ApexCharts(document.querySelector("#staffStatusChart"),{
chart:{type:'bar',height:350},
series:series,
xaxis:{categories:months},
legend:{position:'bottom'}
}).render();

</script>

<script>
$(function(){

    console.log('=== DATATABLE INIT START ===');

    var url = admin_url + 'enterprise_sales_analytics/staff_table';
    console.log('AJAX URL:', url);

    var table = $('#table-staff-performance');

    if (!table.length) {
        console.error('❌ Table NOT FOUND');
        return;
    }

    console.log('✅ Table FOUND');

    var thCount = table.find('thead th').length;
    console.log('THEAD column count:', thCount);

    // 🔥 INIT WITH AJAX DEBUG
    initDataTable(
        '#table-staff-performance',
        admin_url + 'enterprise_sales_analytics/staff_table',
        [],
        [],
        {
            staff: '[name="filter_staff"]',
            accepted_from: '[name="accepted_from"]',
            accepted_to: '[name="accepted_to"]',
            delivered_from: '[name="delivered_from"]',
            delivered_to: '[name="delivered_to"]'
        }
    );

    // 🔥 LISTEN TO AJAX RESPONSE
    table.on('xhr.dt', function (e, settings, json, xhr) {

        console.log('=== AJAX RESPONSE RECEIVED ===');

        if (!json) {
            console.error('❌ JSON is NULL');
            console.log('Raw response:', xhr.responseText);
            return;
        }

        console.log('Full JSON:', json);

        if (json.error) {
            console.error('❌ Server error:', json.message);
        }

        if (json.aaData) {
            console.log('Row count:', json.aaData.length);

            if (json.aaData.length > 0) {
                console.log('Column count (row 0):', json.aaData[0].length);
            }
        } else {
            console.error('❌ aaData missing');
        }

    });

    // 🔥 AJAX ERROR HANDLER
    table.on('error.dt', function (e, settings, techNote, message) {
        console.error('❌ DataTable ERROR:', message);
    });

    console.log('=== DATATABLE INIT END ===');

});
</script>

<script>
$(function () {

    console.log('🚀 SCRIPT LOADED');

    var tableInstance = null;

    /* ================= INIT DATATABLE ================= */
    function initLeadTable() {

        if ($.fn.DataTable.isDataTable('.table-lead-estimate-report')) {
            tableInstance = $('.table-lead-estimate-report').DataTable();
            console.log('⚠️ Already initialized');
            return;
        }

        console.log('✅ Initializing DataTable...');

        tableInstance = initDataTable(
            '.table-lead-estimate-report',
            admin_url + 'enterprise_sales_analytics/lead_first_estimate_table',
            [],
            [],
            {
                staff: '[name="filter_staff"]',
                accepted_from: '[name="accepted_from"]',
                accepted_to: '[name="accepted_to"]',
                delivered_from: '[name="delivered_from"]',
                delivered_to: '[name="delivered_to"]'
            },
            [0, 'desc']
        );

        console.log('✅ DataTable initialized');
    }

    /* ================= TAB SWITCH ================= */
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {

        if ($(e.target).attr("href") === '#tables_tab') {
            console.log('📊 Tables tab opened');
            initLeadTable();
        }
    });

    // if already active
    if ($('#tables_tab').hasClass('active')) {
        initLeadTable();
    }

    /* ================= FILTER BUTTON ================= */
 $(document).on('click', '#applyFilters', function () {

    console.log('🔥 FILTER CLICKED');

    // Lead Estimate Table
    var table1 = $('.table-lead-estimate-report').DataTable();
    if (table1) {
        table1.ajax.reload(null, false);
    }

    // Staff Performance Table
    var table2 = $('#table-staff-performance').DataTable();
    if (table2) {
        table2.ajax.reload(null, false);
    }

});

});
</script>