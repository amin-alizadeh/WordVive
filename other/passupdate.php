<?PHP
date_default_timezone_set('Europe/Helsinki');
require_once ('../connect.php');
require_once ('../credentials.php');

$sql = "SELECT `ID`, `Identifier`, `username`, `password` FROM `UserInfo`";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
  while($row = $result->fetch_assoc()) {
    $id = intval($row["ID"]);
    $md = md5($row["password"] . $passwordSalt . $row["Identifier"]);
    echo $row["ID"] . " " . $row["username"] . " " . $row["Identifier"] . " " . $row["password"] . "<br/>";
    echo "finally: " . $md . "<br/>";
    $sqlup = "UPDATE `UserInfo` SET `password2` = ? WHERE `ID`=?";
    $word_stmt = $conn->prepare($sqlup);
    $word_stmt->bind_param("is", $id, $md);
    
    if ($word_stmt->execute()) {
      echo "OK<p/>";
    } else {
      echo "Error<p/>";
    }
    $word_stmt->close();
  }
}
/*
BEGIN 
	DECLARE uid VARCHAR(32);
	SET uid = REPLACE(UUID(),'-','');
	SET NEW.Identifier = uid;
	SET NEW.password2 = MD5(NEW.password + uid + NEW.passwordsalt);
	SET NEW.password = NULL;
	SET NEW.passwordsalt = NULL;
  INSERT INTO `UserInfo`(`username`, `password`, `passwordsalt`, `email`, `FirstName`, `LastName`) VALUES 
('baba', 'f37a8c55e528fd03add3ac44e8bf564f', 'unDer8shIrt#86', 'baba@gmail.com', 'ba' , 'ba')
END
*/
?>