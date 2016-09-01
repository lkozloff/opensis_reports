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
    include_once("data.php");
    if($_REQUEST['key'] == $SecretKey){
            //defaults for first and last
            $d = new DateTime('first day of this month');
            $first = $d->format('Y-m-d');

            $d = new DateTime('last day of this month');
            $last = $d->format('Y-m-d');

            if(isset($_REQUEST['start_date']))
              $first = $_REQUEST['start_date'];


            if(isset($_REQUEST['end_date']))
              $last = $_REQUEST['end_date'];


            print "<h1>Medical Report <small>(Generated ".date('Y-m-d').")</small></h1>";



?>
            <form method="post" action="<?php echo $_SERVER['HTTP_REFERER'];?>"  >
              <label>Start</label>
              <input type="date" id="start_date" name="start_date" value="<?php echo $first;?>"/>
              <label>End</label>
              <input type="date" id="end_date" name="end_date" value="<?php echo $last; ?>"/>
              <input type="hidden" name="key" value="<?php echo $_REQUEST['key']; ?>"/>
            </form>
<?php




            $db = connectDB();
            $q = $db->prepare("
              select
               IF(student_enrollment.school_id='1','LIS','AHIS') as school,
               Grade.grade,
               school_date as date,
               students.student_id,
               students.first_name,
               students.last_name,
               reason, result

              from
                 students,
                 student_medical_visits,
                 student_enrollment,

              (SELECT
              school_gradelevels.short_name as grade,
              school_gradelevels.id as grade_id
              FROM school_gradelevels) as Grade

              where school_date>='".$first."' AND school_date<='".$last."' AND
              students.student_id = student_medical_visits.student_id AND
              student_enrollment.student_id = students.student_id AND
              student_enrollment.syear = (SELECT MAX(syear) from school_years) AND
              student_enrollment.grade_id = Grade.grade_id

            ");
            $q->execute();
            $result = $q->fetchAll();

            print("
            <table id = 'example-table'>
        	  <thead><tr>
                		<th style='width:4em;'>School</th>
                		<th style='width:3em;'>Grade</th>
                		<th>Date</th>
                		<th style='width:4em;'>SID</th>
                		<th>Name</th>
                		<th>Reason</th>
                		<th>Result</th>
                  </tr>
            </thead>");
            foreach($result as $row){
                $date= $row['date'];
                $sid = $row['student_id'];
                $sname= $row['last_name'];
                $fname = $row['first_name'];
                $reason= $row['reason'];
                $med_result= $row['result'];
                $school= $row['school'];
                $grade= $row['grade'];

            print("
            <tr>
          		<td>$school</td>
          		<td>$grade</td>
          		<td>$date</td>
          		<td>$sid</td>
          		<td>$sname".", "."$fname</td>
          		<td>$reason</td>
          		<td>$med_result</td>

        		</tr>");
            }
            print("</table>");
    }
    else{
      print "ah ah ah - you didn't say the magic word!";
    }
?>
</body>
<script type="text/javascript">
$(document).ready( function() {

      $('#start_date').change(function() {
        this.form.submit();
      });
      $('#end_date').change(function() {
        this.form.submit();
      });

});

</script>
</html>
