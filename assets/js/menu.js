if (localStorage.hasOwnProperty("token")) {
	var token = localStorage.token;
} else {
	window.location = "login.html";
}
$(document).ready(function() {
	$.get("API.php?action=userinfo&token=" + token, function (data) {
		var res = jQuery.parseJSON(data);
		$("#username").text(res.user);
	});
//	$("username").text();
	$("#profile").click(function(){
		console.log("profile");
	});
	
	$("#settings").click(function(){
		console.log("settings");
	});
	
	$("#signout").click(function(){
		console.log("signout");
	});
});