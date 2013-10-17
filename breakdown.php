<?
 include("pChart2.1.3/class/pData.class.php"); 
 include("pChart2.1.3/class/pDraw.class.php"); 
 include("pChart2.1.3/class/pPie.class.php"); 
 include("pChart2.1.3/class/pImage.class.php"); 
 include("connectdb.php");

$dbh = connectDB();
 /* find distinct ethnicities in enrolled students */
$q = $dbh->prepare("
SELECT DISTINCT
enrolled_students.passport

FROM

(SELECT students.student_id as sid, students.ethnicity as passport, student_enrollment.grade_id as grade_id from students, student_enrollment
WHERE 
student_enrollment.student_id = students.student_id AND student_enrollment.syear = 2013)
as enrolled_students
");
 $q->execute();
 $passports = $q->fetchAll();

$data = Array();
$labels = Array();
$others = 0;

 /* find counts by ethnicity */
foreach($passports as $ethnicity){
$passport = $ethnicity['passport'];
$q = $dbh->prepare("
SELECT
count(enrolled_students.passport) as count

FROM

(SELECT students.student_id as sid, students.ethnicity as passport, student_enrollment.grade_id as grade_id from students, student_enrollment
WHERE 
student_enrollment.student_id = students.student_id AND student_enrollment.syear = 2013)
as enrolled_students

WHERE
enrolled_students.passport='$passport'
");
 $q->execute();
 $count = $q->fetch();
if($count['count']>20){
 array_push($data,$count['count']);
 array_push($labels,$passport);
}
else{
 $others += $count['count'];
}
}
 array_push($data,$others);
 array_push($labels,"Other");


 /* Create and populate the pData object */ 
 $ahis_data = new pData();    
 $ahis_data->addPoints($data,"ScoreA");   
 $ahis_data->setSerieDescription("ScoreA","Application A"); 


 /* Define the absissa serie */ 
 $ahis_data->addPoints($labels,"Labels"); 
 $ahis_data->setAbscissa("Labels"); 

 /* Create the pChart object */ 
 $myPicture = new pImage(700,700,$ahis_data); 

 /* Draw a solid background */ 
 $Settings = array("R"=>220, "G"=>220, "B"=>220, "Dash"=>1, "DashR"=>180, "DashG"=>180, "DashB"=>180); 
 $myPicture->drawFilledRectangle(0,0,700,700,$Settings); 

 /* Draw a gradient overlay  */
 $Settings = array("StartR"=>100, "StartG"=>100, "StartB"=>100, "EndR"=>220, "EndG"=>220, "EndB"=>220, "Alpha"=>75); 
 $myPicture->drawGradientArea(0,0,700,700,DIRECTION_VERTICAL,$Settings); 
 $myPicture->drawGradientArea(0,0,700,75,DIRECTION_VERTICAL,array("StartR"=>0,"StartG"=>0,"StartB"=>0,"EndR"=>50,"EndG"=>50,"EndB"=>50,"Alpha"=>100)); 

 /* Add a border to the picture */ 
 $myPicture->drawRectangle(0,0,699,699,array("R"=>0,"G"=>0,"B"=>0)); 

 /* Write the picture title */  
 $myPicture->setFontProperties(array("FontName"=>"/usr/share/fonts/truetype/freefont/FreeSans.ttf","FontSize"=>22)); 
 $myPicture->drawText(10,40,"AH Schools Breakdown by Ethnicity",array("R"=>255,"G"=>255,"B"=>255)); 

 /* Set the default font properties */  
 $myPicture->setFontProperties(array("FontName"=>"/usr/share/fonts/truetype/freefont/FreeSans.ttf","FontSize"=>20,"R"=>0,"G"=>0,"B"=>0)); 

 /* Enable shadow computing */  
 $myPicture->setShadow(TRUE,array("X"=>2,"Y"=>2,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>50)); 

 /* Create the pPie object */  
 $PieChartAHIS = new pPie($myPicture,$ahis_data); 

 $PieChartAHIS->draw2DPie(350,350,array("DrawLabels"=>TRUE,"LabelStacked"=>TRUE,"Border"=>TRUE,"Radius"=>150,"WriteValues"=>PIE_VALUE_PERCENTAGE,"ValuePosition"=>PIE_VALUE_INSIDE,"ValueR"=>0,"ValueG"=>0,"ValueB"=>0)); 

 /* Write the legend */ 
 $myPicture->setFontProperties(array("FontName"=>"/usr/share/fonts/truetype/freefont/FreeSans.ttf","FontSize"=>16)); 
 $myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>0,"G"=>0,"B"=>0,"Alpha"=>20)); 

 /* Write the legend box */  
 $myPicture->setFontProperties(array("FontName"=>"/usr/share/fonts/truetype/freefont/FreeSans.ttf","FontSize"=>16,"R"=>255,"G"=>255,"B"=>255)); 
 $PieChartAHIS->drawPieLegend(260,50,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL)); 

 /* Render the picture (choose the best way) */ 
 $myPicture->autoOutput("pictures/example.draw2DPie.png"); 
?>
