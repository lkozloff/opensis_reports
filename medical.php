<html>
<head>
    <script src="https://code.jquery.com/jquery-3.1.0.min.js" type="text/javascript"></script>
    <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.12/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">

    <script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.12/js/jquery.dataTables.js"></script>
    <script type="text/javascript" charset="utf8" src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

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
              student_enrollment.end_date IS NULL AND
              student_enrollment.grade_id = Grade.grade_id

            ");
            $q->execute();
            $result = $q->fetchAll();

            print("
            <div class='dataTable_wrapper'>
            <table id='medical-table' class='table table-striped table-hover'>
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
            print("</table></div>");
    }
    else{
      print "ah ah ah - you didn't say the magic word!";
    }
?>
</body>
<script src="js/sorting.js"></script>
<script type="text/javascript">
$(document).ready( function() {
      var table = $('#medical-table').DataTable({
         responsive: true,
         pageLength: 25,
         search: {caseInsensitive: false},
        
         columnDefs: [
                { type: 'grades', targets: 1 }
        ],
         buttons: [
           {
                    extend: 'csvHtml5',
                    text: 'TSV export <i class="fa fa-download"></i>',
                    className: 'btn btn-sm btn-success',
                    fieldSeparator: '\t',
                    extension: '.tsv',
                    title: 'IntakeSurveys'
                },

        ]
        });


      $('#start_date').change(function() {
        this.form.submit();
      });
      $('#end_date').change(function() {
        this.form.submit();
      });

});

</script>
</html>
