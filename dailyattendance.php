<?php
include("connectdb.php");
$db = connectDB();
?>
<html>
  <head>
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
         <?php getDailyAttendanceData(1,$db); ?>
        ]);

        var options = {
          title: 'Daily Attendance'
        };

        var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
        chart.draw(data, options);
      }
    </script>
  </head>
  <body>
    <div id="chart_div" style="width: 900px; height: 500px;"></div>
  </body>
</html>


<?php
function getDailyAttendanceData($school_id, $db){
	$q = $db->prepare("
		SELECT school_date, count(*) as count from attendance_day where school_date>= '2013-08-05' group by school_date
	    ");

	$q->execute();

	$res = $q->fetchAll();
	print("['Date','Count'],");
	foreach($res as $val){
	    print("['".$val['school_date']."',".$val['count']."],");
	}
}
?>
