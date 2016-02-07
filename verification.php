<?PHP

date_default_timezone_set('Europe/Helsinki');
require_once ('connect.php');
require_once ('credentials.php');
require_once('helper/verificationhelper.php');
$message = "";

if (isset($_GET["code"])) {
	$message = verifyCode($conn, $_GET["code"], $verificationValid);
} else {
	$message = "wrong arguments";
}
mysqli_close($conn);

?>
<!DOCTYPE html>
<html>
<head>
  <!-- Standard Meta -->
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">

  <!-- Site Properities -->
  <title>Registeration Verification</title>
	<link rel="stylesheet" type="text/css" href="dist/semantic.css">
	<script src="assets/library/jquery.min.js"></script>
	<script src="dist/components/form.js"></script>
	<script src="dist/components/transition.js"></script>
	

  <style type="text/css">
    body {
      background-color: #DADADA;
    }
    body > .grid {
      height: 100%;
    }
    .image {
      margin-top: -100px;
    }
    .column {
      max-width: 450px;
    }
  </style>
</head>
<body>
<div class="ui center aligned grid">

	<div class="row" id="verification">
		<div class="left aligned column">
			<h2 class="ui teal image header">
			<p/>
				<div class="content">
					<?PHP print $message; ?>
				</div>
			</h2>
			
			<div class="ui large form">
				
				<button class="ui teal fluid submit button" id="backLogin">Back to Login</button>
			</div>
		</div>
	</div>
</div>

</body>
<script>
$("#backLogin").click(function(){
	window.location.href = "login.html";
});
</script>
</html>
