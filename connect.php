<?PHP
require_once ('credentials.php');

// Create connection
//$conn = mysqli_connect($servername, $uname, $pwd, $dbname);
$conn = new mysqli($servername, $uname, $pwd, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
?>