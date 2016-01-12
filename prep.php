<?PHP
date_default_timezone_set('Europe/Helsinki');
require_once ('connect.php');

//$sql = "SELECT ID, Enabled FROM UserInfo"; 
$uname = "amin";
$pwd = "30ae43ad1aa0a416699051b73a3dfcf6";
$query = "SELECT ID, Enabled, username FROM UserInfo";// WHERE username=? AND Password=?";

if (!($stmt = mysqli_prepare($conn, $query))) {
	echo "Could not prepare the statement";
}
/*
if (!$stmt->bind_param('ss', $uname, $pwd)) {
    throw new \Exception("Database error: $stmt->errno - $stmt->error");
}
*/
/* execute statement */
mysqli_stmt_execute($stmt);

/* bind result variables */
mysqli_stmt_bind_result($stmt, $name, $code, $uname);

/* fetch values */
while (mysqli_stmt_fetch($stmt)) {
	printf ("%s: %s (%s)<br/>", $uname, $name, $code);
}

/* close statement */
mysqli_stmt_close($stmt);
/* close connection */
mysqli_close($conn);
?>