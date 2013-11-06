<html>
<head>
    <script src="js/jquery-1.7.2.min.js" type="text/javascript"></script>
    <script src="js/jquery.dynatable.js" type="text/javascript"></script>
     <style>
    /* Example Styles for Demo */
    table {
      border: solid 1px #ccc;    border-collapse: collapse;
      border-spacing: 0;
      position: relative; /* Add positioning */
    }
    td, th {
      border-bottom: solid 1px #eee;
      border-top: solid 1px #ccc;
      border-left: solid 1px #eee;
      border-right: solid 1px #ccc;
      padding: 10px 15px;
    }
    th {
      background: #eee;
      width: 150px;
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

    $q = $db->prepare("
	SELECT 
	enrolled_students.student_id, 
	enrolled_students.last_name, 
	enrolled_students.first_name, 
	Grade.grade, 
	ROUND( AVG( student_report_card_comments.comment ) , 2 ) AS average_effort

	FROM 
		(SELECT 
			students.first_name AS first_name, 
			students.last_name AS last_name, 
			students.student_id AS student_id, 
			student_enrollment.grade_id AS grade_id
		FROM 
			students, student_enrollment
		WHERE 
			student_enrollment.student_id = students.student_id
			AND student_enrollment.school_id =1
			AND student_enrollment.syear =2013
		) AS 
	enrolled_students, 

		(	SELECT school_gradelevels.title AS grade, 
			school_gradelevels.id AS grade_id
			FROM school_gradelevels
		) AS 
	Grade, 
	student_report_card_comments

	WHERE 
		enrolled_students.grade_id = Grade.grade_id
		AND enrolled_students.student_id = student_report_card_comments.student_id
		AND student_report_card_comments.comment IS NOT NULL 
		AND student_report_card_comments.marking_period_id =76
	
	GROUP BY student_id ASC 
	ORDER BY grade ASC
    ");
    $q->execute();
    $result = $q->fetchAll();

    print("<table id = 'example-table'><thead><tr><th>SID</th><th>Surname</th><th>First Name</th><th>Grade</th>
            <th>Effort</th></tr></thead>");
    foreach($result as $row){
        $sid = $row['student_id'];
        $sname= $row['last_name'];
        $fname = $row['first_name'];
        $grade = $row['grade'];
        $effort = $row['average_effort'];

    print("<tr><td>$sid</td><td>$sname</td><td>$fname</td><td>$grade</td><td>$effort</td></tr>");
    }
    print("</table>");

?>
</body>
