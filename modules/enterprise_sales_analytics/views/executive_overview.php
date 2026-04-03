<?php init_head(); ?>

<div id="wrapper">
<div class="content">

<h3 class="mbot20">Sales Intelligence Dashboard</h3>

<!-- KPI ROW -->

<div class="row">

<div class="col-md-3">
<div class="panel_s">
<div class="panel-body text-center">
<h3><?= $total_leads ?></h3>
<p>Total Leads</p>
</div>
</div>
</div>

<div class="col-md-3">
<div class="panel_s">
<div class="panel-body text-center">
<h3>₹<?= number_format($pipeline) ?></h3>
<p>Pipeline Value</p>
</div>
</div>
</div>

<div class="col-md-3">
<div class="panel_s">
<div class="panel-body text-center">
<h3>₹<?= number_format($revenue) ?></h3>
<p>Total Revenue</p>
</div>
</div>
</div>

<div class="col-md-3">
<div class="panel_s">
<div class="panel-body text-center">
<h3><?= $conversion_rate ?>%</h3>
<p>Conversion Rate</p>
</div>
</div>
</div>

</div>

<hr>

<!-- SALES FUNNEL + REVENUE -->

<div class="row">

<div class="col-md-6">

<div class="panel_s">
<div class="panel-body">

<h4>Sales Funnel</h4>

<canvas id="funnelChart"></canvas>

</div>
</div>

</div>


<div class="col-md-6">

<div class="panel_s">
<div class="panel-body">

<h4>Revenue Trend</h4>

<canvas id="revenueChart"></canvas>

</div>
</div>

</div>

</div>

<hr>

<!-- SALES PIPELINE -->

<div class="panel_s">
<div class="panel-body">

<h4>Sales Pipeline</h4>

<table class="table table-bordered">

<thead>
<tr>
<th>Opportunity</th>
<th>Customer</th>
<th>Value</th>
<th>Probability</th>
</tr>
</thead>

<tbody>

<?php foreach($pipeline_list as $p){ ?>

<tr>

<td><?= $p->subject ?></td>
<td><?= $p->company ?></td>
<td>₹<?= number_format($p->total) ?></td>
<td><?= $p->probability ?>%</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>
</div>


<hr>

<!-- SALESPERSON PERFORMANCE -->

<div class="panel_s">
<div class="panel-body">

<h4>Salesperson Performance</h4>

<table class="table table-striped">

<thead>
<tr>
<th>Salesperson</th>
<th>Leads</th>
</tr>
</thead>

<tbody>

<?php foreach($team as $t){ ?>

<tr>

<td><?= $t->assigned ?></td>
<td><?= $t->leads ?></td>

</tr>

<?php } ?>

</tbody>

</table>

</div>
</div>


<hr>

<!-- PRODUCT SALES -->

<div class="panel_s">
<div class="panel-body">

<h4>Product Sales</h4>

<table class="table table-striped">

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
<td>₹<?= number_format($p->revenue) ?></td>

</tr>

<?php } ?>

</tbody>

</table>

</div>
</div>


<hr>

<!-- GEOGRAPHIC SALES -->

<div class="panel_s">
<div class="panel-body">

<h4>Geographic Analytics</h4>

<table class="table table-bordered">

<thead>
<tr>
<th>State</th>
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


<hr>

<!-- LEAD AGING -->

<div class="panel_s">
<div class="panel-body">

<h4>Lead Aging</h4>

<table class="table table-bordered">

<thead>
<tr>
<th>Lead</th>
<th>Company</th>
<th>Aging (Days)</th>
</tr>
</thead>

<tbody>

<?php foreach($aging as $a){ ?>

<tr>

<td><?= $a->name ?></td>
<td><?= $a->company ?></td>
<td><?= $a->aging ?></td>

</tr>

<?php } ?>

</tbody>

</table>

</div>
</div>


<hr>

<!-- LOST DEAL ANALYSIS -->

<div class="panel_s">
<div class="panel-body">

<h4>Lost Deals</h4>

<table class="table table-bordered">

<thead>
<tr>
<th>Reason</th>
<th>Total</th>
</tr>
</thead>

<tbody>

<?php foreach($lost as $l){ ?>

<tr>

<td><?= $l->lost_reason ?></td>
<td><?= $l->total ?></td>

</tr>

<?php } ?>

</tbody>

</table>

</div>
</div>


<hr>

<!-- SALES FORECAST -->

<div class="panel_s">
<div class="panel-body">

<h4>Sales Forecast</h4>

<h2>Expected Revenue: ₹<?= number_format($forecast->forecast) ?></h2>

</div>
</div>


</div>
</div>

<?php init_tail(); ?>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

/* FUNNEL */

var funnelData = <?= json_encode($funnel) ?>;

var stages = [];
var values = [];

funnelData.forEach(function(f){

stages.push(f.status);
values.push(f.total);

});

new Chart(document.getElementById('funnelChart'),{

type:'bar',

data:{
labels:stages,
datasets:[{
label:'Leads',
data:values,
backgroundColor:'#10B981'
}]
}

});


/* REVENUE */

var revenueData = <?= json_encode($monthly_revenue) ?>;

var months = [];
var revenue = [];

revenueData.forEach(function(r){

months.push("Month "+r.month);
revenue.push(r.revenue);

});

new Chart(document.getElementById('revenueChart'),{

type:'line',

data:{
labels:months,
datasets:[{
label:'Revenue',
data:revenue,
borderColor:'#3B82F6'
}]
}

});

</script>