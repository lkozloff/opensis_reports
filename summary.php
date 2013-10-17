<html>
<head><link rel = "stylesheet" type="text/css" href="style.css"/>
</head>
<body>

<?php
    include("connectdb.php");
    $db = connectDB();
    studentBodySummary(1,$db);
    print("<br>");
    studentBodySummary(2,$db);
function studentBodySummary($school_id=1,$db){
    $syear = 2013;
    $q = $db->prepare("SELECT title from schools where id=$school_id");
    $q->execute();
    $r = $q->fetch();
    $school_name = $r['title'];
    print("<h1>$school_name</h1>");
print("<table><tr><td><table><tr><th>&nbsp;</th</tr><tr><td>M</td></tr><tr><td>F</td></tr><tr><td>Total</td</tr></table></td>");

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
        print("<tr><td>$gmales</td></tr>");
        print("<tr><td>$gfemales</td></tr>");
        print("<tr><td>$gstudents</td></tr>");
        print("</table></td>");
        
    }

    $females = $total_students-$males;

    
 
        print("<td><table>");
        print("<tr><th>Grand Totals</th></tr>");
        print("<tr><td>$males</td></tr>");
        print("<tr><td>$females</td></tr>");
        print("<tr><td>$total_students</td></tr>");
        print("</table></td>");
        print("</tr></table>");
} 
?>
</html>
