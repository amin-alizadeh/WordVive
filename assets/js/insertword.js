var wordsPerPage = 2;
var paginationStart = '<div class="ui right floated pagination menu">';
var paginationEnd = '</div>';
var pageJump = '<a class="item" onclick="jumpToPage(%n%)">%n%</a>';
var previousPage = '<a class="icon item" onclick="jumpToPreviousPage()"><i class="left chevron icon"></i></a>';
var nextPage = '<a class="icon item" onclick="jumpToNextPage()"><i class="right chevron icon"></i></a>';
var currentPage = 1;

$(document).ready(function() {
	populateWords(wordsPerPage);
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
					alert ("Something went wrong! Try again later.");
				}
			});
		}
		
	});
});

function populateWords(wordsPerPage) {
	var tbl = document.getElementById("wordlist");
	/*
	for (var i = 0; i < wordsPerPage; i++) {
		var r = tbl.insertRow(i+1);
		var w = r.insertCell(0);
		var t = r.insertCell(1);
		var d = r.insertCell(2);
		var s = r.insertCell(3);
		var ss = r.insertCell(4);
	}
	*/
	$.get("API.php?token=" + token + "&action=wordcount", function (data) {
		var res = jQuery.parseJSON(data);
		var wordsCount = res.wordcount;
		var numberOfPages = (wordsCount / wordsPerPage);
		if (numberOfPages > 1) {
			var pageNumbers = (numberOfPages > 1) ? paginationStart + previousPage : "";
			
			for (var i = 1; i <= numberOfPages; i++) {
				pageNumbers += pageJump.replace("%n%", i).replace("%n%", i);
			}
			pageNumbers += (numberOfPages > 1) ? nextPage + paginationEnd : "";
			$("#pageNavigation").html(pageNumbers);
		}
		jumpToPage(currentPage);
	});
	
}


function wordsListPagination(firstW, lastW) {	
	$("#wordTable").addClass("loading");
	$.get("API.php?token=" + token + "&action=wordlist&first=" + firstW + "&last=" + lastW, function (data) {
		$("#wordTable").removeClass("loading");
		var res = jQuery.parseJSON(data);
		var words = res.words;
		var tbl = document.getElementById("wordlist");
		//var i = 0;
		//Prepare Table
		var tblLength = tbl.rows.length;
		for (var i = 1; i < tblLength - 1; i++) {
			tbl.deleteRow(1);
		}
				
		for (var i = 0; i < words.length; i++) {
			var word = words[i];
			var r = tbl.insertRow(i + 1);
			var w = r.insertCell(0);
			var t = r.insertCell(1);
			var d = r.insertCell(2);
			var s = r.insertCell(3);
			var ss = r.insertCell(4);
			w.innerHTML = word.Word;
			t.innerHTML = word.Translation;
			d.innerHTML = word.Description;
			s.innerHTML = word.Step;
			ss.innerHTML = "";
		}
		
	});
}

function jumpToPage(n) {
	currentPage = n;
	wordsListPagination((n - 1) * wordsPerPage, n * wordsPerPage);
	console.log(n);
}

function jumpToNextPage() {
	jumpToPage(currentPage + 1);
}

function jumpToPreviousPage() {
	jumpToPage(currentPage - 1);
}