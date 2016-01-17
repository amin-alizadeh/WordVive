<?PHP
require_once ('DBcredentials.php');

// Create connection
$conn = new mysqli($servername, $uname, $pwd, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
?>