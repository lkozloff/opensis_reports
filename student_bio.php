<html>
<head>
    <script src="js/jquery-1.7.2.min.js" type="text/javascript"></script>
    <script src="js/jquery.dynatable.js" type="text/javascript"></script>
     <style>
    body{
	font-family: sans-serif;
	}
    table {
      border: solid 1px #ccc;    border-collapse: collapse;
      border-spacing: 0;
      position: relative; /* Add positioning */
	font-size: small;
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
		student_enrollment.school_id as school_id,
		students.student_id as student_id,
		students.last_name as last_name, 
		students.first_name as first_name, 
		students.common_name as common_name,
		students.ethnicity as passport

	from students, student_enrollment

	WHERE 
	student_enrollment.student_id = students.student_id
	AND 
	student_enrollment.syear = 2013
	AND
	student_enrollment.school_id=1
	AND 
	(student_enrollment.end_date IS NULL OR student_enrollment.end_date >'2013-12-25')

	order by passport
    ");
    $q->execute();
    $result = $q->fetchAll();

    print("
	<table id = 'example-table'>
	<thead><tr>
		<th>School</th>
		<th>SID</th>
		<th>Surname</th>
		<th>First Name</th>
            	<th>Common Name</th>
		<th>Passport</th></tr></thead>");
    foreach($result as $row){
        $sid = $row['student_id'];
        $sname= $row['last_name'];
        $fname = $row['first_name'];
	$cname = $row['common_name'];
        $passport = $row['passport'];
	$school_id = $row['school_id'];

    print("
	<tr>
		<td>$school_id</td>
		<td>$sid</td>
		<td>$sname</td>
		<td>$fname</td>
            	<td>$cname</td>
		<td>$passport</td>
	</tr>");
    }
    print("</table>");

?>
</body>
