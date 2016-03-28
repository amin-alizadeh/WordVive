<?PHP
date_default_timezone_set('Europe/Helsinki');
require_once ('connect.php'); //credentials.php is required in connect.php
require_once ('login.php'); //lib/random/random.php is required in login.php
require_once 'PHPMailer/PHPMailerAutoload.php';
require_once ('helper/verificationhelper.php');
require_once ('helper/DBOperations.php');

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
  
  $rows = fetchRows($conn, $sql, "i", $userID);
  
  if (count(rows) == 1){
    return $rows[0]['NickName'];
  } else {
    return "";
  }
}

function getUserDetail($conn, $userID) {
  $sql = "SELECT `username`, `email`, `NickName` AS nickname, `FirstName` AS firstname, `LastName` AS lastname FROM `UserInfo` WHERE `ID` = ?";
  
  $rows = fetchRows($conn, $sql, "i", $userID);
  $msg = array();
  if (count(rows) == 1){
    $msg["success"] = true;
    $msg["user"] = $rows[0];
  } else {
    $msg["success"] = false;
    $msg["user"] = array();
  }
  return $msg;
}

function updateUserDetail($conn, $userID, $firstname, $lastname, $nickname) {
  $sql = "UPDATE `UserInfo` SET `NickName`=?, `FirstName`=?, `LastName`=? WHERE `ID`=?";
  
  $user_stmt = $conn->prepare($sql);
	$user_stmt->bind_param("sssi", $nickname, $firstname, $lastname, $userID);
	
	if ($user_stmt->execute()) {
		return "OK";
	} else {
		return "Error";
	}
}

function updatePassword($conn, $userID, $password, $newpassword, $passwordSalt) {
  $msg = array();
  $msg["correctPassword"] = false;
  $msg["success"] = false;
  
  $sql = "SELECT COUNT(*) AS matches FROM `UserInfo` WHERE `ID`=? AND `password`=MD5(?+`Identifier`+?)";
  
  $passCorrect = fetchRows($conn, $sql, 'iss', $userID, $password, $passwordSalt);
  
  if ($passCorrect[0]["matches"] == 0) {
    $msg["message"] = "Incorrect password";
    return $msg;
  } else {
    $msg["correctPassword"] = true;
  }
  //UPDATE `UserInfo` SET `password`=[value-5] WHERE 1
  $sql = "UPDATE `UserInfo` SET `password`=MD5(?+`Identifier`+?) WHERE `ID`=?";
  $user_stmt = $conn->prepare($sql);
	$user_stmt->bind_param("ssi", $newpassword, $passwordSalt, $userID);
	
	if ($user_stmt->execute()) {
		$msg["success"] = true;
    $msg["message"] = "Password successfully updated";
	} else {
		$msg["success"] = false;
    $msg["message"] = "Internal error. Password was not updated";
	}
  mysqli_stmt_close($user_stmt);
  return $msg;
}

function checkWordBase($conn, $userID, $wordBase) {
  $msg = array();
  $sql = "SELECT w.ID, w.Word, w.Translation, w.Description, 
    CASE WHEN uws.Step IS NULL THEN 1 ELSE uws.Step END AS Step
    FROM `Words` w
    INNER JOIN UserList ul ON ul.ListID=w.ListID AND ul.UserID=?
    LEFT OUTER JOIN UserWordStep AS uws ON (uws.WordID=w.ID AND uws.UserID=ul.UserID)
    WHERE `WordBase` LIKE ? LIMIT 0,1"; 
  
  $rows = fetchRows($conn, $sql, "is", $userID, $wordBase);
  
  if (count($rows) > 0) {
    return $rows[0];
  }
  return null;
}

function insertWord($conn, $userID, $word, $translation, $description, $wordBase, $list, $forceInsert) {
  $msg = array();
  $msg["base"] = $wordBase;
  $msg["exist1"] = $forceInsert;
  if (! $forceInsert) {
    $dupWord = checkWordBase($conn, $userID, $wordBase);
    if (! is_null($dupWord)) {
      $msg["word"] = $dupWord;
      $msg["status"] = "Duplicate";
    } else {
      $forceInsert = true;
    }
  }
  $msg["exist2"] = $forceInsert;
  if ($forceInsert == true) {
    $sql = "INSERT INTO `Words` (`UserID`, `ListID`, `Word`, `Translation`, `Description`, `WordBase`) VALUES (?,?,?,?,?,?)";
    if (insertRow($conn, $sql, "iissss", $userID, $list, $word, $translation, $description, $wordBase)) {
      $sql = "SELECT LAST_INSERT_ID() AS WordID";
      $wordIDRow = fetchRows($conn, $sql);
      $wordID = $wordIDRow[0]["WordID"];
      if ($wordID > 0) {
        $sql = "INSERT INTO `UserWordStep`(`UserID`, `WordID`) VALUES (?,?)";
        $msg["WordID"] = $wordID;
        if (insertRow($conn, $sql, "ii", $userID, $wordID)) {
          $msg["status"] = "OK";
        } else {
          $msg["status"] = "Error";
        }
      } else {
        $msg["status"] = "Error";
      }
    } else {
      $msg["status"] = "Error";
    }
  }  
	
  return $msg;
}

function updateWord($conn, $userID, $id, $word, $translation, $description, $wordBase) {
	$sql = "UPDATE `Words` SET `Word`=?,`Translation`=?,`Description`=?, `WordBase`=? WHERE `ID`=? AND `UserID`=?";
  
	$word_stmt =  $conn->prepare($sql);
	$word_stmt->bind_param("ssssii", $word, $translation, $description, $wordBase, $id, $userID);
	
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

function getWordsList($conn, $userID, $lists, $first, $last, $filter='%') {
  $listMarks = array();
  for ($i = 0; $i < count($lists); $i++) {
    array_push($listMarks, "?");
  }

  $sql = "SELECT w.ID, w.Word, w.Translation, w.Description, ".
    "CASE WHEN uws.Step IS NULL THEN 1 ELSE uws.Step END AS Step ".
    "FROM UserInfo AS usr ".
    "INNER JOIN UserList AS ul  ON usr.ID = ul.UserID AND usr.ID=? AND ul.ListID IN (%lists%) ".
    "INNER JOIN List AS l ON l.ListID=ul.ListID ".
    "INNER JOIN Words AS w ON w.ListID = l.ListID ".
    "LEFT OUTER JOIN UserWordStep AS uws ON (uws.WordID=w.ID AND uws.UserID=usr.ID) ".
    "WHERE (w.WordBase LIKE ?) ".
    "ORDER BY InsertTime DESC LIMIT ?,?"; 
  $sql = str_ireplace("%lists%", implode(",", $listMarks), $sql);
  $binds = 'i' . str_repeat("i", count($lists)) . 'sii';
  $cnt = ($last - $first);
  $args = array($conn, $sql, $binds, $userID);
  if (is_array($lists)) {
    foreach ($lists as $l) {
      array_push($args, $l);
    }
  } else {
    array_push($args, $lists);
  }
  array_push($args, $filter, $first, $cnt);

  return call_user_func_array('fetchRows', $args);
}

function getWordsCount($conn, $list, $userID, $filter = '%') {
  $sql="SELECT COUNT(w.Word) AS wordcount ".
    "FROM Words AS w ".
    "INNER JOIN List AS l ON w.ListID = ? AND w.Word LIKE ? AND l.ListID = w.ListID ".
    "INNER JOIN UserList AS ul ON ul.ListID = l.ListID AND ul.UserID=?";
  
	$row = fetchRows($conn, $sql, 'isi', $list, $filter, $userID);
  
  $message = $row[0]["wordcount"];
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

function getPracticeList($conn, $userID, $lists, $n) {
  if (! is_array($lists)) {
    $lists = $lists;
  }

  $listMarks = array();
  for ($i = 0; $i < count($lists); $i++) {
    array_push($listMarks, "?");
  }
  
  $sql = "SELECT w.ID, w.Word, w.Translation, w.Description, ul.ListID
    ,CASE WHEN uws.Step IS NULL THEN 1 ELSE uws.Step END AS Step
    FROM UserList ul INNER JOIN Words w ON 
    (ul.UserID=? AND w.ListID=ul.ListID %ListCriteria%)
    LEFT OUTER JOIN UserWordStep AS uws ON 
    (uws.Step<uws.GoalStep AND uws.WordID=w.ID AND uws.UserID=ul.UserID) 
    ORDER BY RAND() LIMIT 0,?";
    
  $listCriteria = "";
  $binds = 'ii';
  if (count($lists) > 1 || (count($lists) == 1) && $lists[0] != -1) {
    $listCriteria = str_ireplace("%lists%", implode(",", $listMarks), " AND ul.ListID IN (%lists%) ");
    $binds = 'i' . str_repeat("i", count($lists)) . 'i';
  } else {
    $lists = array();
  }
  $sql = str_ireplace("%ListCriteria%", $listCriteria, $sql);
        
  $args = array($conn, $sql, $binds, $userID);
  
  foreach ($lists as $l) {
    array_push($args, $l);
  }

  array_push($args, $n);
  
  $rows = call_user_func_array('fetchRows', $args);

	return $rows;
}

function submitPractice($conn, $userID, $cr, $incr) {
  $sql = "INSERT INTO UserWordStep (WordID, UserID) ".
  "SELECT uws.WordID, ? ".
  "FROM UserWordStep uws ".
  "LEFT OUTER JOIN UserWordStep uwsOrg ON uws.WordID=uwsOrg.WordID AND uwsOrg.UserID=? ".
  "WHERE (uws.WordID IN (". $cr .") OR uws.WordID IN (". $incr .")) AND uwsOrg.UserID IS NULL";
  $missing_steps_stmt =  $conn->prepare($sql);
  $missing_steps_stmt->bind_param("ii", $userID, $userID);
  if ($missing_steps_stmt->execute()) {
    $sqlcr = "UPDATE UserWordStep SET Step=Step+1, LastCheckTime=CURRENT_TIMESTAMP WHERE UserID=? AND WordID IN (". $cr .")";
    $sqlincr = "UPDATE UserWordStep SET Step=1, LastCheckTime=CURRENT_TIMESTAMP WHERE UserID=? AND WordID IN (". $incr .")";
    $crres = false;
    $incrres = false;
    
    if (strlen($cr) > 0) {
      $cr_stmt = $conn->prepare($sqlcr);
      $cr_stmt->bind_param("i", $userID);
      if ($cr_stmt->execute()) {
        $crres = true;
      }
    } else {
      $crres = true;
    }
    
    if (strlen($incr) > 0) {
      $incr_stmt = $conn->prepare($sqlincr);
      $incr_stmt->bind_param("i", $userID);
      if ($incr_stmt->execute()) {
        $incrres = true;
      }
    } else {
      $incrres = true;
    }
    return ($crres and $incrres);
  }
	return false;
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
  $sql = "SELECT `l`.`ListID` AS value, `l`.`ListName` AS name FROM `UserInfo` AS `usr` INNER JOIN `UserList` AS `ul` ON `ul`.`UserID` = `usr`.`ID` ".
      "INNER JOIN `List` AS `l` ON `l`.`ListID`=`ul`.`ListID` ".
      "WHERE `usr`.`ID`=? ORDER BY `ListCreatedOn` DESC LIMIT ?,?";
	
  return fetchRows($conn, $sql, "iii", $userID, $first, $cnt);
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

function shareListUser($conn, $userID, $user, $listID){
  $message = "";
  $sql = "SELECT `ID` FROM `UserInfo` WHERE `email`=? OR `username`=?";
  if (!($stmt = mysqli_prepare($conn, $sql))) {
		echo "Could not prepare the statement";
	}
	if (!$stmt->bind_param('ss', $user, $user)) {
		throw new \Exception("Database error: $stmt->errno - $stmt->error");
	}

	mysqli_stmt_execute($stmt);
	mysqli_stmt_bind_result($stmt, $uid);
	$stmt->store_result();
	$num_result = $stmt->num_rows;
	mysqli_stmt_fetch($stmt);
  
  if($num_result == 1) {
    $sql = "INSERT INTO `UserList`(`UserID`, `ListID`) VALUES (?,?)";
    if (($stmt_list = mysqli_prepare($conn, $sql))) {
      if (!$stmt_list->bind_param('ii', $uid, $listID)) {
        throw new \Exception("Database error: $stmt->errno - $stmt->error");
      }
      if ($stmt_list->execute()) {
        $message = "OK";
      } else {
        $message = "List is already assigned or something went wrong.";
      }
    } else {
      $message = "Failed";
    }
    mysqli_stmt_close($stmt_list);
  } else if ($num_result == 0) {
    $message = "User not found";
  } else {
    $message = "There was a mismatch";
  }
  mysqli_stmt_close($stmt);
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
        $wordBase = isset($_POST["wordbase"]) ? $_POST["wordbase"] : "";
				$translation = isset($_POST["translation"]) ? $_POST["translation"] : "";
				$description = isset($_POST["description"]) ? $_POST["description"] : "";
        $list = $_POST["list"];
        $forceInsert = isset($_POST["force"]) ? filter_var($_POST["force"], FILTER_VALIDATE_BOOLEAN) : false;
				$message = insertWord($conn, $userID, $word, $translation, $description, $wordBase, $list, $forceInsert);
				break;
			case "get":
				$message["action"] = "check";
				break;
			case "userinfo":
				$message["user"] = getUserInfo($conn, $userID);
			break;
      case "userdetail":
				$message["user"] = getUserDetail($conn, $userID);
			break;
      case "updateuserdetail":
				$message["status"] = updateUserDetail($conn, $userID, $_POST["firstname"], $_POST["lastname"], $_POST["nickname"]);
			break;
      case "updatepassword":
				$message["update"] = updatePassword($conn, $userID, $_POST["password"], $_POST["newpassword"], $passwordSalt);
			break;
			case "wordlist":
        $filter = "%";
        if (isset($_POST["filter"])) {
          $filter = $_POST["filter"];
        }
        $message["wordcount"] = getWordsCount($conn, $_POST["lists"], $userID, $filter);
				$message["words"] = getWordsList($conn, $userID, $_POST["lists"], $_POST["first"], $_POST["last"], $filter);
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
        $lists = array(-1);
        if(isset($_POST["lists"])) {
          $lists = $_POST["lists"];
          if (! is_array($lists)) {
            $lists = array($lists);
          }
        }
				if (isset($_GET["count"])) {
					$message["practicelist"] = getPracticeList($conn, $userID, $lists, $_GET["count"]);
				} else {
					$message["practicelist"] = getPracticeList($conn, $userID, $lists, 10);
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
        $wordBase = isset($_POST["wordbase"]) ? $_POST["wordbase"] : "";
				$translation = isset($_POST["translation"]) ? $_POST["translation"] : "";
				$description = isset($_POST["description"]) ? $_POST["description"] : "";
				$message["status"] = updateWord($conn, $userID, $wid, $word, $translation, $description, $wordBase);
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
				$message["results"] = getListList($conn, $userID, $_GET["first"], $_GET["last"]);
        $message["success"] = true;
				break;
      case "sharelistuser":
				$message["status"] = shareListUser($conn, $userID, $_POST["user"], $_POST["list"]);
				break;
      
		}
		
	} else {
		$message["status"] = "Invalid token";
	}
}
echo json_encode($message);
mysqli_close($conn);
?>