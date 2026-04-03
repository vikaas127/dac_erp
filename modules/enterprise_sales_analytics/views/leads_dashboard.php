<?php init_head(); ?>

<div id="wrapper">
<div class="content">

<h3 class="mbot20">Leads Analytics</h3>

<div class="row">

<!-- Lead Sources -->

<div class="col-md-6">
<div class="panel_s">
<div class="panel-body">

<h4>Lead Sources</h4>
<div id="sourceChart"></div>

</div>
</div>
</div>

<!-- Lead Funnel -->

<div class="col-md-6">
<div class="panel_s">
<div class="panel-body">

<h4>Lead Funnel</h4>
<div id="funnelChart"></div>

</div>
</div>
</div>

</div>


<div class="row">

<!-- Monthly Lead Status -->

<div class="col-md-12">
<div class="panel_s">
<div class="panel-body">

<h4>Monthly Lead Status</h4>
<div id="statusChart"></div>

</div>
</div>
</div>

</div>


<!-- Lead Aging -->

<div class="panel_s">
<div class="panel-body">

<h4>Lead Aging</h4>

<table class="table table-bordered">

<thead>
<tr>
<th>Name</th>
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

</div>
</div>

<?php init_tail(); ?>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>

/* ---------------- Lead Sources ---------------- */

var sourceData = <?= json_encode($sources) ?>;

var labels = [];
var values = [];

sourceData.forEach(function(s){
labels.push(s.source);
values.push(s.total);
});

new ApexCharts(document.querySelector("#sourceChart"),{
chart:{type:'pie',height:320},
series:values,
labels:labels
}).render();



/* ---------------- Lead Funnel ---------------- */

var funnelData = <?= json_encode($funnel) ?>;

var stages=[];
var totals=[];

funnelData.forEach(function(f){
stages.push(f.status);
totals.push(f.total);
});

new ApexCharts(document.querySelector("#funnelChart"),{
chart:{type:'bar',height:320},
series:[{name:'Leads',data:totals}],
xaxis:{categories:stages}
}).render();



/* ---------------- Monthly Lead Status ---------------- */

var statusData = <?= json_encode($monthly_status) ?>;

var months = [];
var statusMap = {};
var statusTypes = new Set();

statusData.forEach(function(row){

var m = "Month " + row.month;

if(!months.includes(m)){
months.push(m);
}

statusTypes.add(row.status);

if(!statusMap[row.status]){
statusMap[row.status] = {};
}

statusMap[row.status][m] = row.total;

});


var series = [];

statusTypes.forEach(function(status){

var data=[];

months.forEach(function(m){
data.push(statusMap[status][m] || 0);
});

series.push({
name:status,
data:data
});

});


new ApexCharts(document.querySelector("#statusChart"),{

chart:{type:'bar',height:350},

series:series,

xaxis:{
categories:months
},

plotOptions:{
bar:{
horizontal:false,
columnWidth:'50%'
}
}

}).render();

</script>