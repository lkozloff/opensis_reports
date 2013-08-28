<?php
include("connectdb.php");

$sdbh = connectDB();
$syear = 2012;

$total_sdays = Array();
$total_da = Array();
$total_dt = Array();
$total_du = Array();
$student_count = 0;

//when do we assume that a student shouldn't count against the percentages?
$threshold = 30;

//where do s1 and s2 begin? (will have to re-evaluate for a Logos version where we go by quarter(?))
$query = $sdbh->prepare("SELECT * FROM marking_periods where school_id=2 AND syear = $syear AND parent_id >0");
$query->execute();
$mp_result = $query->fetchAll(PDO::FETCH_ASSOC);
$i = 1;

//Figure out classes available
$classq = $sdbh->prepare("SELECT * from school_gradelevels where school_id=2 order by sort_order asc");
$classq->execute();
$classes = $classq->fetchAll(PDO::FETCH_ASSOC);


//this runs once per marking period -
foreach($mp_result as $val){
   $sdate = $val['start_date'];
   $edate = $val['end_date'];
   print("Date range: $sdate - $edate\n");

   //get total number of days per marking period from attendance calendar (all the way to the end of the year)
   $q = $sdbh->prepare("SELECT COUNT(*) as count from attendance_calendar where syear=$syear AND school_id=2 AND school_date>='"
         .$sdate."' AND school_date<='".$edate."'");
   $q->execute();
   $res = $q->fetch();

   foreach($classes as $class){
	
	$sdays = 0;
	$da = 0;
	$dt = 0;
	$du = 0;
	$student_count = 0;
	
	$squery = $sdbh->prepare("SELECT * from student_enrollment where syear = 2012 and grade_id = '".$class['id']."'");
	$squery->execute();
	$students = $squery->fetchAll(PDO::FETCH_ASSOC);
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

	   //get the total number of unknown days
	   $q = $sdbh->prepare("SELECT COUNT(*) as count from attendance_calendar where syear=$syear AND school_id=2 AND school_date>='"
		 .date("Y-m-d",strtotime("Tomorrow"))."' AND school_date<='".$edate."'");

	   $q->execute();
	   $dures = $q->fetch();
	   
           //don't want students who left to count against us	
	   if(($res['count']-$dares['count'])<$threshold){
		   $sdays += $res['count'];
		   $da += $dares['count'];
		   $dt += $dtres['count'];
		   $du += $dures['count'];
		   //load them up	
		   $total_sdays[$i] += $res['count'];
		   $total_da[$i] += $dares['count'];
		   $total_dt[$i] += $dtres['count'];
		   $total_du[$i] += $dures['count'];

	  	   $student_count++;
	   }
	 }//end processing individual students

	//days absent are the total days - days present - days tardy (that is: all attendance codes that aren't 'present' or 'late')
	$da = $sdays - $da - $dt - $du;
	
	if($student_count){
		print(" - ".$class['title']." - \n");
		print("Number of Students: $student_count\n");
		print("Total Days: $sdays\nTotal Tardies: $dt\nTotal Absences: $da\nPercent Tardy:".round(100*$dt/$sdays)."%\nPercent Absent: ".round(100*$da/$sdays)."%\n\n");
		}

   }// end processing class
  
  

$i++;
}// end processing semester


$sdays1 = $total_sdays['1'];
$sdays2 = $total_sdays['2'];

//tardies are tardies!
$dt1 = $total_dt['1'];
$dt2 = $total_dt['2'];

//If we're not at the end of a marking period, we don't know about certain days - so we give them the benefit of the doubt
$unknownS1 = $total_du['1'];
$unknownS2 = $total_du['2'];

//days absent are the total days - days present - days tardy (that is: all attendance codes that aren't 'present' or 'late')
$da1 = $total_sdays['1'] - $total_da['1'] - $dt1 - $unknownS1;
$da2 = $total_sdays['2'] - $total_da['2'] - $dt2 - $unknownS2;


print("*** SUMMARY ***\n\t*S1*\nTotal Days: $sdays1\nTotal Tardies: $dt1\nTotal Absences: $da1\nPercent Tardy:".round(100*$dt1/$sdays1)."%\nPercent Absent: ".round(100*$da1/$sdays1)."%\n");
print("\t*S2*\nTotal Days: $sdays2\nTotal Tardies: $dt2\nTotal Absences: $da2\nPercent Tardy:".round(100*$dt2/$sdays2)."%\nPercent Absent: ".round(100*$da2/$sdays2)."%\n");

?>
