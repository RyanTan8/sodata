<?php
class User {
    function __construct($conn){
        if(!isset($this->db)){
            // Connect to the database
						$this->db = $conn;
						$this->schoolID = 0;
						$this->type = 0;
            /*$conn = new mysqli($this->dbHost, $this->dbUsername, $this->dbPassword, $this->dbName);
            if($conn->connect_error){
                die("Failed to connect with MySQL: " . $conn->connect_error);
            }else{
                $this->db = $conn;
            }*/
        }
    }
		function checkUserType($userID)
		{
			//Check to see if user is a student or coach
			$query = "SELECT `schoolID`, 'student' as type FROM `student` WHERE `userID`=$userID UNION SELECT `schoolID`, 'teacher' as type FROM `coach` WHERE `userID`=$userID";
			$result = $this->db->query($query) or print("\n<br />Warning: query failed:$query. " . $this->db->error. ". At file:". __FILE__ ." by " . $_SERVER['REMOTE_ADDR'] .".");

			if($result->num_rows > 0){
				$row = $result->fetch_assoc();
				return [$row['schoolID'], $row['type']]; //school id, type
			}
			else {
				//check to see if this is a super user
				$query = "SELECT `privilege` FROM `user` WHERE userID=$userID";
				$result = $this->db->query($query) or print("\n<br />Warning: query failed:$query. " . $this->db->error. ". At file:". __FILE__ ." by " . $_SERVER['REMOTE_ADDR'] .".");
				if($result->num_rows > 0){
					$row = $result->fetch_assoc();
					if($row['privilege']>1)
					{
							return [0, 'super']; //school id, type
					}
				}
				else {
					//This is not a registered user
					return [NULL, 'none']; //school id, type
				}
			}
		}

    function checkUser($data = array()){
        if(!empty($data)){
            // Check whether the user already exists in the database
            $checkQuery = "SELECT * FROM `user` WHERE oauth_provider = '".$data['oauth_provider']."' AND oauth_uid = '".$data['oauth_uid']."'";

						//TODO: figure out how to find school ID.  See line below.  Problem with this is it misses the superuser that should be able to view all schools.
						//$checkQuery = "SELECT * FROM `user` LEFT JOIN `student` ON `student`.`schoolID`=`user`.`userID` LEFT JOIN `coach` ON `coach`.`schoolID`=`user`.`userID` WHERE `oauth_provider` = 'google' AND `oauth_uid` = '109397293342063106702'";
						//echo $checkQuery;

						$checkResult = $this->db->query($checkQuery);
            // Add modified time to the data array
            if(!array_key_exists('modified',$data)){
                $data['modified'] = date("Y-m-d H:i:s");
            }

            if($checkResult->num_rows > 0){
								//Check to see if a user is a student or teacher
								$checkRow = $checkResult->fetch_assoc();
								$userType = $this->checkUserType($checkRow['userID']);
                // Prepare column and value format
                $colvalSet = '';
                $i = 0;
                foreach($data as $key=>$val){
                    $pre = ($i > 0)?', ':'';
                    $colvalSet .= $pre.$key."='".$this->db->real_escape_string($val)."'";
                    $i++;
                }
                $whereSql = " WHERE oauth_provider = '".$data['oauth_provider']."' AND oauth_uid = '".$data['oauth_uid']."'";
                // Update user data in the database
                $query = "UPDATE `users` SET ".$colvalSet.$whereSql;
                $update = $this->db->query($query);
            }else{
                // Add created time to the data array
                if(!array_key_exists('created',$data)){
                    $data['created'] = date("Y-m-d H:i:s");
                }

                // Prepare column and value format
                $columns = $values = '';
                $i = 0;
                foreach($data as $key=>$val){
                    $pre = ($i > 0)?', ':'';
                    $columns .= $pre.$key;
                    $values  .= $pre."'".$this->db->real_escape_string($val)."'";
                    $i++;
                }

                // Insert user data in the database
                $query = "INSERT INTO `user` (".$columns.") VALUES (".$values.")";
                $insert = $this->db->query($query);
								$userID = $this->db->insert_id;
								//If new user, check to see if the email of the user is already added.  If so, automatically give them access to their account.
								$query = "UPDATE `student` SET `userID` = $userID WHERE `email` LIKE '" . $data['email'] . "'";
								if ($this->db->query($query)) {
										$queryPrivilege = "UPDATE `user` SET `privilege` = 1 WHERE `userID`=$userID";
										$this->db->query($queryPrivilege);
								} else {
									//if not a student email check coaches
									$query = "UPDATE `coach` SET `userID` = $userID WHERE `email` LIKE '" . $data['email'] ."'";
									if ($this->db->query($query) === TRUE) {
										$queryPrivilege = "UPDATE `user` SET `privilege` = 1 WHERE `userID`=$userID";
										$this->db->query($queryPrivilege);
									} else {
										//If the email does not match or the email is not google, give the user a chance to provide token to link account.
										//TODO add a way for a user to do this.
									}
								}
							//If the email does not match or the email is not google, give the user a chance to provide token to link account.
						}

            // Get user data from the database
            $result = $this->db->query($checkQuery);
            $userData = $result->fetch_assoc();

						if(!empty($userData))
						{
							//added to be able to use in session
							$userData['schoolID'] = $userType[0];
							$userData['type'] = $userType[1];
						}
        }
        // Return user data
        return !empty($userData)?$userData:false;
    }
}
