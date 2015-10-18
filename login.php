<?PHP

function generateRandomString($length = 32) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function checkLogin($conn, $username, $password) {
	$message = array();

	$sql = "SELECT ID, Enabled FROM UserInfo WHERE username='" . $username . "'	AND password='" . $password . "'"; 
	$result = $conn->query($sql);

	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		if ($row["Enabled"] == "1") {
			$foundToken = false;
			$randomString = "";
			while(!$foundToken) {
				$randomString = generateRandomString();
				$sql = "SELECT ItemID FROM SessionToken WHERE Token='". $randomString . "'";
				$result = $conn->query($sql);
				if ($result->num_rows > 0) {
					$foundToken = false;
				} else { 
					$foundToken = true;
				}
			}
			
			$sql = "INSERT INTO `SessionToken`(`Token`, `UserID`, `ValidUntil`) VALUES ('" . $randomString . "', " . $row["ID"] . ", DATE_ADD(now(), INTERVAL 2 HOUR))";
			if ($conn->query($sql) === TRUE) {
				$message['token'] = $randomString;
				$message['status'] = 'OK';
			} else {
				$message["token"] = "";
				$message["status"] = "Error";
			}
			
		} else {
			$message["status"] = "Disabled";
		}
	} else {
		$message["status"] = "Invalid";
	}

	return json_encode($message);
}
?>