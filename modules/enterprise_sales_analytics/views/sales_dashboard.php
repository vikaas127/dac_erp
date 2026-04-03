<?php init_head(); ?>

<div id="wrapper">
<div class="content">

<h3 class="mbot20">Sales Analytics</h3>

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

<!-- ================= TOP SECTION ================= -->

<div class="row">

<div class="col-md-6">
<div class="panel_s">
<div class="panel-body">
<h4>Revenue Trend</h4>
<div id="revenueChart"></div>
</div>
</div>
</div>

<div class="col-md-6">
<div class="panel_s">
<div class="panel-body">
<h4>Sales Forecast</h4>
<h2 class="text-success">₹<?= number_format($forecast->forecast ?? 0) ?></h2>
</div>
</div>
</div>

</div>

<!-- ================= PIPELINE ================= -->

<div class="panel_s">
<div class="panel-body">

<h4>Sales Pipeline</h4>

<table  class="table table-bordered table-striped">

<thead>
<tr>
<th>Opportunity</th>
<th>Customer</th>
<th>Value</th>
</tr>
</thead>

<tbody>

<?php foreach($pipeline_list as $p){ ?>
<tr>
<td><?= $p->subject ?></td>
<td><?= $p->company ?></td>
<td>₹<?= number_format($p->total) ?></td>
</tr>
<?php } ?>

</tbody>
</table>

</div>
</div>

<!-- ================= PRODUCTS + CUSTOMERS ================= -->

<div class="row">

<!-- PRODUCTS -->

<div class="col-md-6">
<div class="panel_s">
<div class="panel-body">

<h4>Top Products</h4>

<table  class="table table-bordered table-striped">

<thead>
<tr>
<th>Product</th>
<th>Units</th>
<th>Revenue</th>
</tr>
</thead>

<tbody>

<?php foreach($products as $p){ ?>
<tr>
<td><?= $p->description ?></td>
<td><?= $p->units ?></td>
<td class="text-success">₹<?= number_format($p->revenue) ?></td>
</tr>
<?php } ?>

</tbody>
</table>

</div>
</div>
</div>

<!-- CUSTOMERS (🔥 NEW) -->

<div class="col-md-6">
<div class="panel_s">
<div class="panel-body">

<h4>Top Customers</h4>

<table  class="table table-bordered table-striped">

<thead>
<tr>
<th>Customer</th>
<th>Units</th>
<th>Revenue</th>
</tr>
</thead>

<tbody>

<?php foreach($customers as $c){ ?>
<tr>
<td><?= $c->company ?></td>
<td><?= $c->units ?></td>
<td class="text-primary">₹<?= number_format($c->revenue) ?></td>
</tr>
<?php } ?>

</tbody>
</table>

</div>
</div>
</div>

</div>

<!-- ================= REGION ================= -->

<div class="panel_s">
<div class="panel-body">

<h4>Geographic Sales</h4>

<table  class="table table-bordered table-striped">

<thead>
<tr>
<th>Region</th>
<th>Leads</th>
</tr>
</thead>

<tbody>

<?php foreach($regions as $r){ ?>
<tr>
<td><?= $r->state ?></td>
<td><?= $r->leads ?></td>
</tr>
<?php } ?>

</tbody>
</table>

</div>
</div>

</div>
</div>

<?php init_tail(); ?>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>

/* ================= REVENUE CHART ================= */

var revenueData = <?= json_encode($monthly_revenue ?? []) ?>;

var months=[], revenue=[];

revenueData.forEach(function(r){
months.push("Month "+r.month);
revenue.push(r.revenue);
});

new ApexCharts(document.querySelector("#revenueChart"),{
chart:{type:'line'},
series:[{name:'Revenue',data:revenue}],
xaxis:{categories:months}
}).render();


/* ================= DATATABLE ================= */

$(document).ready(function(){

$('#pipelineTable').DataTable({order:[[2,"desc"]]});
$('#productTable').DataTable({order:[[2,"desc"]]});
$('#customerTable').DataTable({order:[[2,"desc"]]});
$('#regionTable').DataTable();

});

</script>