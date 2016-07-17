var token = "1";
if (localStorage.hasOwnProperty("token")) {
	token = localStorage.token;
} else {
	window.location = "login.html";
}

var menu = '<a href="/insertword.html" class="item"><i class="home icon"></i>Home</a>' +
      '<div class="ui inverted compact menu">'+
        '<div class="ui simple dropdown item">Tests'+
          '<i class="dropdown icon"></i>'+
          '<div class="menu">'+
            '<a href="/tests/testwords.html" class="item"><i class="check square icon"></i>Yes/No</a>' +
            '<a href="/tests/multiplechoice.html" class="item"><i class="list icon"></i>Multiple Choice</a>' +
          '</div>'+
       '</div>'+
      '</div>'+
			'<div class="right menu">' +
				'<a class="ui simple dropdown item">' +
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
	$.get("/API.php?action=userinfo&token=" + token, function (data) {
		var res = jQuery.parseJSON(data);
		$("#username").text(res.user);
	});
//	$("username").text();
	$("#profile").click(function(){
		window.location.href = 'profile.html';
	});
	
	$("#settings").click(function(){
		console.log("settings");
	});
	
	$("#signout").click(function(){
		$.get("/API.php?token=" + token + "&action=logout", function (data) {
			var res = jQuery.parseJSON(data);
			if (res.status == "OK" && res.logout) {
				localStorage.removeItem("token");
				window.location.href = "login.html";
			} else {
				alert ("Something went wrong! Please try again later.");
			}
		});
	});
});