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
         <?php getDailyAttendanceData($db); ?>
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
function getDailyAttendanceData($db){

        $q = $db->prepare("
            select * from attendance_calendar where syear=2013 
                AND school_date <'".date("Y-m-d",strtotime("Tomorrow"))."'
        ");
	$q->execute();
	$res = $q->fetchAll();
	print("['Date','Present','Sick'],");
        foreach($res as $day){
            $cur_date = $day['school_date'];
            $qa = $db->prepare("
                SELECT attendance_code, COUNT( attendance_code ) AS count
                FROM attendance_period
                WHERE school_date =  '$cur_date'
                GROUP BY attendance_code
            ");
            $qa->execute();
            $sick =0; $present=0; $absent=0;
            $codes = $qa->fetchAll();
            foreach($codes as $code){
                switch($code['attendance_code']){
                    case 45:
                        $sick += $code['count'];
                        break;
                    case 41:
                        $sick += $code['count'];
                        break;
                    case 43:
                        $present += $code['count'];
                        break;
                    case 38:
                        $present += $code['count'];
                        break;
                    case 47:
                        $present += $code['count'];
                        break;
                    case 35:
                        $present += $day['count'];
                        break;
                    default:
                        $absent += $day['count'];
            }
        }
    print("['$cur_date',$present,$sick],");

    }
}
?>