<?PHP
date_default_timezone_set('Europe/Helsinki');
require_once ('connect.php'); //credentials.php is required in connect.php
require_once ('login.php'); //lib/random/random.php is required in login.php
//require_once 'PHPMailer/PHPMailerAutoload.php';
require_once ('helper/verificationhelper.php');
require_once ('helper/DBOperations.php');
//require_once ('helper/Emailer.php');

function getUserFromToken($conn, $token) {
	$uID = null;
	$sql = "SELECT UserID, ValidUntil, IsValid FROM SessionToken WHERE Token=?";
  
  $rows = fetchRows($conn, $sql, "s", $token);
  if (count($rows) == 1 && $rows[0]['IsValid'] == 1){
    return $rows[0]['UserID'];
  } else {
    return "";
  }
}

function getUserInfo($conn, $userID) {
	$sql = "SELECT CASE WHEN `NickName` IS NULL OR LENGTH(`NickName`) = 0 THEN CONCAT(`FirstName`,' ',`LastName`) ELSE `NickName` END AS NickName FROM UserInfo WHERE ID=?";
  
  $rows = fetchRows($conn, $sql, "i", $userID);
  
  if (count($rows) == 1){
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
  
	if (modifyRows($conn, $sql, "sssi", $nickname, $firstname, $lastname, $userID)) {
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
  
  $sql = "UPDATE `UserInfo` SET `password`=MD5(?+`Identifier`+?) WHERE `ID`=?";
  
	if (modifyRows($conn, $sql, "ssi", $newpassword, $passwordSalt, $userID)) {
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
    if (modifyRows($conn, $sql, "iissss", $userID, $list, $word, $translation, $description, $wordBase)) {
      $sql = "SELECT LAST_INSERT_ID() AS WordID";
      $wordIDRow = fetchRows($conn, $sql);
      $wordID = $wordIDRow[0]["WordID"];
      if ($wordID > 0) {
        $sql = "INSERT INTO `UserWordStep`(`UserID`, `WordID`) VALUES (?,?)";
        $msg["WordID"] = $wordID;
        if (modifyRows($conn, $sql, "ii", $userID, $wordID)) {
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
  
	$sql = "UPDATE Words AS w
  INNER JOIN UserList AS ul ON w.ListID = ul.ListID AND ul.UserID = ?
  SET w.`Word`=?, w.`Translation`=?,w.`Description`=?, w.`WordBase`=?
  WHERE w.`ID`=?";
	if (modifyRows($conn, $sql, "issssi", $userID, $word, $translation, $description, $wordBase, $id)) {
		return "OK";
	} else {
		return "Error";
	}
}

function deleteWord($conn, $userID, $id) {
	$sql = "DELETE w FROM `Words` AS w INNER JOIN UserList AS ul ON w.ListID = ul.ListID AND ul.UserID = ? WHERE w.`ID`=?";
  
	if (modifyRows($conn, $sql, "ii", $userID, $id)) {
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

  $sql = "SELECT w.ID, w.Word, w.Translation, w.Description, 
    CASE WHEN uws.Step IS NULL THEN 1 ELSE uws.Step END AS Step 
    FROM Words as w
    INNER JOIN UserList AS ul ON w.ListID = ul.ListID AND ul.UserID=? AND ul.ListID IN (%lists%) 
    LEFT OUTER JOIN UserWordStep AS uws ON (uws.WordID=w.ID AND uws.UserID=ul.UserID) 
    WHERE (w.WordBase LIKE ?) 
    ORDER BY InsertTime DESC LIMIT ?,?";
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
	$sql = "UPDATE SessionToken SET IsValid=0 WHERE Token=?";
	if (modifyRows($conn, $sql, "s", $token)) {
		return true;
	} else { 
		return false;
	}
}

function getPracticeList($conn, $userID, $lists, $n) {
  if (! is_array($lists)) {
    $list = $lists;
    $lists = array();
    $lists[0] = $list;
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
    array_push($args, intval($l));
  }

  array_push($args, $n);
  //var_dump($args);
  $rows = call_user_func_array('fetchRows', $args);

	return $rows;
}

function submitPractice($conn, $userID, $cr, $incr) {
  $sql = "INSERT INTO UserWordStep (WordID, UserID) ".
  "SELECT DISTINCT uws.WordID, ? ".
  "FROM UserWordStep uws ".
  "LEFT OUTER JOIN UserWordStep uwsOrg ON uws.WordID=uwsOrg.WordID AND uwsOrg.UserID=? ".
  "WHERE (uws.WordID IN (". $cr .") OR uws.WordID IN (". $incr .")) AND uwsOrg.UserID IS NULL";
  
  if (modifyRows($conn, $sql, "ii", $userID, $userID)) {
    $sqlcr = "UPDATE UserWordStep SET Step=Step+1, LastCheckTime=CURRENT_TIMESTAMP WHERE UserID=? AND WordID IN (". $cr .")";
    $sqlincr = "UPDATE UserWordStep SET Step=1, LastCheckTime=CURRENT_TIMESTAMP WHERE UserID=? AND WordID IN (". $incr .")";
    $crres = false;
    $incrres = false;
    
    if (strlen($cr) > 0) {
      if (modifyRows($conn, $sqlcr, "i", $userID)) {
        $crres = true;
      }
    } else {
      $crres = true;
    }
    
    if (strlen($incr) > 0) {
      if (modifyRows($conn, $sqlincr, "i", $userID)) {
        $incrres = true;
      }
    } else {
      $incrres = true;
    }
    return ($crres and $incrres);
  }
	return false;
}

function registerNewUser($conn, $username, $password, $firstname, $lastname, $email, $terms, $passwordSalt) {
	$message = array();
	$sql = 'SELECT (SELECT COUNT(username) FROM UserInfo WHERE username=?) AS usernameExist'.
			', (SELECT COUNT(email) FROM UserInfo WHERE email=?) AS emailExist';
	
  $row = fetchRows($conn, $sql, 'ss', $username, $email);
  $usernameExist = $rows[0]['usernameExist'];
  $emailExist = $rows[0]['emailExist'];
	
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
		if(modifyRows($conn, $sql, "ssssss", $username, $password, $email, $firstname, $lastname, $passwordSalt)){
			if (prepareVerification($conn, $username, $email, $firstname, $lastname)) {
				$message['status'] = 'OK';
			} else {
				$message["status"] = "Fail";
				$message["message"] = "Server error. Try again later.";
			}
		} else {
			$message["status"] = "Fail";
			$message["message"] = "Server error. Try again later.";
		}
	}

	return $message;
}

function prepareVerification($conn, $username, $email, $firstname, $lastname) {
	$sql_ver = "INSERT INTO `Verifications`(`Code`, `UserID`, `VerificationCode`) VALUES(?,?,?)";
	$sql_usr = "SELECT `ID`, `Identifier` FROM `UserInfo` WHERE username=?";
	
  $rows_usr = fetchRows($conn, $sql_usr, 's', $username);
  $userID = $rows_usr[0]['ID'];
  $identifier = $rows_usr[0]['Identifier'];
	
	$code = generateRandomString(32);
	$verCode = generateRandomString(8);
	
	if(modifyRows($conn, $sql_ver, "sis", $code, $userID, $verCode)) {
		return sendVerificationEmail($conn, $userID, $code, $verCode, $identifier, $email);
	} else {
		return false;
	}
  
}

function sendVerificationEmail($conn, $userID, $code, $verCode, $identifier, $email, $firstname, $lastname) {
  
	$preparedMail = prepareEmail($firstname, $lastname, $username, $code, $verCode);

	return sendEmail($email, $preparedMail["subject"], $preparedMail["body"], $preparedMail["altbody"], "WordVive Registration");
}

function getListList($conn, $userID, $first=0, $last=10) {
  $cnt = $last - $first;
  $sql = "SELECT `l`.`ListID` AS value, `l`.`ListName` AS name FROM `UserInfo` AS `usr` INNER JOIN `UserList` AS `ul` ON `ul`.`UserID` = `usr`.`ID` ".
      "INNER JOIN `List` AS `l` ON `l`.`ListID`=`ul`.`ListID` ".
      "WHERE `usr`.`ID`=? ORDER BY `ListCreatedOn` DESC LIMIT ?,?";
	
  return fetchRows($conn, $sql, "iii", $userID, $first, $cnt);
}

function insertList($conn, $userID, $list) {
  $message = array();
  $sql = "INSERT INTO `List`(`ListName`,`ListOwnerID`) VALUES (?, ?)";
  if (modifyRows($conn, $sql, "si", $list, $userID)) {
    $sql = "SELECT LAST_INSERT_ID() AS ListID";
    $rows_list = fetchRows($conn, $sql);
    if (count($rows_list) == 1) {
      $listID = intval($rows_list[0]['ListID']);      
      $sql = "INSERT INTO `UserList`(`ListID`, `UserID`) VALUES (?, ?)";
      if (modifyRows($conn, $sql, "ii", $listID, $userID)) {
        $message["status"] = "OK";
        $message["ListID"] = $listID;
      }
    }
  }
  return $message;
}

function renameList($conn, $userID, $listID, $listName) {
  $sql = "UPDATE `List` AS l 
  INNER JOIN `UserList` AS ul ON l.ListID=ul.ListID AND l.ListID=? AND ul.UserID=?
  SET l.`ListName`=?";
  if (modifyRows($conn, $sql, "iis", $listID, $userID, $listName)) {
    return "OK";
  }
  return "Failed";
}

function deleteList ($conn, $userID, $listID) {
  $sql = "DELETE FROM `UserList` WHERE `ListID`=? AND `UserID`=?";
    
  if (modifyRows($conn, $sql, "ii", $listID, $userID)) {
    return "OK";
  }
  return "Failed";
}
/*
function deleteList($conn, $userID, $listID) {
  $sql = "DELETE FROM `List` AS l INNER JOIN `UserList` AS ul ON l.ListID=ul.ListID AND l.ListID=? AND ul.UserID=?";
  if (modifyRows($conn, $sql, "ii", $listID, $userID)) {
    return "OK";
  }
  return "Failed";
}
*/
function shareListUser($conn, $userID, $user, $listID){
  $message = "Failed";
  $sql = "SELECT `ID` FROM `UserInfo` WHERE `email`=? OR `username`=?";
  $rows_ID = fetchRows($conn, $sql, 'ss', $user, $user);
  
  if(count($rows_ID) == 1) {
    $uid = intval($rows_ID[0]['ID']);
    $sql = "INSERT INTO `UserList`(`UserID`, `ListID`) VALUES (?,?)";
    if (modifyRows($conn, $sql, 'ii', $uid, $listID)) {
      $message = "OK";
    } else {
      $message = "List is already assigned or something went wrong.";
    }
  } else if ($num_result == 0) {
    $message = "User not found";
  } else {
    $message = "There was a mismatch";
  }
  return $message;
}

/*
Main body starts here
*/
$message = array();
if (isset($_GET["action"]) && $_GET["action"] == "login" && isset($_POST["username"]) && isset($_POST["password"])) {
	$message = checkLogin ($conn, $_POST["username"], $_POST["password"], $passwordSalt);
	
} else if (isset($_GET["action"]) && $_GET["action"] == "register" && isset($_POST["username"]) && isset($_POST["password"]) && isset($_POST["firstname"]) && isset($_POST["lastname"]) && isset($_POST["email"]) && isset($_POST["terms"])) {
	
	$message = registerNewUser($conn, $_POST["username"], $_POST["password"], $_POST["firstname"], $_POST["lastname"], $_POST["email"], $_POST["terms"], $passwordSalt);
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
				$message = insertList($conn, $userID, $list);
				break;
      case "listlist":
				$message["results"] = getListList($conn, $userID, $_GET["first"], $_GET["last"]);
        $message["success"] = true;
				break;
      case "sharelistuser":
				$message["status"] = shareListUser($conn, $userID, $_POST["user"], $_POST["list"]);
				break;
      case "renamelist":
        $message["status"] = renameList ($conn, $userID, $_POST["id"], $_POST["name"]);
        break;
      case "deletelist":
        $message["status"] = deleteList ($conn, $userID, $_POST["id"]);
        break;
      
		}
		
	} else {
		$message["status"] = "Invalid token";
	}
}
echo json_encode($message);
mysqli_close($conn);
?>