<?php
$servername = "mysql1217.ixwebhosting.com";
$username = "A968741_Connect";
$password = "Connect1364";
$dbname = "A968741_NMC_2015";
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
echo "Connected successfully<br/>";

$sql = "SELECT ID, username, password, email, lastlogin FROM UserInfo";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // output data of each row
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row["ID"]. " - UserName: " . $row["username"]. " " . $row["password"]. " " . $row["email"]. " " . $row["lastlogin"].  "<br>";
    }
} else {
    echo "0 results";
}
$conn->close();
?>