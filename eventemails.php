<?php
header("Content-Type: text/plain");
require_once  ("php/functions.php");
userCheckPrivilege(1);
$eventID = intval($_POST['myID']);
$year = getCurrentSOYear();
$studentIDWhere = "";
if(isset($studentID))
{
	$studentIDWhere ="AND `student`.`studentID` != $studentID";
}

//Use Tournament ID to Find Student List
function getEventStudentListTournament($year, $tournamentID, $eventID)
{
	$query = "SELECT `student`.`studentID`, `student`.`last`, `student`.`first`, `student`.`email`, `student`.`emailSchool`, `event`.`event` FROM `tournamentevent`
	INNER JOIN `event` ON `tournamentevent`.`eventID` = `event`.`eventID`
	INNER JOIN `teammateplace` ON `tournamentevent`.`tournamenteventID` = `teammateplace`.`tournamenteventID`
	INNER JOIN `student` ON `teammateplace`.`studentID` = `student`.`studentID`
	WHERE `tournamentevent`.`tournamentID`=$tournamentID AND `tournamentevent`.`eventID`=$eventID
	ORDER BY `student`.`last`, `student`.`first`";
	return printEmailTable($query, $eventID, $year);
}

//Print All students that have competed in an event, exclude graduated students
function getEventStudentListAllCompetitors($eventID, $year)
{
	global $schoolID;
	$query = "SELECT DISTINCT `student`.`studentID`, `student`.`last`, `student`.`first`, `student`.`email`, `student`.`emailSchool`, `event`.`event` FROM `tournament` 
	INNER JOIN `tournamentevent` USING (`tournamentID`) 
	INNER JOIN `event` USING (`eventID`) 
	INNER JOIN `teammateplace` USING (`tournamenteventID`) 
	INNER JOIN `student` USING (`studentID`) 
	WHERE `student`.`schoolID`=$schoolID AND `student`.`active`=1 AND `tournamentevent`.`eventID`=$eventID AND `tournament`.`notCompetition`=0 AND `student`.`yearGraduating`>= $year";
	return printEmailTable($query, $eventID, $year);
}

$eventName = getEventName($eventID);
$output="<h2>$eventName</h2>";

//Find Roster tournaments (not competitions)
$tournament=getLatestTournamentNCSchoolID($year);
if($tournament)
{
    $output .= "<h3>". $tournament["tournamentName"] ."</h3>";
	$output .= getEventStudentListTournament($year, $tournament['tournamentID'],$eventID);
}

//Print all students who have competed even those not assigned to the event
$allcompetitors = getEventStudentListAllCompetitors($eventID, $year);
if($allcompetitors)
{
    $output .= "<h3>All Active Students that have Competed in this Event</h3>";
    $output .= $allcompetitors;
}

$output.= "<p><button class='btn btn-outline-secondary' onclick='window.history.back()' type='button'><span class='bi bi-arrow-left-circle'></span> Return</button></p>";
echo $output;
?>
