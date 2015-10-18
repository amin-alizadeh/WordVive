var token = "1";
$(document).ready(function() {
	$("#insert").click(function () {
		var word = $("#word").val().trim();
		var translation = $("#translation").val().trim();
		var description = $("#description").val().trim();
		if (word.length == 0 && translation.length == 0) {
			$("#wordfield").addClass("error");
			$("#translationfield").addClass("error");
		} else if (word.length == 0) {
			$("#wordfield").addClass("error");
			$("#translationfield").removeClass("error");
		} else if (translation.length == 0) {
			$("#translationfield").addClass("error");
			$("#wordfield").removeClass("error");
		} else { //everything alright
			$("#wordfield").removeClass("error");
			$("#translationfield").removeClass("error");
			$("#insert").addClass("loading");
			
			$.post("API.php?token=" + token + "&action=insert", {word:word, translation:translation, description:description}, function (data) {
				$("#insert").removeClass("loading");
				var res = jQuery.parseJSON(data);
				if (res.status == "OK") {
					$("#word").val("");
					$("#translation").val("");
					$("#description").val("");
					$("#insert").removeClass("teal");
					$("#insert").addClass("green");
					setTimeout(function(){
						$("#insert").removeClass("green");
						$("#insert").addClass("teal");
					}, 1000);
					$("#insert").addClass("teal");
				} else {
					alert ("Something went wrong. Try again later");
				}
			});
		}
		
	});
});
