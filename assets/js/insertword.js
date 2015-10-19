var wordPerPage = 20;
$(document).ready(function() {
	populateWords(wordPerPage);
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

function populateWords(wordPerPage) {
	var tbl = document.getElementById("wordlist");
	for (var i = 0; i < wordPerPage; i++) {
		var r = tbl.insertRow(i+1);
		var w = r.insertCell(0);
		var t = r.insertCell(1);
		var d = r.insertCell(2);
		var s = r.insertCell(3);
		var ss = r.insertCell(4);
	}
	$.get("API.php?token=" + token + "&action=wordcount", function (data) {
		var res = jQuery.parseJSON(data);
		var wordsCount = res.wordcount;
		wordsListPagination(0, wordPerPage, wordPerPage);
	});
	
}


function wordsListPagination(firstW, lastW, wordPerPage) {	
	$.get("API.php?token=" + token + "&action=wordlist&first=" + firstW + "&last=" + lastW, function (data) {
		var res = jQuery.parseJSON(data);
		var words = res.words;
		var tbl = document.getElementById("wordlist");
		var i = 0;
		var rows = tbl.rows;
		for (i; i < words.length; i++) {
			var word = words[i];
			
			var r = rows[i + 1];
			var c = r.cells;
			c[0].innerHTML = word.Word;
			c[1].innerHTML = word.Translation;
			c[2].innerHTML = word.Description;
			c[3].innerHTML = word.Step;
			c[4].innerHTML = "";
		}
		for (i; i < wordPerPage; i++) {
			console.log(i);
			//tbl.deleteRow(i);
		}
	});
}