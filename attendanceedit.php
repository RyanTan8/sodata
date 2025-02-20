<?php
require_once  ("php/functions.php");
userCheckPrivilege(2);

$meetingID = intval($_POST['myID']);
if(isset($meetingID))
{
	$query = "SELECT * FROM `meeting` WHERE `meetingID` = $meetingID";
	$result = $mysqlConn->query($query) or error_log("\n<br />Warning: query failed:$query. " . $mysqlConn->error. ". At file:". __FILE__ ." by " . $_SERVER['REMOTE_ADDR'] .".");
	$row = $result->fetch_assoc();
	$eventID = $row['eventID'];
    $meetingTypeID = $row['meetingTypeID'];

}
function loadAttendanceData($meetingID)
{
    global $mysqlConn;
    $attendanceQuery = "SELECT * FROM `meetingattendance` INNER JOIN `student` ON `student`.`studentID` = `meetingattendance`.`studentID` WHERE `meetingattendance`.`meetingID` = $meetingID";
    $result = $mysqlConn->query($attendanceQuery) or error_log("\n<br />Warning: query failed:$attendanceQuery. " . $mysqlConn->error. ". At file:". __FILE__ ." by " . $_SERVER['REMOTE_ADDR'] .".");
    $studentOutput = "";
    while($attendanceRow = $result -> fetch_assoc()):
        $formattedName = $attendanceRow['first'] . " " . $attendanceRow['last'];
        $meetingAttendanceID = $attendanceRow['meetingAttendanceID'];
        $studentOutput .= "<div>
				<h3>${formattedName}</h3>
				<p>Attendance: P = Present, AU = Absent Unexcused, AE = Absent Excused (Contacted you with a reason before meeting / Absent from school)</p>
			<div class='form-check form-check-inline'>
				<input class='form-check-input' type='radio' name='attendance-${studentID}-${meetingAttendanceID}' id='attendance-${studentID}-${meetingAttendanceID}-P' value='1' checked>
				<label class='form-check-label' for='attendance-${studentID}-${meetingAttendanceID}-P'>P</label>
			</div>
			<div class='form-check form-check-inline'>
				<input class='form-check-input' type='radio' name='attendance-${studentID}-${meetingAttendanceID}' id='attendance-${studentID}-${meetingAttendanceID}-AU' value='0'>
				<label class='form-check-label' for='attendance-${studentID}-${meetingAttendanceID}-AU'>AU</label>
			</div>
			<div class='form-check form-check-inline'>
				<input class='form-check-input' type='radio' name='attendance-${studentID}-${meetingAttendanceID}' id='attendance-${studentID}-${meetingAttendanceID}-AE' value='-1'>
				<label class='form-check-label' for='attendance-${studentID}-${meetingAttendanceID}-AE'>AE</label>
			</div>
			</div><hr>";
    endwhile;
    return $studentOutput;
}

?>
<form id="addTo" method="post">
	<fieldset>
		<legend>Edit Meeting</legend>
		<?php
        if($meetingTypeID == 1){ ?>
                <input id="eventID" name="eventID" class="form-control" type="hidden" value="<?=$row["eventID"]?>">
        <?php }
        else {
            //$row = NULL;
        }
        ?>
        <p>
            <label for="meetingDate">Meeting Date</label>
            <input id="meetingDate" name="meetingDate" class="form-control" type="date" value="<?=$row["meetingDate"]?>" required>
        </p>
        <p>
            <label for="meetingTimeIn">Time In</label>
            <input id="meetingTimeIn" name="meetingTimeIn" class="form-control" type="time" value="<?=$row["meetingTimeIn"]?>"required>
        </p>
        <p>
            <label for="meetingTimeOut">Time Out</label>
            <input id="meetingTimeOut" name="meetingTimeOut" class="form-control" type="time" value="<?=$row["meetingTimeOut"]?>" required>
        </p>

        <p>
            <label for="meetingDescription">Meeting Description</label>
	        <textarea id="meetingDescription" name="meetingDescription" class="form-control" data-summernote><?=$row["meetingDescription"]?></textarea>
        </p>
	    
        <p>
            <label for="meetingHW">Meeting Homework</label>
            <textarea id="meetingHW" name="meetingHW" class="form-control" data-summernote><?=$row["meetingHW"]?></textarea>
        </p>

        <p>
        <div id="attendanceContainer"></div>
        
        </p>

	<p><button class='btn btn-outline-secondary' onclick='window.history.back()' type='button'><span class='bi bi-arrow-left-circle'></span> Return</button></p>
	</fieldset>
</form>

<script defer>
 
    $(document).ready(function() {
        loadAttendanceData(<?= $meetingID ?>);
		//$('#meetingHW').summernote({focus: true});
		//$('#meetingDescription').summernote({focus: true});
		//loadSummerNoteButtons();
    });

</script>
