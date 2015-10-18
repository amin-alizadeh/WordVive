<?PHP
$servername = "mysql1217.ixwebhosting.com";
$uname = "A968741_Connect";
$pwd = "Connect1364";
$dbname = "A968741_NMC_2015";
// Create connection
$conn = new mysqli($servername, $uname, $pwd, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
?>