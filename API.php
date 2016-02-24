<?PHP
date_default_timezone_set('Europe/Helsinki');
require_once ('connect.php'); //credentials.php is required in connect.php
require_once ('login.php'); //lib/random/random.php is required in login.php
require_once 'PHPMailer/PHPMailerAutoload.php';
require_once ('helper/verificationhelper.php');

function getUserFromToken($conn, $token) {
	$uID = null;
	$sql = "SELECT UserID, ValidUntil, IsValid FROM SessionToken WHERE Token=?";
	if (!($stmt = mysqli_prepare($conn, $sql))) {
		echo "Could not prepare the statement";
	}
	if (!$stmt->bind_param('s', $token)) {
		throw new \Exception("Database error: $stmt->errno - $stmt->error");
	}

	mysqli_stmt_execute($stmt);
	mysqli_stmt_bind_result($stmt, $userID, $validUntil, $isValid);
	$stmt->store_result();
	$num_result = $stmt->num_rows;
	
	if ($num_result > 0) {
		mysqli_stmt_fetch($stmt);
		if ($isValid == 1) {
			$uID = $userID;
		}
	}

	return $userID;
}

function getUserInfo($conn, $userID) {
	$sql = "SELECT CASE WHEN `NickName` IS NULL OR LENGTH(`NickName`) = 0 THEN CONCAT(`FirstName`,' ',`LastName`) ELSE `NickName` END AS NickName FROM UserInfo WHERE ID=?";
	if (!($stmt = mysqli_prepare($conn, $sql))) {
		echo "Could not prepare the statement";
	}
	if (!$stmt->bind_param('i', $userID)) {
		throw new \Exception("Database error: $stmt->errno - $stmt->error");
	}

	mysqli_stmt_execute($stmt);
	mysqli_stmt_bind_result($stmt, $nickName);
	$stmt->store_result();
	$num_result = $stmt->num_rows;
	
	//$result = $conn->query($sql);

	if ($num_result > 0) {
		mysqli_stmt_fetch($stmt);
		return $nickName;
	} else {
		return "";
	}
}

function insertWord($conn, $userID, $word, $translation, $description, $list) {
	$sql = "INSERT INTO `Words` (`UserID`, `Word`, `Translation`, `Description`) VALUES (?,?,?,?)";
  
	$word_stmt =  $conn->prepare($sql);
	$word_stmt->bind_param("isss", $userID, $word, $translation, $description);
	
	if ($word_stmt->execute()) {
    $sql = "SELECT LAST_INSERT_ID() AS WordID";
    $list_stmt =  $conn->prepare($sql);
    $user_list_exec = false;
    $user_word_step_exec = false;
    if ($list_stmt->execute()) {
      mysqli_stmt_bind_result($list_stmt, $wordID);
      $list_stmt->store_result();
      $num_result = $list_stmt->num_rows;
      mysqli_stmt_fetch($list_stmt);
      if($num_result == 1) {
        $sql = "INSERT INTO `WordList`(`ListID`, `WordID`) VALUES (?,?)";
        $stmt_user_list = $conn->prepare($sql);
        $stmt_user_list->bind_param("ii", $list, $wordID);
        $user_list_exec = $stmt_user_list->execute();
      }
      $sql = "INSERT INTO `UserWordStep`(`UserID`, `WordID`) VALUES (?,?)";
      $stmt_user_word_step = $conn->prepare($sql);
      $stmt_user_word_step->bind_param("ii", $userID, $wordID);
      $user_word_step_exec = $stmt_user_word_step->execute();
    }
    if ($user_list_exec && $user_word_step_exec) {
      return "OK";
    } else {
      return "Error";
    }
	} else {
		return "Error";
	}

}

function updateWord($conn, $userID, $id, $word, $translation, $description) {
	$sql = "UPDATE `Words` SET `Word`=?,`Translation`=?,`Description`=? WHERE `ID`=? AND `UserID`=?";
  
	$word_stmt =  $conn->prepare($sql);
	$word_stmt->bind_param("sssii", $word, $translation, $description, $id, $userID);
	
	if ($word_stmt->execute()) {
		return "OK";
	} else {
		return "Error";
	}
}

function deleteWord($conn, $userID, $id) {
	$sql = "DELETE FROM `Words` WHERE `ID`=? AND `UserID`=?";
  
	$word_stmt =  $conn->prepare($sql);
	$word_stmt->bind_param("ii", $id, $userID);
	
	if ($word_stmt->execute()) {
		return "OK";
	} else {
		return "Error";
	}
}

function getWordsList($conn, $userID, $list, $first, $last) {
  $sql = "SELECT w.ID, w.Word, w.Translation, w.Description, CASE WHEN uws.Step IS NULL THEN 0 ELSE uws.Step END AS Step ".
  "FROM UserInfo AS usr ".
  "INNER JOIN UserList AS ul  ON usr.ID = ul.UserID ".
  "INNER JOIN List AS l ON l.ListID=ul.ListID ".
  "INNER JOIN WordList AS wl ON wl.ListID = l.ListID ".
  "INNER JOIN Words AS w ON w.ID = wl.WordID ".
  "LEFT OUTER JOIN UserWordStep AS uws ON (uws.WordID=w.ID AND uws.UserID=usr.ID) ".
  "WHERE usr.ID=" . $userID . " AND ul.ListID = " . $list . " ORDER BY InsertTime DESC LIMIT " . 
  $first . ", " . ($last - $first);
  
	/*$sql = "SELECT ID, Word, Translation, Description, Step FROM Words " .
			"WHERE UserID = " . $userID . " ORDER BY InsertTime DESC LIMIT " . $first . ", " . ($last - $first);
  */
	$result = $conn->query($sql);
	$message = array();
	if ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
			$message[] = $row;
		}
	}
	return $message;
}

function getWordsCount($conn, $list, $userID) {
  //$sql = "SELECT COUNT(Word) AS wordcount FROM Words WHERE UserID = " . $userID;
  $sql="SELECT COUNT(w.Word) AS wordcount ".
    "FROM Words AS w ".
    "INNER JOIN WordList AS wl ON wl.WordID = w.ID AND wl.ListID = ". $list ." ".
    "INNER JOIN List AS l ON l.ListID = wl.ListID  ".
    "INNER JOIN UserList AS ul ON ul.ListID = l.ListID AND ul.UserID=" . $userID;
  
	$result = $conn->query($sql);
	$message = "0";
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		$message = $row["wordcount"];
	}
	return $message;
}

function logUserOut($conn, $token) {
	$sql = "UPDATE SessionToken SET IsValid=0 WHERE Token='". $token ."'";
	if ($conn->query($sql) === TRUE) {
		return true;
	} else { 
		return false;
	}
}

function getPracticeList($conn, $userID, $n) {
	$sql = "SELECT rndW.ID, w.Word, w.Translation, w.Description, Step FROM Words w " .
	
    "INNER JOIN (" .
    "SELECT ID FROM Words WHERE UserID=". $userID ." ORDER BY RAND() LIMIT 0, " . $n .
    ") AS rndW ON rndW.ID = w.ID";
	
	$result = $conn->query($sql);
	$message = array();
	if ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
			$message[] = $row;
		}
	}
	return $message;
	
}

function submitPractice($conn, $userID, $cr, $incr) {
	$sqlcr = "UPDATE Words SET Step = Step + 1 WHERE UserID=". $userID ." AND ID IN (". $cr .")";
	$sqlincr = "UPDATE Words SET Step = 1 WHERE UserID=". $userID ." AND ID IN (". $incr .")";
	$crres = false;
	$incrres = false;
	
	if (strlen($cr) > 0) {
		if ($conn->query($sqlcr) === TRUE) {
			$crres = true;
		}
	} else {
		$crres = true;
	}
	
	if (strlen($incr) > 0) {
		if ($conn->query($sqlincr) === TRUE) {
			$incrres = true;
		}
	} else {
		$incrres = true;
	}
	
	return ($crres and $incrres);
	
}

function registerNewUser($conn, $username, $password, $firstname, $lastname, $email, $terms, $emailHost, $emailPort, $emailAddress, $emailPassword, $passwordSalt) {
	$message = array();
	$sql = 'SELECT (SELECT COUNT(username) FROM UserInfo WHERE username=?) AS usernameExist'.
			', (SELECT COUNT(email) FROM UserInfo WHERE email=?) AS emailExist';
	
	if (!($stmt = mysqli_prepare($conn, $sql))) {
		echo "Could not prepare the statement";
	}
	if (!$stmt->bind_param('ss', $username, $email)) {
		throw new \Exception("Database error: $stmt->errno - $stmt->error");
	}

	mysqli_stmt_execute($stmt);
	mysqli_stmt_bind_result($stmt, $usernameExist, $emailExist);
	$stmt->store_result();
	$num_result = $stmt->num_rows;
	mysqli_stmt_fetch($stmt);
	
	if($usernameExist > 0 && emailExist > 0) {
		$message["status"] = "Fail";
		$message["message"] = "Username and Email already exist";
	} else if($usernameExist > 0 && emailExist == 0) {
		$message["status"] = "Fail";
		$message["message"] = "Username already exist";
	} else if($usernameExist == 0 && emailExist > 0) {
		$message["status"] = "Fail";
		$message["message"] = "Email already exist";
	} else {
		
		
		$sql = "INSERT INTO UserInfo(username, password, email, FirstName, LastName, passwordsalt) VALUES (?,?,?,?,?,?)";
		$user_stmt =  $conn->prepare($sql);
		$user_stmt->bind_param("ssssss", $username, $password, $email, $firstname, $lastname, $passwordSalt);
		if ($user_stmt->execute()) {
			
			if (prepareVerification($conn, $username, $email, $firstname, $lastname, $emailHost, $emailPort, $emailAddress, $emailPassword)) {
				$message['status'] = 'OK';
			} else {
				$message["status"] = "Fail";
				$message["message"] = "Server error. Try again later.";
			}
		} else {
			$message["status"] = "Fail";
			$message["message"] = "Server error. Try again later.";
		}
		mysqli_stmt_close($user_stmt);
		mysqli_stmt_close($stmt);
	}

	return $message;
}

function prepareVerification($conn, $username, $email, $firstname, $lastname, $emailHost, $emailPort, $emailAddress, $emailPassword) {
	$sql_ver = "INSERT INTO `Verifications`(`Code`, `UserID`, `VerificationCode`) VALUES(?,?,?)";
	$sql_usr = "SELECT `ID`, `Identifier` FROM `UserInfo` WHERE username=?";
	
	if (!($stmt_usr = mysqli_prepare($conn, $sql_usr))) {
		echo "Could not prepare the statement";
	}
	if (!$stmt_usr->bind_param('s', $username)) {
		throw new \Exception("Database error: $stmt_usr->errno - $stmt_usr->error");
	}

	mysqli_stmt_execute($stmt_usr);
	mysqli_stmt_bind_result($stmt_usr, $userID, $identifier);
	$stmt_usr->store_result();
	$num_result = $stmt_usr->num_rows;
	mysqli_stmt_fetch($stmt_usr);
	
	$code = generateRandomString(32);
	$verCode = generateRandomString(8);
	
	$stmt_ver = $conn->prepare($sql_ver);
	$stmt_ver->bind_param("sis", $code, $userID, $verCode);
	if($stmt_ver->execute()) {
		return sendVerificationEmail($conn, $userID, $code, $verCode, $identifier, $email, $firstname, $lastname, $emailHost, $emailPort, $emailAddress, $emailPassword);
	} else {
		return false;
	}
}

function sendVerificationEmail($conn, $userID, $code, $verCode, $identifier, $email, $firstname, $lastname, $emailHost, $emailPort, $emailAddress, $emailPassword) {
	$mail = new PHPMailer;
	$mail->isSMTP(); // Set mailer to use SMTP
	$mail->Host = $emailHost;  // Specify main and backup SMTP servers
	$mail->Username = $emailAddress;
	$mail->Password = $emailPassword;
	$mail->Port = $emailPort;
	$mail->setFrom($emailAddress, 'WordVive Registration');
	$mail->addAddress($email, $firstname);
	$mail->addReplyTo($emailAddress, 'Information');
	//$mail->SMTPDebug = 1;
	//$mail->Debugoutput = 'html';
	
	$mail->isHTML(true);
	$preparedMail = prepareEmail($firstname, $lastname, $username, $code, $verCode);//From credentials.php
	
	$mail->Subject = $preparedMail["subject"];
	$mail->Body    = $preparedMail["body"];
	$mail->AltBody = $preparedMail["altbody"];

	if(!$mail->send()) {
		return false;
	} else {
		return true;
	}

}

function getListList($conn, $userID, $first=0, $last=10) {
  $cnt = $last - $first;
  /*
  SELECT `usr`.`ID`,`l`.`ListID`, `l`.`ListName` 
  FROM `UserInfo` AS `usr` 
  INNER JOIN `UserList` AS `ul` ON `ul`.`UserID` = `usr`.`ID`
  INNER JOIN `List` AS `l` ON `l`.`ListID`=`ul`.`ListID` 
  WHERE `usr`.`ID`=1
  ORDER BY `ListCreatedOn` DESC LIMIT 0,10
  */
  $sql = "SELECT `l`.`ListID`, `l`.`ListName` FROM `UserInfo` AS `usr` INNER JOIN `UserList` AS `ul` ON `ul`.`UserID` = `usr`.`ID` ".
      "INNER JOIN `List` AS `l` ON `l`.`ListID`=`ul`.`ListID` ".
      "WHERE `usr`.`ID`=". intval($userID) . " ORDER BY `ListCreatedOn` DESC LIMIT " . intval($first) ."," . intval($cnt);
	$result = $conn->query($sql);
	$list = array();
	if ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
			$list[] = $row;
		}
	}
  
  return $list;
}

function insertList($conn, $userID, $list) {
  $sql = "INSERT INTO `List`(`ListName`) VALUES (?)";
  $list_in_stmt =  $conn->prepare($sql);
  $list_in_stmt->bind_param("s", $list);
  if ($list_in_stmt->execute()) {
    $sql = "SELECT LAST_INSERT_ID() AS ListID";
    $list_stmt =  $conn->prepare($sql);
    if ($list_stmt->execute()) {
      mysqli_stmt_bind_result($list_stmt, $listID);
      $list_stmt->store_result();
      $listID = intval($listID);
      $num_result = $list_stmt->num_rows;
      mysqli_stmt_fetch($list_stmt);
      if($num_result == 1) {
        $sql = "INSERT INTO `UserList`(`ListID`, `UserID`) VALUES (?, ?)";
        $stmt_user_list = $conn->prepare($sql);
        $stmt_user_list->bind_param("ii", $listID, $userID);
        if ($stmt_user_list->execute()) {
          $message = "OK";
        }
      }      
    }
  }
  return $message;
}

$message = array();
if (isset($_GET["action"]) && $_GET["action"] == "login" && isset($_POST["username"]) && isset($_POST["password"])) {
	$message = checkLogin ($conn, $_POST["username"], $_POST["password"], $passwordSalt);
	
} else if (isset($_GET["action"]) && $_GET["action"] == "register" && isset($_POST["username"]) && isset($_POST["password"]) && isset($_POST["firstname"]) && isset($_POST["lastname"]) && isset($_POST["email"]) && isset($_POST["terms"])) {
	
	$message = registerNewUser($conn, $_POST["username"], $_POST["password"], $_POST["firstname"], $_POST["lastname"], $_POST["email"], $_POST["terms"], $emailHost, $emailPort, $emailAddress, $emailPassword, $passwordSalt);
} else if (isset($_GET["action"]) && isset($_POST["code"]) && $_GET["action"] == "verification") {
	$message["message"] = verifyCode($conn, $_POST["code"], $verificationValid);
	if (stripos($message["message"], "success") !== false) {
		$message["status"] = "OK";
	} else {
		$message["status"] = "Fail";
	}
} else if (isset($_GET["token"]) && isset($_GET["action"])) {
	$userID = getUserFromToken($conn, $_GET["token"]);
	if ( $userID != null) {
		$message["status"] = "OK";
		switch($_GET["action"]) {
			case "insert":
				$word = isset($_POST["word"]) ? $_POST["word"] : "";
				$translation = isset($_POST["translation"]) ? $_POST["translation"] : "";
				$description = isset($_POST["description"]) ? $_POST["description"] : "";
        $list = $_POST["list"];
				$message["status"] = insertWord($conn, $userID, $word, $translation, $description, $list);
				break;
			case "get":
				$message["action"] = "check";
				break;
			case "userinfo":
				$message["user"] = getUserInfo($conn, $userID);
			break;
			case "wordlist":
				$message["words"] = getWordsList($conn, $userID, $_GET["list"], $_GET["first"], $_GET["last"]);
				break;
			case "wordcount":
				$message["wordcount"] = getWordsCount($conn, $_GET["list"], $userID);
				break;
			case "logout":
				$message["logout"] = logUserOut($conn, $_GET["token"]);
				// remove all session variables
				session_unset(); 
				// destroy the session 
				session_destroy(); 
			case "practicelist":
				if (isset($_GET["count"])) {
					$message["practicelist"] = getPracticeList($conn, $userID, $_GET["count"]);
				} else {
					$message["practicelist"] = getPracticeList($conn, $userID, 10);
				}
				break;
			case "submitpractice":
				if(isset($_POST["correct"]) && isset($_POST["incorrect"])) {
					$cr = $_POST["correct"];
					$incr = $_POST["incorrect"];
					$message["submit"] = submitPractice($conn, $userID, $cr, $incr);
				} else {
					$message["submit"] = false;
				}
				break;
			case "updateword":
				$wid = isset($_POST["id"]) ? $_POST["id"] : "";
				$word = isset($_POST["word"]) ? $_POST["word"] : "";
				$translation = isset($_POST["translation"]) ? $_POST["translation"] : "";
				$description = isset($_POST["description"]) ? $_POST["description"] : "";
				$message["status"] = updateWord($conn, $userID, $wid, $word, $translation, $description);
				break;
      case "deleteword":
				$wid = isset($_POST["id"]) ? $_POST["id"] : "";
				$message["status"] = deleteWord($conn, $userID, $wid);
				break;
      case "insertlist":
				$list = $_POST["list"];
				$message["status"] = insertList($conn, $userID, $list);
				break;
      case "listlist":
				$message["lists"] = getListList($conn, $userID, $_GET["first"], $_GET["last"]);
				break;
      
		}
		
	} else {
		$message["status"] = "Invalid token";
	}
}
echo json_encode($message);
mysqli_close($conn);
?>