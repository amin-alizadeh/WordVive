<?PHP
date_default_timezone_set('Europe/Helsinki');
require_once ('connect.php');
require_once ('login.php');
function getUserFromToken($conn, $token) {
	$userID = null;
	$sql = "SELECT `UserID`, `ValidUntil`, `IsValid` FROM `SessionToken` WHERE Token='" . $token . "'";
	$result = $conn->query($sql);

	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		
		if($row["IsValid"] == 1) {
			$userID = $row["UserID"];
		}
	}

	return $userID;
}
function getUserInfo($conn, $userID) {
	$sql = "SELECT `NickName` FROM `UserInfo` WHERE ID=". $userID;
	$result = $conn->query($sql);

	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		return $row["NickName"];
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


$message = array();
if (isset($_GET["action"]) && $_GET["action"] == "login" && isset($_POST["username"]) && isset($_POST["password"])) {
	$message = checkLogin ($conn, $_POST["username"], $_POST["password"]);
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
		}
		
	} else {
		$message["status"] = "Invalid";
	}
}
echo json_encode($message);
?>