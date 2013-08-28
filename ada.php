<?php
/**
 * Average Daily Attendance
 * 
 */
include("connectdb.php");

//things to specify
$syear = 2013;
$sdate = "2013-08-01";
$edate = "2013-08-25";

//initial values
$total_sdays = 0;
$total_da = 0;
$total_dt = 0;
$total_du = 0;
$student_count = 0;

$sdbh = connectDB();

//when do we assume that a student shouldn't count against the percentages?
$threshold = 15;

//Figure out classes available
$classq = $sdbh->prepare("SELECT * from school_gradelevels where school_id=2 order by sort_order asc");
$classq->execute();
$classes = $classq->fetchAll(PDO::FETCH_ASSOC);


//get total number of days per marking period from attendance calendar (all the way to the end of the range)
$q = $sdbh->prepare("SELECT COUNT(*) as count from attendance_calendar where syear=$syear AND school_id=2 AND school_date>='"
 .$sdate."' AND school_date<='".$edate."'");
$q->execute();
$daycount = $q->fetch();

//get the number of days between tomorrow and the end of the range
$q2 = $sdbh->prepare("SELECT COUNT(*) as count from attendance_calendar where syear=$syear AND school_id=2 AND school_date>='"
		 .date("Y-m-d",strtotime("Tomorrow"))."' AND school_date<='".$edate."'");

$q2->execute();
 $futuredaycount = $q2->fetch();

print("Date range: $sdate - $edate<br/>");
print("Number of school days in range: ".($daycount['count']-$futuredaycount['count']));
if($futuredaycount['count'] >0){ 
	print("<br/>(".$futuredaycount['count']." occur in the future and will not be calculated)<br/>");
	$edate = date("Y-m-d",strtotime("Today"));
	print("effective date range: $sdate - $edate<br/>");
}

print("<br/>");

//get total number of days per marking period from attendance calendar with the new effective date
$q = $sdbh->prepare("SELECT COUNT(*) as count from attendance_calendar where syear=$syear AND school_id=2 AND school_date>='"
 .$sdate."' AND school_date<='".$edate."'");
$q->execute();
$res = $q->fetch();

print("Threshold set to $threshold, absences exceeding this amount will not count towards the totals. <br/><br/>");

   foreach($classes as $class){
	
	$sdays = 0;
	$da = 0;
	$dt = 0;
	$du = 0;
	$student_count = 0;
	
	$squery = $sdbh->prepare("SELECT * from student_enrollment where syear = ".$syear." and end_date IS NULL and grade_id = '".$class['id']."'");
	$squery->execute();
	$students = $squery->fetchAll(PDO::FETCH_ASSOC);
	print("<h1> - ".$class['title']." - </h1>");
	print("<table><tr><th>SID</th><th>name</th><th>Absent</th><th>Late</th></tr>");
	foreach($students as $student){
	   
		$sid = $student['student_id'];
		//print("Working with student_id: $sid <br/>");
	
	   //get total number of days present for selected student by
	   $qda = $sdbh->prepare("
		 SELECT count(attendance_period.school_date) as count from attendance_period,
		 (SELECT id from attendance_codes where syear=$syear AND school_id =2 AND title LIKE \"present\") as present_id
		 WHERE
		 attendance_period.attendance_code = present_id.id AND student_id = $sid AND school_date>='"
		 .$sdate."' AND school_date<='".$edate."'");
		
	   $qda->execute();
	   $dares = $qda->fetch();

	   //get total number of days tardy
	   $qdt = $sdbh->prepare("
		 SELECT count(attendance_period.school_date) as count from attendance_period,
		 (SELECT id from attendance_codes where syear=$syear AND school_id =2 AND title LIKE \"late\") as late_id
		 WHERE
		 attendance_period.attendance_code = late_id.id AND student_id = $sid AND school_date>='"
		 .$sdate."' AND school_date<='".$edate."'");
	   $qdt->execute();
	   $dtres = $qdt->fetch();

	   //get student name
	   $sq = $sdbh->prepare("select first_name, last_name from students where student_id = '$sid'");
	   $sq->execute();
	   $sname = $sq->fetch();

	   $student_sdays = $res['count'];
	   $student_dt = $dtres['count'];
	   $student_da = $student_sdays - $dares['count'] - $dtres['count'];

	   print("<tr><td>$sid</td><td>".$sname['first_name']." ".$sname['last_name']."</td><td>$student_da</td><td>$student_dt</td></tr>");

           //don't want students who left to count against us	
	   if($student_da<$threshold){

		   $sdays += $res['count'];
		   $da += $dares['count'];
		   $dt += $dtres['count'];
	
		   
		   //load them up	
		   $total_sdays += $res['count'];
		   $total_da += $dares['count'];
		   $total_dt += $dtres['count'];
	

	  	   $student_count++;
	   }
	 }//end processing individual students
	print("</table>");

	//days absent are the total days - days present - days tardy (that is: all attendance codes that aren't 'present' or 'late')
	$tda = $sdays - $da - $dt;
	
	if($student_count){
		print("<h2>Number of Students: $student_count</h2>");
		print("<h2>Total Days: $sdays<br/>Total Tardies: $dt<br/>Total Absences: $tda<br/>Percent Tardy:".round(100*$dt/$sdays)."%<br/>Percent Absent: ".round(100*$tda/$sdays)."%<br/><br/></h2>");
		}

   }// end processing class
  
 


$sdays = $total_sdays;


//tardies are tardies!
$dt = $total_dt;


//days absent are the total days - days present - days tardy (that is: all attendance codes that aren't 'present' or 'late')
$da = $total_sdays - $total_da - $dt;



print("<h2><br/>Total Days: $sdays<br/>Total Tardies: $dt<br/>Total Absences: $da<br/>Percent Tardy:".round(100*$dt/$sdays)."%<br/>Percent Absent: ".round(100*$da/$sdays)."%<br/></h2>");


?>
