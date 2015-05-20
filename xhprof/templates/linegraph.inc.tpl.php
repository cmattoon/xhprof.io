<?php
namespace ay\xhprof;
?>
<script src="public/js/charts.min.js"></script>
<style>
 .legend  {
   list-style: none;
   display: inline;
 }
 .legend span {
   display: inline-block;
   height: 1em;
   width: 1em;
 }
 span.wt { background-color:red;}
 span.cpu { background-color: blue; }
 span.mu { background-color: orange; }
 span.pmu {background-color: green; }
</style>
<div class="histogram-layout">
  <div class="left">
    <div class="column">
      <div class="label">Time-Averaged Stats</div>
      <canvas id="myChart" width="720" height="470" style='border:1px solid #f80'></canvas>
      <div id="legendDiv">
	<li class="legend"><span class="wt"></span> Wall Time (sec)</li>
	<li class="legend"><span class="cpu"></span> CPU Time (sec)</li>
	<li class="legend"><span class="mu"></span> Avg Mem Use (MB)</li>
	<li class="legend"><span class="pmu"></span> Peak Mem Use (MB)</li>
      </div>
      <!-- <svg class="histogram-date"></svg> -->
    </div>
  </div>
  <div class="right">
    <div class="column">
      <div class="label">CPU</div>
      <canvas id="cpuChart" width="530px" height="225px"></canvas>
    </div>
    <div class="column">
      <div class="label">Wall Time</div>
      <canvas id="wtChart" width="530px" height="225px"></canvas>
    </div>
  </div>
  <div class="center">
    <div class="column">
      <div class="label">Memory Usage</div>
      <canvas id="muChart" width="530px" height="225px"></canvas>
    </div>
    <div class="column">
      <div class="label">Peak Memory Usage</div>
      <canvas id="pmuChart" width="530px" height="225px"></canvas>
    </div>
  </div>
</div>
<script type="text/javascript">
$(function(){
   var requests	= <?php
		  echo json_encode(array_map(function($e){
		    return array($e['request_id'], $e['request_timestamp'], $e['wt'], $e['cpu'], $e['mu'], $e['pmu']);
		  }, $data['discrete']));
		  ?>;
   window.reqs = requests;
   var format = {
     bytes: function(number) {
       var precision = 2;
       var base = Math.log(Math.abs(number)) / Math.log(1024);
       var suffixes = ['b', 'k', 'M', 'G', 'T'];   
       return (Math.pow(1024, base - Math.floor(base))).toFixed(precision) + ' ' + suffixes[Math.floor(base)];
     },
     microseconds: function(number) {
       var pad		= false;
       var suffix	= 'µs';
       
       if (number >= 1000) {
	 number	= number / 1000;
	 suffix	= 'ms';
	 
	 if (number >= 1000) {
	   pad	= true;
	   number = number / 1000;
	   suffix = 's';
	   
	   if (number >= 60) {
	     number = number / 60;
	     suffix = 'm';
	   }
	 }
       }
       
       return pad ? number.toFixed(2) + ' ' + suffix : number + ' ' + suffix;
     }
   };
   var rlen = requests.length;
   var data = {'wt':{}, 'cpu':{}, 'mu':{}, 'pmu':{}};
   var lbls = [];
   var keys = ['id','ts', 'wt', 'cpu', 'mu','pmu'];

   for (var i=0;i<rlen;i++) {
     var req = requests[i];
     // id, timestamp, wt, cpu, mu, pmu
     // Get minutely averages
     var dt = new Date(req[1]*1000);
     //var dts = dt.toISOString();
     var min = (dt.getMinutes() > 30) ? ':30' : ':00';
     var dts = dt.toDateString() + ' ' + dt.getHours() +min;
     if (false && 10) {
       dts = dts.substring(0, dts.length-1)+'0';
     }
     if (lbls.indexOf(dts) < 0) {
       lbls.push(dts);
     }
     for (var j=2;j<keys.length;j++) {
       var key = keys[j];
       if (!data[key].hasOwnProperty(dts)) {
	 data[key][dts] = [];
       }
       data[key][dts].push(req[j]);
     }
   }
   // Sort out averages per timestamp
   var sorted = {}
   for (key in data) {
     if (key === undefined) continue;
     var info = data[key];
     if (info === undefined) continue;
     sorted[key] = {};
     for (ts in data[key]) {
       tslen = data[key][ts].length;
       sum = 0;
       for (var i=0;i<tslen;i++) {
	 sum += parseFloat(data[key][ts][i]);
       }
       var avg = sum / tslen;
       switch (key) {
	   case 'wt':
	   case 'mu':
	   case 'pmu':
	   case 'cpu':
	   avg = avg / 1000000;
	   break;	   
       }
       sorted[key][ts] = avg;
     }
   }
   data = sorted;

   var wtLight = "rgba(255, 201, 201, 0.98)";
   var wtDark = "rgba(255, 73, 73, 0.98)";

   var cpuLight = "rgba(167, 214, 255, 0.98)";
   var cpuDark = "rgba(0, 136, 255, 0.98)";

   var memLight = "rgba(255, 224, 169, 0.98)";
   var memDark = "rgba(255, 163, 0, 0.98)";

   var memLight2 = "rgba(193, 234, 193, 0.98)";
   var memDark2 = "rgba(27, 187, 27, 0.98)";

   var peak_mem_use = {
     label: "Peak Memory",
     fillColor: memLight2,
     strokeColor: memDark2,
     pointColor:  memLight2,
     pointStrokeColor: memDark2,
     pointHighlightFill: memDark2,
     pointHighlightStroke: memLight2,
     data: data['pmu']
   };
   var mem_use = {
     label: "Memory",
     fillColor: memLight,
     strokeColor: memDark,
     pointColor:  memLight,
     pointStrokeColor: memDark,
     pointHighlightFill: memDark,
     pointHighlightStroke: memLight,
     data: data['mu']
   };
   var wall_time = {
     label: "Wall Time",
     fillColor: wtLight,
     strokeColor: wtDark,
     pointColor: wtLight,
     pointStrokeColor: wtDark,
     pointHighlightFill: wtDark,
     pointHighlightStroke: wtLight,
     data: data['wt']
   };
   var cpu_use = {
     label: "CPU",
     fillColor: cpuLight,
     strokeColor: cpuDark,
     pointColor:  cpuLight,
     pointStrokeColor: cpuDark,
     pointHighlightFill: cpuDark,
     pointHighlightStroke: cpuLight,
     data: data['cpu']
   };
   var datasets = {
     labels: lbls,
     datasets: [
       peak_mem_use,
       mem_use,
       wall_time,
       cpu_use,
     ]
   };
   var ctx = document.getElementById("myChart").getContext("2d");
   var ctxWt = document.getElementById('wtChart').getContext('2d');
   var ctxCpu = document.getElementById('cpuChart').getContext('2d');
   var ctxMu = document.getElementById('muChart').getContext('2d');
   var ctxPmu = document.getElementById('pmuChart').getContext('2d');

   var chart = new Chart(ctx).Line(datasets);
   var pmuChart = new Chart(ctxPmu).Line({labels:lbls, datasets: [peak_mem_use]});
   var muChart = new Chart(ctxMu).Line({labels:lbls, datasets: [mem_use]});//datasets.datasets[1]);
   var wtChart = new Chart(ctxWt).Line({labels:lbls, datasets: [wall_time]});//datasets.datasets[2]);
   var cpuChart = new Chart(ctxCpu).Line({labels:lbls, datasets: [cpu_use]});//datasets.datasets[3]);

   //document.getElementById('legendDiv').innerHTML = chart.generateLegend();
   var filter = crossfilter(requests);
   
 });
</script>