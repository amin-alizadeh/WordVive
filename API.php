<?PHP
date_default_timezone_set('Europe/Helsinki');
require_once ('connect.php');
require_once ('login.php');
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
	$sql = "SELECT NickName FROM UserInfo WHERE ID=?";//. $userID;
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

function insertWord($conn, $userID, $word, $translation, $description) {
	$sql = "INSERT INTO `Words` (`UserID`, `Word`, `Translation`, `Description`) VALUES (".
		 $userID . ", '" . $word . "', '" . $translation . "', '" . $description . "')";
	if ($conn->query($sql) === TRUE) {
		return "OK";
	} else { 
		return "Error";
	}
}
function updateWord($conn, $userID, $id, $word, $translation, $description) {
	$sql = "UPDATE `Words` SET `Word`=". $word .",`Translation`=". $translation .",`Description`=". $description .
		" WHERE `ID`=". $id ." AND `UserID`=". $userID;
	if ($conn->query($sql) === TRUE) {
		return "OK";
	} else { 
		return "Error";
	}
}

function getWordsList($conn, $userID, $first, $last) {
	$sql = "SELECT ID, Word, Translation, Description, Step FROM Words " .
			"WHERE UserID = " . $userID . " ORDER BY InsertTime DESC LIMIT " . $first . ", " . ($last - $first);
	$result = $conn->query($sql);
	$message = array();
	if ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
			$message[] = $row;
		}
	}
	return $message;
}

function getWordsCount($conn, $userID) {
	$sql = "SELECT COUNT(Word) AS wordcount FROM Words " .
			"WHERE UserID = " . $userID;
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
	$sql = "SELECT rndW.ID, Word, Translation, Description, Step FROM Words w "
    . "INNER JOIN ("
    . "SELECT ID FROM Words WHERE UserID=". $userID ." ORDER BY RAND() LIMIT 0, " . $n
    . ") AS rndW ON rndW.ID = w.ID";
	
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

function registerNewUser($conn, $username, $password, $firstname, $lastname, $email, $terms) {
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
		
		
		$sql = "INSERT INTO UserInfo(username, password, email, FirstName, LastName) VALUES (?,?,?,?,?)";
		$user_stmt =  $conn->prepare($sql);
		$user_stmt->bind_param("sssss", $username, $password, $email, $firstname, $lastname);
		if ($user_stmt->execute()) {
			$message['status'] = 'OK';
			
		} else {
			$message["status"] = "Fail";
			$message["message"] = "Server error. Try again later.";
		}
		mysqli_stmt_close($user_stmt);
		mysqli_stmt_close($stmt);
	}

	return $message;
}

$message = array();
if (isset($_GET["action"]) && $_GET["action"] == "login" && isset($_POST["username"]) && isset($_POST["password"])) {
	$message = checkLogin ($conn, $_POST["username"], $_POST["password"]);
	
} else if (isset($_GET["action"]) && $_GET["action"] == "register" && isset($_POST["username"]) && isset($_POST["password"]) && isset($_POST["firstname"]) && isset($_POST["lastname"]) && isset($_POST["email"]) && isset($_POST["terms"])) {
	
	$message = registerNewUser($conn, $_POST["username"], $_POST["password"], $_POST["firstname"], $_POST["lastname"], $_POST["email"], $_POST["terms"]);
	
} else if (isset($_GET["token"]) && isset($_GET["action"])) {
	$userID = getUserFromToken($conn, $_GET["token"]);
	if ( $userID != null) {
		$message["status"] = "OK";
		switch($_GET["action"]) {
			case "insert":
				$word = isset($_POST["word"]) ? $_POST["word"] : "";
				$translation = isset($_POST["translation"]) ? $_POST["translation"] : "";
				$description = isset($_POST["description"]) ? $_POST["description"] : "";
				$message["status"] = insertWord($conn, $userID, $word, $translation, $description);
				break;
			case "get":
				$message["action"] = "check";
				break;
			case "userinfo":
				$message["user"] = getUserInfo($conn, $userID);
			break;
			case "wordlist":
				$message["words"] = getWordsList($conn, $userID, $_GET["first"], $_GET["last"]);
				break;
			case "wordcount":
				$message["wordcount"] = getWordsCount($conn, $userID);
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
				$message["status"] = updateWord($conn, $userID, $id, $word, $translation, $description);
				break;
		}
		
	} else {
		$message["status"] = "Invalid token";
	}
}
echo json_encode($message);
mysqli_close($conn);
?>