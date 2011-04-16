<?php
	error_reporting(1);
	require_once('class.cd.php');
	$metrics = CD::getConversions('test');
?>
<html>
	<head>
		<style type="text/css">
			table.ab_table { font: normal 14px arial, sans-serif; padding-left:40px; }
			table.ab_table tr th { font: bold 14px arial, sans-serif; padding: 3px 10px; border-bottom: 3px solid #969696; width:150px; text-align:center;}
			table.ab_table tr td { background: #fff; padding: 10px 5px; width:150px; text-align:center;}
			table.ab_table tr.alt td { background: #f3f3f3; }
		</style>
		<script type='text/javascript' src='https://www.google.com/jsapi'></script>
		<script type='text/javascript'>
			google.load("visualization", "1", {packages:["corechart"]});
			google.setOnLoadCallback(drawChart);
			function drawChart() {
				var data = new google.visualization.DataTable();
				data.addColumn('string', 'Date');
				data.addColumn('number', 'Original');
				<?php
					for($k=1;$k<$metrics[1];$k++)
						echo "data.addColumn('number','Alt ".$k."');\n";
					$i = 0;
					echo "data.addRows(".$k.");\n";
					foreach($metrics[0] AS $date => $keys) {
						echo "data.setValue(".$i.",0,'".date('M j',strtotime($date))."');\n";
						for($j=0;$j<$k;$j++) {
							$conv_rate = 0;
							$extras[$j]['start'] += $keys[$j]['start'];
							$extras[$j]['finish'] += $keys[$j]['finish'];
							if($keys[$j]['start'] > 0)
								$conv_rate = round($keys[$j]['finish']/$keys[$j]['start'],2);
							echo "data.setValue(".$i.",".($j+1).",".$conv_rate.");\n";
						}
						$i++;
					}	
				?>
				
				var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
				chart.draw(data, {width: 800, height: 340, title: 'Conversions', vAxis: {format: '#%'}});
			}
		</script>
	</head>
	
	<body>
		<div id='chart_div' style='margin-left:60px;'></div>
		<table class="ab_table">
			<tr>
				<th>Option</th>
				<th>Est. Conversion Rate</th>
				<th>Chance to Beat Orig.</th>
				<th>Observed Improvement</th>
				<th>Goals/Starts</th>
			<tr>
			<?php
				foreach($extras as $key => $val):
					$conv_rate = round($val['finish']/$val['start'],2);
					$se = CD::calculateSE($val['finish'],$val['start']);
					$class = ($j % 2 == 0) ? 'class="alt"' : '';
					if($key == 0) {
						$name = 'Original';
						$prob = '-';
						$chance = '-';
						$orig_start = $val['start'];
						$orig_finish = $val['finish'];
					} else {
						$name = 'Alt ' . $key;
						$prob = CD::calculateProbability($val['finish'],$val['start'],$orig_finish,$orig_start);
						$impr = CD::calculateImprovement($val['finish'],$val['start'],$orig_finish,$orig_start);
					}
			?>
			<tr <?php echo $class ?>>
				<td><?php echo $name ?></td>
				<td><?php echo round($conv_rate * 100,2) ?>% &#177; <?php echo round($se * 100,1) ?>%</td>
				<td><?php echo round($prob * 100,2) ?>%</td>
				<td><?php echo round($impr * 100,2) ?>%</td>
				<td><?php echo $val['finish'] ?>/<?php echo $val['start'] ?></td>
			<tr>
			<?php $j++;endforeach ?>
	</body>
</html>