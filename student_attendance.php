<html>
<head>
    <script src="js/jquery-2.0.3.min.js" type="text/javascript"></script>
    <script src="js/jquery.dynatable.js" type="text/javascript"></script>
    <script src="js/table2CSV.js" type="text/javascript"></script>
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
       $('table').each(function() {
        var $table = $(this);
 
        var $button = $("<button type='button'>");
        $button.text("Get CSV");
        $button.insertAfter($table);
 
        $button.click(function() {
           var csv = $table.table2CSV({delivery:'value'});
            window.location.href = 'data:text/csv;charset=UTF-8,'
                            + encodeURIComponent(csv);
        });
    });
  </script>
</head>
<body>    
<?php
    include("connectdb.php");
    $db = connectDB();
    if(!isset($_REQUEST['syear'])) $syear = 2013;
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


    $q = $db->prepare("
	SELECT 
	enrolled_students.sid,
	enrolled_students.dob,
	enrolled_students.fname,
	enrolled_students.sname,
	enrolled_students.cname,
	enrolled_students.school_id,
	Grade.title as grade

	FROM

	(SELECT students.first_name as fname, students.last_name as sname, students.common_name as cname, students.student_id as sid, students.birthdate as dob, students.ethnicity as passport, student_enrollment.grade_id as grade_id, student_enrollment.school_id as school_id from students, student_enrollment
	WHERE 
	student_enrollment.student_id = students.student_id AND student_enrollment.syear = '$syear')
	as enrolled_students,

	(SELECT students_join_address.student_id as SID, address.email as Email, address.mobile_phone as Mobile, address.sec_mobile_phone as Mobile2, address.email as email1, address.sec_email as email2 FROM address, students_join_address where address.address_id = students_join_address.address_id) as student_addresses,

	(SELECT school_gradelevels.title as title, school_gradelevels.id as grade_id from school_gradelevels) as Grade

	WHERE
	student_addresses.SID = enrolled_students.SID AND
	enrolled_students.grade_id = Grade.grade_id

	ORDER BY enrolled_students.Sname ASC
    ");
    $q->execute();
   $result = $q->fetchAll();

    print("
	<table id = 'example-table'>
	<thead><tr>
		<th style=\"width: 3em;\">School</th>
		<th>Grade</th>
		<th style=\"width: 3em;\">SID</th>
		<th>Surname</th>
		<th>First Name</th>
            	<th>Common Name</th>
                <th>Late</th>
                <th>Absent</th>
	</tr></thead>");
    foreach($result as $row){
        $sid = $row['sid'];
        $sname= $row['sname'];
        $fname = $row['fname'];
	$cname = $row['cname'];
	$school_id = $row['school_id'];
	$grade = $row['grade'];
	$attendance = getAttendance($db, $sid, $syear, $school_id);

	$start = $db->prepare("
		SELECT student_id, start_date, MIN(syear)
		FROM student_enrollment
		WHERE student_id = '$sid'");
	$start->execute();
	$start_date = $start->fetch();

        //shouldn't ever by null, that would be weird
	$start_date = date('Y-M-d',strtotime($start_date['start_date']));

	$end = $db->prepare("
		SELECT student_id, end_date, MAX(syear)
		FROM student_enrollment
		WHERE student_id = '$sid'
                AND syear=(SELECT MAX(syear) FROM student_enrollment
                            WHERE student_id='$sid');");
	$end->execute();
	$end_date = $end->fetch();
        if($end_date['end_date']!=null)
	    $end_date = date('Y-M-d',strtotime($end_date['end_date']));
        else 
            $end_date = null;

        $lates = $attendance['late'];
        $absences = $attendance['absent'];

    print("
	<tr>
		<td style=\"width:3em;\">$schools[$school_id]</td>
		<td>$grade</td>
		<td \"width:3em;\"><a href=\"student_absence_summary.php?sid=$sid\">$sid</a></td>
		<td>$sname</td>
		<td>$fname</td>
            	<td>$cname</td>
            	<td>$lates</td>
            	<td>$absences</td>
	</tr>");
    }
    print("</table>");

//returns an array keyed by 'absent' and 'late'
function getAttendance($db, $sid, $syear='2013', $school_id='1'){
    //initial values
    $attendance = ["absent"=>0,"late"=>"0"];

    
    //just getting absences by semester
    $query = $db->prepare("SELECT * FROM marking_periods where school_id=$school_id AND syear = $syear AND mp_type='semester'");
    $query->execute();
    $mp_result = $query->fetchAll();
    $i = 1;

    //this runs once per marking period -
    foreach($mp_result as $val){
       $sdate = $val['start_date'];
       $edate = $val['end_date'];
       $short_name = $val['short_name'];

       //if(strtotime($edate)>strtotime("Yesterday")) $edate = date("Y-m-d",strtotime("Yesterday"));


       //get total number of days per marking period from attendance calendar (all the way to the end of the mp)
       $q = $db->prepare("SELECT COUNT(*) as count from attendance_calendar where syear=$syear AND school_id=$school_id
          AND school_date>='".$sdate."' AND school_date<='".$edate."'");
       $q->execute();
       $res = $q->fetch();

       //get total number of days present for selected student by
       $qda = $db->prepare("
             SELECT count(attendance_period.school_date) as count from attendance_period,
             (SELECT id from attendance_codes where syear=$syear AND school_id =$school_id AND title LIKE \"present\")
             as present_id
             WHERE
             attendance_period.attendance_code = present_id.id AND student_id = $sid AND school_date>='"
             .$sdate."' AND school_date<='".$edate."'");
       $qda->execute();
       $dares = $qda->fetch();

       //get total number of days tardy
       $qdt = $db->prepare("
             SELECT count(attendance_period.school_date) as count from attendance_period,
             (SELECT id from attendance_codes where syear=$syear AND school_id =$school_id AND title LIKE \"late\")
             as late_id
             WHERE
             attendance_period.attendance_code = late_id.id AND student_id = $sid AND school_date>='"
             .$sdate."' AND school_date<='".$edate."'");
       $qdt->execute();
       $dtres = $qdt->fetch();

       //get the total number of unknown days
       $q = $db->prepare("SELECT COUNT(*) as count from attendance_calendar where syear=$syear AND school_id=$school_id
            AND school_date>='".date("Y-m-d",strtotime("Today"))."' AND school_date<='".$edate."'");

         $q->execute() or die();
         $dures = $q->fetch();


         $sdays[$short_name] = $res['count'];


         /*
          * days absent are the total days - days present - days tardy
          * (that is: all attendance codes that aren't 'present' or 'late')
          */
         $da[$short_name] = $res['count'] - $dares['count'] - $dtres['count'] - $dures['count'];
         if($da[$short_name]<0) $da[$short_name]=0;

         $dt[$short_name] = $dtres['count'];

         //not strictly necessary
         $du[$short_name] = $dures['count'];
	
	$attendance['absent'] += $da[$short_name];
	$attendance['late'] += $dt[$short_name];

        //print("in $short_name, found ".$da[$short_name]." absences</br>");

      }

     return $attendance;
    
}
?>
<script type="text/javascript">
      $('#example-table').dynatable({
	
	dataset: {
	    perPageDefault: 10,
	    perPageOptions: [10,20,50,500,2000],
            sortTypes: {
                sid: 'number',
                absent: 'number',
                late: 'number'
            }
	},

	}); //end dynatable
</script>

</body>
