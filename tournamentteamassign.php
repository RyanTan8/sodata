<?php
require_once  ("../connectsodb.php");
require_once  ("checksession.php"); //Check to make sure user is logged in and has privileges
userCheckPrivilege(1);
require_once  ("functions.php");

$output = "";
$teamID = intval($_POST['myID']);
if(empty($teamID))
{
	echo "<div style='color:red'>teamID is not set.</div>";
	exit();
}

//Get team and tournament row information
$query = "SELECT * FROM `team` INNER JOIN `tournament` ON `team`.`tournamentID`=`tournament`.`tournamentID` WHERE `teamID` = $teamID";
$resultTeam = $mysqlConn->query($query) or error_log("\n<br />Warning: query failed:$query. " . $mysqlConn->error. ". At file:". __FILE__ ." by " . $_SERVER['REMOTE_ADDR'] .".");
$rowTeam = $resultTeam->fetch_assoc();

//Get tournament times
$query = "SELECT * FROM `timeblock` WHERE `tournamentID` = ".$rowTeam['tournamentID']." ORDER BY `timeStart`";
$result = $mysqlConn->query($query) or error_log("\n<br />Warning: query failed:$query. " . $mysqlConn->error. ". At file:". __FILE__ ." by " . $_SERVER['REMOTE_ADDR'] .".");
if(mysqli_num_rows($result))
{
	$output .="<h2>";
	if(userHasPrivilege(3)){
		$output .="Adjust Teammate Assignments";
	}
	else{
		if($rowTeam['dateTournament']<date("Y-m-d")){
			//Show results as title after tournament date
			$output .="<h2>Results</h2>";
		}
		else {
			//Show schedule as title before and during tournament date
			$output .="<h2>Schedule</h2>";
		}
	}
		$output .=" <span id='myTitle'>".$rowTeam['tournamentName'].": ".$rowTeam['teamName']."</span></h2><div id='note'></div>";
	$output .="<form id='changeme' method='post' action='tournamentChangeMe.php'><table>";
	$timeblocks = [];
	while ($row = $result->fetch_assoc()):
		$query = "SELECT * FROM `tournamenttimechosen` INNER JOIN `tournamentevent` ON `tournamenttimechosen`.`tournamenteventID`=`tournamentevent`.`tournamenteventID` INNER JOIN `event` ON `tournamentevent`.`eventID`=`event`.`eventID` WHERE `timeblockID` = ".$row['timeblockID'];
		$resultEvents = $mysqlConn->query($query) or error_log("\n<br />Warning: query failed:$query. " . $mysqlConn->error. ". At file:". __FILE__ ." by " . $_SERVER['REMOTE_ADDR'] .".");
		$events = [];
		while ($rowEvent = $resultEvents->fetch_assoc()):
			$rowEvent['eventTotal']=0;
			array_push($events, $rowEvent);
		endwhile;
		$row['events'] = $events; //add count of events in timeblock
		array_push($timeblocks, $row);
	endwhile;

	//Run through times and figure out the number of different dates and print columns with colspan of times for that date
	$output .="<tr><th rowspan='3' style='vertical-align:bottom;'>Students</th>";
	$dateCheck = "";
	$dateI = 0;
	for ($i = 0; $i < count($timeblocks); $i++) {
		$eventNumber = count($timeblocks[$i]['events'])>0?count($timeblocks[$i]['events']):1;
		if($dateCheck==""){
			$dateCheck=date("F j, Y",strtotime($timeblocks[$i]["timeStart"]));
			$dateI = $eventNumber;
		}
		else {
			if($dateCheck!=date("F j, Y",strtotime($timeblocks[$i]["timeStart"]))){
				$output .= "<th colspan='$dateI' style='text-align:center;'>" . $dateCheck . "</th>";
			}
			else {
				$dateI += $eventNumber;
			}
		}
	}
	$output .= "<th colspan='$dateI' style='text-align:center;'>" . $dateCheck . "</th>";
	$output .="<th rowspan='2' style='vertical-align:bottom;'>Total Events</th></tr>";

//print the time for each event and date
	$output .="<tr>";
	for ($i = 0; $i < count($timeblocks); $i++) {
		$eventNumber = count($timeblocks[$i]['events'])>0?count($timeblocks[$i]['events']):1;
		$output .= "<th id='timeblock-".$timeblocks[$i]['timeblockID']."' colspan='$eventNumber' style='background-color:".rainbow($i)."'>" . date("g:i A",strtotime($timeblocks[$i]["timeStart"])) ." - " . date("g:i A",strtotime($timeblocks[$i]["timeEnd"]))  . "</th>";
	}
	$output .="</tr>";

	//print the event under each time
	$output .="<tr>";
	for ($i = 0; $i < count($timeblocks); $i++) {
		$timeEvents= $timeblocks[$i]['events'];
		if($timeEvents)
		{
			for ($n = 0; $n < count($timeEvents); $n++) {
				$output .= "<th id='event-".$timeEvents[$n]['tournamenteventID']."' style='background-color:".rainbow($i)."'>".$timeEvents[$n]['event']."</th>";
			}
		}
		else {
			$output .= "<th style='background-color:".rainbow($i)."'></th>";
		}

	}
	$output .="<td id='studenttotal-".$rowStudent['studentID']."'></td></tr>";

	//Get students
	$query = "SELECT * FROM `teammate` INNER JOIN `student` ON `teammate`.`studentID`=`student`.`studentID` WHERE `teamID` = $teamID ORDER BY `last` ASC, `first` ASC";
	$resultStudent = $mysqlConn->query($query) or error_log("\n<br />Warning: query failed:$query. " . $mysqlConn->error. ". At file:". __FILE__ ." by " . $_SERVER['REMOTE_ADDR'] .".");
	if(mysqli_num_rows($resultStudent))
	{
		while ($rowStudent = $resultStudent->fetch_assoc()):
			$studentTotal = 0;
			$output .="<tr>";
			$output .="<td id='teammate-".$rowStudent['studentID']."'>".$rowStudent['last'].", " . $rowStudent['first'] ."</td>";
			for ($i = 0; $i < count($timeblocks); $i++) {
				$timeEvents= $timeblocks[$i]['events'];
				if($timeEvents)
				{
					for ($n = 0; $n < count($timeEvents); $n++) {
						$checkbox = "teammateplace-".$timeEvents[$n]['tournamenteventID']."-".$rowStudent['studentID']."-".$teamID;
						$checkboxEvent = "teammateEvent-".$timeEvents[$n]['tournamenteventID']." "."teammateStudent-".$rowStudent['studentID'];

						$query = "SELECT * FROM `teammateplace` WHERE `tournamenteventID` =  ".$timeEvents[$n]['tournamenteventID']." AND `studentID` = ".$rowStudent['studentID']." AND `teamID` = $teamID";
						$resultTeammateplace = $mysqlConn->query($query) or error_log("\n<br />Warning: query failed:$query. " . $mysqlConn->error. ". At file:". __FILE__ ." by " . $_SERVER['REMOTE_ADDR'] .".");
						$output .="<td style='background-color:".rainbow($i)."' class='$checkboxEvent'>";
						$checked = mysqli_num_rows($resultTeammateplace)?" checked ":"";
						$timeblocks[$i]['events'][$n]['eventTotal'] +=$checked?1:0;
						$studentTotal +=$checked?1:0;
						if(userHasPrivilege(3)){
							$output .= "<input type='checkbox' onchange='javascript:tournamentEventTeammate($(this))' id='$checkbox' name='$checkbox' value='' $checked>";
						}
						else {
							$output .=$checked?"<div class='fa'>&#xf00c;</div>":"";
						}
						$output .="</td>";
					}
				}
				else {
					$output .= "<td style='background-color:".rainbow($i)."'></th>";
				}

			}
			$output .="<td id='studenttotal-".$rowStudent['studentID']."'>$studentTotal</td></tr>";
		endwhile;
	}
	else {
		exit("Make sure to add students to this team before this step!");
	}

	//print the total signed up for each event
	$output .="<tr><td>Total Teammates</td>";
	for ($i = 0; $i < count($timeblocks); $i++) {
		$timeEvents= $timeblocks[$i]['events'];
		if($timeEvents)
		{
			for ($n = 0; $n < count($timeEvents); $n++) {
				$output .= "<td id='eventtotal-".$timeEvents[$n]['tournamenteventID']."' style='background-color:".rainbow($i)."'>".$timeEvents[$n]['eventTotal']."</td>";
			}
		}
		else {
			$output .= "<td style='background-color:".rainbow($i)."'></td>";
		}
	}
	$output .="</tr>";

	//print the total signed up for each event
	$output .="<tr><td>Place</td>";
	for ($i = 0; $i < count($timeblocks); $i++) {
		$timeEvents= $timeblocks[$i]['events'];
		if($timeEvents)
		{
			for ($n = 0; $n < count($timeEvents); $n++) {
				$placeName = "placement-".$timeEvents[$n]['tournamenteventID']."--".$teamID;//do not put studentID here the -- makes this null
				$query = "SELECT * FROM `teammateplace` WHERE `tournamenteventID` =  ".$timeEvents[$n]['tournamenteventID']." AND `teamID` = $teamID";
				$resultTeammateplace = $mysqlConn->query($query) or error_log("\n<br />Warning: query failed:$query. " . $mysqlConn->error. ". At file:". __FILE__ ." by " . $_SERVER['REMOTE_ADDR'] .".");
				$place="";
				if(mysqli_num_rows($resultTeammateplace))
				{
					$rowPlace = $resultTeammateplace->fetch_assoc();
				}
				$output .= "<td style='background-color:".rainbow($i)."'>";
				if(userHasPrivilege(3)){
					$output .= "<input id='$placeName' name='$placeName' type='number' onchange='javascript:tournamentEventTeammate($(this))' value='".$rowPlace['place']."'/>";
				}
				else {
					$output .= $rowPlace['place'];
				}
				$output .= "</td>";
			}
		}
		else {
			$output .= "<td style='background-color:".rainbow($i)."'></td>";
		}
	}
	$output .="</tr>";

	$output .="</table></form>";
}
else {
	exit("<div>Set available time blocks first!</div>");
}
echo $output;
?>
<br>
<form id="addTo" method="post" action="tournamenteventadd.php">
	<p>
		<input class="button" type="button" onclick="window.history.back()" value="Return" />
	</p>
</form>