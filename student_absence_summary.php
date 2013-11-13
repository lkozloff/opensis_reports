<html>
<head>
    <script src="js/jquery-2.0.3.min.js" type="text/javascript"></script>
    <script src="js/jquery.dynatable.js" type="text/javascript"></script>
     <style>
    body{
	font-family: sans-serif;
	}
    table {
      border: solid 1px #ccc;    border-collapse: collapse;
      border-spacing: 0;
      position: relative; /* Add positioning */
	font-size: x-small;
    }
    td, th {
      border-bottom: solid 1px #eee;
      border-top: solid 1px #ccc;
      border-left: solid 1px #eee;
      border-right: solid 1px #ccc;
      padding: 5px;
    }
    th {
      background: #eee;
      min-width: 70px;
    }
    a{
        text-decoration: none;
        color: gray;
    }  
.dynatable-search {
  float: right;
  margin-bottom: 10px;
}

.dynatable-pagination-links {
  float: right;
}

.dynatable-record-count {
  display: block;
  padding: 5px 0;
}

.dynatable-pagination-links span,
.dynatable-pagination-links li {
  display: inline-block;
}

.dynatable-page-link,
.dynatable-page-break {
  display: block;
  padding: 5px 7px;
}

.dynatable-page-link {
  cursor: pointer;
}

.dynatable-active-page {
  background: #71AF5A;
  border-radius: 5px;
  color: #fff;
}
.dynatable-page-prev.dynatable-active-page,
.dynatable-page-next.dynatable-active-page {
  background: none;
  color: #999;
  cursor: text;
} 

  </style>
<script type="text/javascript">
    $(document).ready( function() {
        $('#example-table').dynatable();
    });
  </script>
</head>
<body>    
<?php
    include("connectdb.php");
    $db = connectDB();
    if(!isset($_REQUEST['sid'])) print("<h1>No Student ID entereed!</h1>");
    else $sid=$_REQUEST['sid'];

    if(!isset($_REQUEST['syear'])) $syear=2013;
    else $syear=$_REQUEST['syear'];
    
    
    $syear2 = $syear+1;
    print("<h1>$syear - $syear2</h1>");

    //get all possible school years
    $year = $db->prepare("SELECT DISTINCT syear from school_years");
    $year->execute();
    $syears = $year->fetchAll();
    
    foreach($syears as $cur_syear){
	$thisyear = $cur_syear['syear']; 
        print("|&nbsp;<a href = \"student_attendance.php?syear=$thisyear\">$thisyear</a>&nbsp;");
    }
	print("|");
    
    $schools = Array(1=>'LIS',2=>'AHIS');

    //best effort guess which school the student is enrolled at
    $stu = $db->prepare("SELECT school_id from student_enrollment where student_id='$sid' and syear='$syear'");
    $stu->execute();
    $schid = $stu->fetch();
    $school_id = $schid['school_id'];

    $stu = $db->prepare("SELECT first_name, last_name from students WHERE student_id='$sid'");
    $stu->execute();
    $sname = $stu->fetch();


    print("<h2>$sid: ".$sname['last_name'].", ".$sname['first_name']."</h2>");
    print("
	<table id = 'example-table'>
	<thead><tr>
		<th>Date</th>
		<th>Absence Type</th>
		<th>Reason</th>
                <th>Period</th>
	</tr></thead>");

    //find missing attendance
    $abs = $db->prepare("
        SELECT * 
        FROM  `attendance_calendar` 
        WHERE syear='$syear'
        AND school_date <= '".date("Y-m-d",strtotime("Yesterday"))."'
        AND school_id = '$school_id'
        AND school_date NOT 
        IN (
            SELECT school_date
            FROM attendance_period
            WHERE student_id =$sid
        )
    ");
    $abs->execute();
    $result = $abs->fetchAll();

    foreach($result as $row){
        $date = $row['school_date'];
        $type = "Not Recorded";
        print("
	<tr>
            	<td>$date</td>
            	<td>$type</td>
            	<td>$reason</td>
                <td>$period</td>
	</tr>");
   } 
   //get other attendance codes
    $absq = $db->prepare("SELECT * from attendance_codes where syear='$syear'");
    $absq->execute();
    $abs_res=$absq->fetchAll();

    $abscodes = Array();
    foreach($abs_res as $abscode){
        $id = $abscode['id'];
        $title = $abscode['title'];

        $abscodes[$id]=$title;
     }

   //find absences
    $qdt = $db->prepare("
         SELECT * from attendance_period,
         (SELECT id from attendance_codes where syear=$syear AND state_code='A' )
         as absent_id
         WHERE
        attendance_period.attendance_code = absent_id.id AND student_id = '$sid'");
         $qdt->execute();
         $dtres = $qdt->fetchAll();

    foreach($dtres as $dt){
        $reason = $dt['attendance_reason'];
        $date = $dt['school_date'];
        $ac = $dt['attendance_code'];
        $type = $abscodes[$ac];

        $cp_id = $dt['course_period_id'];
        
        //get course title
        $q = $db->prepare("SELECT title from course_periods where course_period_id='$cp_id'");
        $q->execute();
        $t = $q->fetch();
        $period = $t['title'];
        print("
	<tr>
            	<td>$date</td>
            	<td>$type</td>
            	<td>$reason</td>
                <td>$period</td>
	</tr>");
    }

    //find late days
    $qdt = $db->prepare("
         SELECT * from attendance_period,
         (SELECT id from attendance_codes where syear=$syear AND title LIKE \"late\")
         as late_id
         WHERE
        attendance_period.attendance_code = late_id.id AND student_id = '$sid'");
         $qdt->execute();
         $dtres = $qdt->fetchAll();

    foreach($dtres as $dt){
        $reason = $dt['attendance_reason'];
        $date = $dt['school_date'];
        $ac = $dt['attendance_code'];
        $type = "Late";
 
        $cp_id = $dt['course_period_id'];
        
        //get course title
        $q = $db->prepare("SELECT title from course_periods where course_period_id='$cp_id'");
        $q->execute();
        $t = $q->fetch();
        $period = $t['title'];
       print("
	<tr>
            	<td>$date</td>
            	<td>$type</td>
            	<td>$reason</td>
            	<td>$period</td>
	</tr>");
    }


  

        print("</table>");
?>
</body>
