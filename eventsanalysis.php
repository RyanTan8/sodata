<?php
require_once  ("../connectsodb.php");
require_once  ("checksession.php"); //Check to make sure user is logged in and has privileges
userCheckPrivilege(3);
require_once  ("functions.php");

$output = "";
$year = isset($_POST['myID'])?intval($_POST['myID']):getCurrentSOYear();
$year =  $year?$year:getCurrentSOYear(); //if 0 is sent, it will be fixed here

function getEventResults($db, $SOyear)
{
	$SOyear = getIfSet($SOyear, getCurrentSOYear()); //this should not be null, but I included this just in case.

	$rows = [];
	$query = "SELECT * FROM `event` x
	INNER JOIN `eventyear` ON x.`eventID`=`eventyear`.`eventID`
	JOIN (SELECT `eventID`, `year` as tournamentyear, AVG(`place`) as avgplace, AVG(`score`) as avgscore FROM `tournamentevent`
	INNER JOIN `teammateplace` ON `tournamentevent`.`tournamenteventID`=`teammateplace`.`tournamenteventID`
         INNER JOIN `tournament` ON `tournamentevent`.`tournamentID`=`tournament`.`tournamentID` WHERE year = '$SOyear' GROUP BY `eventID`) y
    ON x.`eventID`= y.`eventID`
    WHERE `eventyear`.`year` = '$SOyear'
	ORDER BY `event`";
	$result = $db->query($query) or error_log("\n<br />Warning: query failed:$query. " . $db->error. ". At file:". __FILE__ ." by " . $_SERVER['REMOTE_ADDR'] .".");
	while($row = $result->fetch_assoc()):
		array_push($rows, $row);
	endwhile;
	return $rows;
}

function printTable($db, $events)
{
	$output = "";
	$notescore = 0;
	//Run through times and figure out the number of different dates and print columns with colspan of times for that date
	$output .="<table id='tournamentTable' class='tournament'><thead><tr>";
	$output .= "<th rowspan='1' style='vertical-align:bottom;'><div><a href='javascript:tournamentSort(`event`, 0)'>Events</a></div></th>";
	$output .= "<th rowspan='1' style='vertical-align:bottom;'><div><a href='javascript:tournamentSort(`avgplace`, 1)'>Average Place</a></div><div>(Lower is Better)</div></th>";
	$output .= "<th rowspan='1' style='vertical-align:bottom;'><div><a href='javascript:tournamentSort(`avgscore`, 1)'>Average Score</a></div><div>(Higher is Better)</div></th></tr></thead>";
	$output .="<tbody>";
	//Print events
	foreach ($events as $i=>$event) {
			$output .="<tr eventID=".$event['eventID']." event=".$event['event']." avgplace=".$event['avgplace']." avgscore=".$event['avgscore']."><td>".$event['event']."</td>";
			$output .="<td>".number_format($event['avgplace'],2)."</td>";
			$output .="<td>".number_format($event['avgscore'],2)."</td></tr>";
	}

	$output .="</tbody></table></form>";
	echo $output;
}

echo "<h2><span id='myTitle'>Event Analysis - $year</span></h2><div id='note'></div>";

$events = getEventResults($mysqlConn,$year);
printTable($mysqlConn, $events);

?>
<br>
<div>This checks the current science olympiad year only.</div>
<form id="addTo" method="post" action="tournamenteventadd.php">
	<p>
				<input class="button fa" type="button" onclick="window.history.back()" value="&#xf0a8; Return" />
	</p>
</form>