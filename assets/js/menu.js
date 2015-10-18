var token = "1";
if (localStorage.hasOwnProperty("token")) {
	token = localStorage.token;
} else {
	window.location = "login.html";
}

var menu = '<a href="#" class="item"><i class="home icon"></i>Home</a>' +
			'<div class="right menu">' +
				'<a href="#" class="ui simple dropdown item">' +
					'<div id="username">Hello User!</div> <i class="dropdown icon"></i>' +
					'<div class="menu">' +
						'<div class="item" id="profile"><i class="student icon"></i>Profile</div>' +
						'<div class="item" id="settings"><i class="settings icon"></i>Settings</div>' +
						'<div class="divider"></div>' +
						'<!--<div class="header">Header Item</div>-->' +
						'<div class="item" id="signout"><i class="sign out icon"></i>Sign out</div>' +
					'</div>' +
				'</a>' +
			'</div>';
$("#menu").html(menu);
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