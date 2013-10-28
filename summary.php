<?php session_start(); 
 include("connectdb.php");
    $db = connectDB();
?>
<html>
<head><link rel = "stylesheet" type="text/css" href="style.css"/>
 <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var LISdata = google.visualization.arrayToDataTable([
            <?php ethnicBreakdown(1,$db); ?>
        ]);

        var LISoptions = {
          title: 'LIS Passport Breakdown',
          colors: ['#0094FF', '#248EFF', '#61ADFF', '#94C8FF', '#D6EAFF'],
        };

        var LISchart = new google.visualization.PieChart(document.getElementById('logoschart'));
        LISchart.draw(LISdata, LISoptions);

 	var AHISdata = google.visualization.arrayToDataTable([
            <?php ethnicBreakdown(2,$db); ?>
        ]);

        var AHISoptions = {
          title: 'AHIS Passport Breakdown',
          colors: ['#25C700', '#2DF000', '#2DF141', '#91F57A', '#D6EAFF'],
        };

        var AHISchart = new google.visualization.PieChart(document.getElementById('ahischart'));
        AHISchart.draw(AHISdata, AHISoptions);

	var AHdata = google.visualization.arrayToDataTable([
            <?php ethnicBreakdown('%',$db); ?>
        ]);

        var AHoptions = {
          title: 'Total Passport Breakdown',
        };

        var AHchart = new google.visualization.PieChart(document.getElementById('ahchart'));
        AHchart.draw(AHdata, AHoptions);

      }
      </script>
</head>
<body>
<?php
   

    //Logos
    ?>
    <table>
    <tr style="border: none;"><td colspan=2>
    <div id="logos">
    <h1>Logos International School</h1>
    <h2>Student Body Summary</h2></tr>
    <tr style="border: none;"><td><?php studentBodySummary(1,$db); ?></td><td>
    <div id="logoschart" style="width: 300px; height: 300px;"></div></td></tr>
    </div>

    <?php
    //AHIS
    ?>
    <tr style ="border: none;"><td colspan=2>
    <div id="ahis">
    <h1>AH International School</h1>
    <h2>Student Body Summary</h2></tr>
    <tr style="border: none;"><td><?php studentBodySummary(2,$db); ?></td><td>
    <div id="ahischart" style="width: 300px; height: 300px;"></div></td></tr>
    </div>
    </table>

<?php
function studentBodySummary($school_id=1,$db){
    $syear = 2013;
print("<table class=\"holder\"><tr><td><table><tr><th>&nbsp;</th</tr><tr><td>F</td></tr><tr><td>M</td></tr><tr><td>Total</td></tr><tr><td>Ratio</td></tr></table></td>");

    $q = $db->prepare("
        SELECT
        grades.grade,
        grades.grade_id,
        grades.sort_order
        FROM 
            (SELECT DISTINCT grade_id FROM student_enrollment WHERE school_id=$school_id AND syear=$syear) 
        as levels,
            (SELECT school_gradelevels.title as grade, 
                    school_gradelevels.id as grade_id, 
                    school_gradelevels.sort_order as sort_order 
            FROM school_gradelevels) 
        as grades
        WHERE
            levels.grade_id = grades.grade_id
        ORDER BY sort_order ASC
    ");

    $q->execute();
    $res = $q->fetchAll();

    $total_students = 0;
    $males = 0;
    
    foreach($res as $grade){
        $grade_id = $grade['grade_id'];
        $gstudents = 0;
        $gmales = 0;
        $gfemales =0;
              
        $q = $db->prepare("
            SELECT 
            enrolled_students.sid,
            enrolled_students.gender,
            enrolled_students.passport
            FROM

            (SELECT students.student_id as sid, students.ethnicity as passport, students.gender as gender,student_enrollment.grade_id as grade_id 
            FROM students, student_enrollment
            WHERE 
            student_enrollment.student_id = students.student_id AND student_enrollment.syear = $syear AND student_enrollment.school_id=$school_id
            AND (student_enrollment.end_date IS NULL OR student_enrollment.end_date >'".date("Y-m-d",strtotime("Today"))."')
            AND student_enrollment.grade_id=$grade_id
            )
            as enrolled_students,

            (SELECT school_gradelevels.title as grade, school_gradelevels.id as grade_id from school_gradelevels) 
            as grade

            WHERE
            enrolled_students.grade_id = grade.grade_id
       ");
             
        $q->execute();
        $class = $q->fetchAll();
        $gstudents = count($class);
        foreach($class as $member) if(strcmp($member['gender'],"Male")==0)$gmales++;
        $gfemales = $gstudents - $gmales;

        $males+=$gmales;
        $total_students+=$gstudents;

        print("<td><table>");
        print("<tr><th>".$grade['grade']."</th></tr>");
        print("<tr><td>$gfemales</td></tr>");
        print("<tr><td>$gmales</td></tr>");
        print("<tr><td>$gstudents</td></tr>");
        print("<tr><td style=\"background-color:rgb(150,150,220);\"><div style=\"width:".(($gfemales/$gstudents)*100)."%; background-color:rgb(220,150,150);\">".round($gfemales/$gstudents,2)."</div></td></tr>");
        print("</table></td>");
        
    }

    $females = $total_students-$males;

    
 
        print("<td><table>");
        print("<tr><th>Grand Totals</th></tr>");
        print("<tr><td>$females</td></tr>");
        print("<tr><td>$males</td></tr>");
        print("<tr><td>$total_students</td></tr>");
        print("<tr><td style=\"background-color:rgb(150,150,220);\"><div style=\"width:".(($females/$total_students)*100)."%; background-color:rgb(220,150,150);\">".round($females/$total_students,2)."</div></td></tr>");
        print("</table></td>");
        print("</tr></table>");
} 

function ethnicBreakdown($school_id, $db){
       $q = $db->prepare("
       SELECT students.ethnicity as passport, count(*) as count

       from students, student_enrollment
       WHERE 
            student_enrollment.student_id = students.student_id
       AND  
            student_enrollment.syear = 2013
       AND
            student_enrollment.school_id LIKE '$school_id'
       AND 
            (student_enrollment.end_date IS NULL OR student_enrollment.end_date >'".date("Y-m-d",strtotime("Today"))."')

       group by passport
    ");
    $q->execute();
    $res = $q->fetchAll();
    $total = 0;
    $other= 0;
    print("['Passport','Count'],");
    foreach($res as $val){
       if($val['count']<10) $other+=$val['count'];
       else
        print("['".$val['passport']."',".$val['count']."],");
        $total+=$val['count'];

    }

    print("['Other',$other]");
}

?>
        <div id="ahchart" style="width: 100%; height: 300px;"></div>
</html>
