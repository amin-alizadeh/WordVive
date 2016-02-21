<?PHP
require_once "lib/random/random.php";

function generateRandomString($length = 32) {
    
  try {
    $string = random_bytes($length);
    $randomString = (bin2hex($string));
  } catch (TypeError $e) {
    // Well, it's an integer, so this IS unexpected.    //die("An unexpected error has occurred"); 
    $randomString = getRandomString($length);
  } catch (Error $e) {
    // This is also unexpected because 32 is a reasonable integer.    //die("An unexpected error has occurred");
    $randomString = getRandomString($length);
  } catch (Exception $e) {
    // If you get this message, the CSPRNG failed hard.     //die("Could not generate a random string. Is our OS secure?");
    $randomString = getRandomString($length);
  }

  return substr ($randomString, 0, $length);
}

function getRandomString($length = 32) {
  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $charactersLength = strlen($characters);
  $randomString = '';
  for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
  }
  return $randomString;
}

function checkLogin($conn, $username, $password, $passwordSalt) {
	$message = array();

	$sql = "SELECT ID, Enabled, Activated FROM UserInfo WHERE username=? AND password=MD5(? +`Identifier`+ ?)"; 
	
	
	if (!($stmt = mysqli_prepare($conn, $sql))) {
		echo "Could not prepare the statement";
	}
	if (!$stmt->bind_param('sss', $username, $password, $passwordSalt)) {
		throw new \Exception("Database error: $stmt->errno - $stmt->error");
	}

	mysqli_stmt_execute($stmt);
	mysqli_stmt_bind_result($stmt, $ID, $Enabled, $Activated);
	$stmt->store_result();
	$num_result = $stmt->num_rows;
	
	if ($num_result == 0) {
		$message["status"] = "Invalid";
	} else if ($num_result == 1) {
		mysqli_stmt_fetch($stmt);
		if ($Enabled == 1) {
			if ($Activated == 1) {
				
				$foundToken = false;
				$randomString = "";
				$sql = "SELECT ItemID FROM SessionToken WHERE Token=?";
				if (!($token_stmt = mysqli_prepare($conn, $sql))) {
					echo "Could not prepare the statement";
				}
				if (!$token_stmt->bind_param('s', $randomString)) {
					throw new \Exception("Database error: $token_stmt->errno - $token_stmt->error");
				}
				
				while(!$foundToken) {
					$randomString = generateRandomString();
					mysqli_stmt_execute($token_stmt);
					
					$token_stmt->store_result();
					
					if ($token_stmt->num_rows > 0) {
						$foundToken = false;
					} else { 
						$foundToken = true;
					}
				}
				
				$sql = "INSERT INTO SessionToken (Token, UserID, ValidUntil) VALUES (?, ? , DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 2 HOUR))";
				$token_store_stmt =  $conn->prepare($sql);
				$token_store_stmt->bind_param("si", $randomString, $ID);
				
				if ($token_store_stmt->execute()) {
					$message['token'] = $randomString;
					$message['status'] = 'OK';
				} else {
					$message['token'] = "";
					$message['status'] = "Error";
				}

				mysqli_stmt_close($token_store_stmt);
				mysqli_stmt_close($token_stmt);
			} else {
				$message["status"] = "Inactive";
			}
		} else {
			$message["status"] = "Disabled";
		}
	} else {
		$message["status"] = "Invalid. Multiple results were found.";
	}
	
	/* close statement */
	mysqli_stmt_close($stmt);
	
	return $message;
	
}
?>