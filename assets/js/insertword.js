var wordsPerPage = 5;
var paginationStart = '<div class="ui right floated pagination menu">';
var paginationEnd = '</div>';
var pageJump = '<a class="item" onclick="jumpToPage(%n%)" id="pgJump%n%">%n%</a>';
var previousPage = '<a class="icon item" onclick="jumpToPreviousPage()"><i class="left chevron icon"></i></a>';
var nextPage = '<a class="icon item" onclick="jumpToNextPage()"><i class="right chevron icon"></i></a>';
var currentPage = 0;
var numberOfPages = 1;
var wordSettings = '<button class="circular ui tiny teal icon button"><i class="teal edit icon"></i></button>'+
					'<button class="circular ui tiny red icon button"><i class="red remove icon"></i></button>';
var wordSettings = '<i onclick="editWord(%n%)" class="teal edit icon"></i>'+
					'<i onclick="deleteWord(%n%)" class="red remove icon"></i>';
var wordsList = [];
var wordEditID = -1;
var wordDeleteID = -1;

$(document).ready(function() {
  $('#wordsPerPage').val(wordsPerPage);
  $('#wordsPerPage').change(function() {
    var newWordsPerPage = parseInt($('#wordsPerPage').val());
    var newPageNumber = Math.floor(((currentPage-1) * wordsPerPage) / newWordsPerPage) + 1;
    populateWords(newWordsPerPage, newPageNumber);
  });
  
	$('#wordEditModal').modal({closable:true}).modal('setting', 'transition', 'horizontal flip');
	$('#wordDeleteModal').modal({closable:true}).modal('setting', 'transition', 'horizontal flip');
	populateWords(wordsPerPage, 1);
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
					if (currentPage == 1) {
						/*var tbl = document.getElementById("wordlist");
						tbl.deleteRow(tbl.rows.length - 2);
						var r = tbl.insertRow(1);
						var w = r.insertCell(0);
						var t = r.insertCell(1);
						var d = r.insertCell(2);
						var s = r.insertCell(3);
						var ss = r.insertCell(4);
						w.innerHTML = word;
						t.innerHTML = translation;
						d.innerHTML = description;
						s.innerHTML = "1";
						ss.innerHTML = wordSettings;*/
						populateWords(wordsPerPage, 1);
					} else {
						populateWords(wordsPerPage, 1);
					}
				} else {
					alert ("Something went wrong! Try again later.");
				}
			});
		}
		
	});
});

function populateWords(setWordsPerPage, jump) {
  wordsPerPage = setWordsPerPage;
	var tbl = document.getElementById("wordlist");
	$("#wordTable").addClass("loading");
	$.get("API.php?token=" + token + "&action=wordcount", function (data) {
		$("#wordTable").removeClass("loading");
		res = jQuery.parseJSON(data);
		var wordsCount = res.wordcount;
		numberOfPages = Math.ceil(wordsCount / setWordsPerPage);
		
		if (jump < 1) jump = 1;
    if(jump > numberOfPages) jump = numberOfPages;
    jumpToPage(jump);
	});
	
}

function paginationList(numberOfPages, jump) {
  if (numberOfPages > 1) {
			var pageNumbers = (numberOfPages > 1) ? paginationStart + previousPage : "";
			var paginationCorrectionAdded = false;
      //console.log("---");
			for (var i = 1; i <= numberOfPages; i++) {
        //console.log("page: " + i + " of " + numberOfPages + " to " + jump + " then " + ((i < 2) || ((numberOfPages - i) < 2) || (Math.abs(jump - i) < 2)));
        if ((i < 2) || ((numberOfPages - i) < 2) || (Math.abs(jump - i) < 2)) {
          pageNumbers += pageJump.replace("%n%", i).replace("%n%", i).replace("%n%", i);
          paginationCorrectionAdded = false;
        } else if (!paginationCorrectionAdded){
          pageNumbers += '<a class="item">:</a>';
          paginationCorrectionAdded = true;
        }
			}
			pageNumbers += (numberOfPages > 1) ? nextPage + paginationEnd : "";
			$("#pageNavigation").html(pageNumbers);
		}
}

function wordsListPagination(firstW, lastW) {	
	$("#wordTable").addClass("loading");
	$.get("API.php?token=" + token + "&action=wordlist&first=" + firstW + "&last=" + lastW, function (data) {
		$("#wordTable").removeClass("loading");
		var res = jQuery.parseJSON(data);
		wordsList = res.words;
		var tbl = document.getElementById("wordlist");
		//var i = 0;
		//Prepare Table
		var tblLength = tbl.rows.length;
		for (var i = 1; i < tblLength - 1; i++) {
			tbl.deleteRow(1);
		}
				
		for (var i = 0; i < wordsList.length; i++) {
			var word = wordsList[i];
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
			ss.innerHTML = wordSettings.replace("%n%", i).replace("%n%", i);
			ss.className = "right aligned";
		}
		
	});
}

function jumpToPage(n) {
	if (n > 0 && n <= numberOfPages) {
		if (currentPage > 0) $("#pgJump" + currentPage).removeClass("disabled");
		currentPage = n;
		$("#pgJump" + currentPage).addClass("disabled");
		wordsListPagination((n - 1) * wordsPerPage, n * wordsPerPage);
    paginationList(numberOfPages, n);
	}
}

function jumpToNextPage() {
	jumpToPage(currentPage + 1);
}

function jumpToPreviousPage() {
	jumpToPage(currentPage - 1);
}

function editWord(id) {
	if (! $('#wordEditModal').modal('is active')) $('#wordEditModal').modal('show');
	$('#editword').val(wordsList[id].Word);
	$('#edittranslation').val(wordsList[id].Translation);
	$('#editdescription').val(wordsList[id].Description);
	wordEditID = id;
}

function deleteWord(id) {
	if (! $('#wordDeleteModal').modal('is active')) $('#wordDeleteModal').modal('show');
	$('#deleteWordDes').html(wordsList[id].Word);
	
	wordDeleteID = id;
}

function submitEdit() {
	$("#modalDescription").addClass("loading");
	$("#submitEditWord").addClass("loading");
	$("#cancelEditWord").addClass("loading");
	$.post("API.php?token=" + token + "&action=updateword", {word:$('#editword').val(), id:wordsList[wordEditID].ID, 
	translation:$('#edittranslation').val(), description:$('#editdescription').val()}, function (data) {
		$("#modalDescription").removeClass("loading");
		$("#submitEditWord").removeClass("loading");
		$("#cancelEditWord").removeClass("loading");
		$('#wordEditModal').modal('hide');
		var res = jQuery.parseJSON(data);
		if (res.status == "OK") {
			console.log("OK");
      jumpToPage(currentPage);
		}
	});
}

function submitDelete() {
	$("#submitDeleteWord").addClass("loading");
	$("#cancelDeleteWord").addClass("loading");
	$.post("API.php?token=" + token + "&action=deleteword", {id:wordsList[wordDeleteID].ID}, function (data) {
		$("#submitDeleteWord").removeClass("loading");
		$("#submitDeleteWord").removeClass("loading");
		$('#wordDeleteModal').modal('hide');
		var res = jQuery.parseJSON(data);
		if (res.status == "OK") {
			console.log("OK");
      jumpToPage(currentPage);
		}
	});
}

function cancelEdit() {
  if ($('#wordEditModal').modal('is active')) $('#wordEditModal').modal('hide');
}

function cancelDelete() {
  if ($('#wordDeleteModal').modal('is active')) $('#wordDeleteModal').modal('hide');
}