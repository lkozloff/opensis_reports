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
$threshold = 30;

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

print("Date range: $sdate - $edate\n");
print("Number of school days in range: ".($daycount['count']-$futuredaycount['count']));
if($futuredaycount['count'] >0){ 
	print("\n(".$futuredaycount['count']." occur in the future and will not be calculated)\n");
	$edate = date("Y-m-d",strtotime("Today"));
	print("effective date range: $sdate - $edate\n");
}

print("\n");

//get total number of days per marking period from attendance calendar with the new effective date
$q = $sdbh->prepare("SELECT COUNT(*) as count from attendance_calendar where syear=$syear AND school_id=2 AND school_date>='"
 .$sdate."' AND school_date<='".$edate."'");
$q->execute();
$res = $q->fetch();

print("Threshold set to $threshold, absences exceeding this amount will not count towards the totals. \n\n");

   foreach($classes as $class){
	
	$sdays = 0;
	$da = 0;
	$dt = 0;
	$du = 0;
	$student_count = 0;
	
	$squery = $sdbh->prepare("SELECT * from student_enrollment where syear = ".$syear." and end_date IS NULL and grade_id = '".$class['id']."'");
	$squery->execute();
	$students = $squery->fetchAll(PDO::FETCH_ASSOC);
	print(" - ".$class['title']." - \n");
	foreach($students as $student){
	   
		$sid = $student['student_id'];
		//print("Working with student_id: $sid \n");
	
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

	   print("\t\t$sid\t\t".$sname['first_name']." ".$sname['last_name']."\t\t\t\tAbsent: $student_da\tTardy: $student_dt\n");

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

	//days absent are the total days - days present - days tardy (that is: all attendance codes that aren't 'present' or 'late')
	$tda = $sdays - $da - $dt;
	
	if($student_count){
		print("Number of Students: $student_count\n");
		print("Total Days: $sdays\nTotal Tardies: $dt\nTotal Absences: $tda\nPercent Tardy:".round(100*$dt/$sdays)."%\nPercent Absent: ".round(100*$tda/$sdays)."%\n\n");
		}

   }// end processing class
  
 


$sdays = $total_sdays;


//tardies are tardies!
$dt = $total_dt;


//days absent are the total days - days present - days tardy (that is: all attendance codes that aren't 'present' or 'late')
$da = $total_sdays - $total_da - $dt;



print("*** SUMMARY ***\nTotal Days: $sdays\nTotal Tardies: $dt\nTotal Absences: $da\nPercent Tardy:".round(100*$dt/$sdays)."%\nPercent Absent: ".round(100*$da/$sdays)."%\n");


?>
