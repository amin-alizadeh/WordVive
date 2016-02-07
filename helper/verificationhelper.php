<?PHP
function verifyCode($conn, $codeHash, $verificationValid) {
	$message = "";
	if(strlen($codeHash) == 64 || strlen($codeHash) == 8) {
		if (strlen($codeHash) == 64) {
			$code = substr($codeHash, 0, 32);
			$hash = substr($codeHash, -32);
			
			$sql = "SELECT `ItemID`, `UserID` FROM `Verifications` WHERE `Code` = ? AND `UsedOn`IS NULL AND ".
			"TIMESTAMPDIFF(MINUTE, `CreatedOn`, CURRENT_TIMESTAMP) < ? AND CONVERT(?, CHAR(32)) = CONVERT(MD5(`VerificationCode`), CHAR(32))";
			
			if (!($stmt_check = mysqli_prepare($conn, $sql))) {
				$message = "Could not prepare the statement";
			}
			if (!$stmt_check->bind_param('sis', $code, $verificationValid, $hash)) {
				throw new \Exception("Database error: $stmt_check->errno - $stmt_check->error");
			}
		} else {
			$sql = "SELECT `ItemID`, `UserID` FROM `Verifications` WHERE `VerificationCode`=?";
			
			if (!($stmt_check = mysqli_prepare($conn, $sql))) {
				$message = "Could not prepare the statement";
			}
			if (!$stmt_check->bind_param('s', $codeHash)) {
				throw new \Exception("Database error: $stmt_check->errno - $stmt_check->error");
			}
		}
		
		mysqli_stmt_execute($stmt_check);
		mysqli_stmt_bind_result($stmt_check, $itemID, $userID);
		$stmt_check->store_result();
		$num_result = $stmt_check->num_rows;
		mysqli_stmt_fetch($stmt_check);
		if($num_result == 1) {
			$sql_ver = "UPDATE `Verifications` SET `UsedOn`=CURRENT_TIMESTAMP WHERE `itemID`=?";
			$sql_user = "UPDATE `UserInfo` SET `Activated`=1 WHERE `ID` = ?";
			$stmt_ver_update = $conn->prepare($sql_ver);
			$stmt_ver_update->bind_param("i", $itemID);
				
			if ($stmt_ver_update->execute()) {
				$stmt_user_active = $conn->prepare($sql_user);
				$stmt_user_active->bind_param("i", $userID);
				if ($stmt_user_active->execute()) {
					$message = "Successful verification.";
				} else {
					$message = "could not activate";
				}
				mysqli_stmt_close($stmt_user_active);
			} else {
				$message = "DB Error";
			}

			mysqli_stmt_close($stmt_ver_update);
		} else {
			$message = "something went wrong";
		}
		mysqli_stmt_close($stmt_check);
	} else {
		$message = "wrong code";
	}
	
	return $message;
} 