var wordsPerPage = 10;
var paginationStart = '<div class="ui right floated pagination menu">';
var paginationEnd = '</div>';
var pageJump = '<a class="item" onclick="jumpToPage(%n%)" id="pgJump%n%">%n%</a>';
var pageJumpDisabled = '<a class="disabled item" onclick="jumpToPage(%n%)" id="pgJump%n%">%n%</a>';
var previousPage = '<a class="icon item" onclick="jumpToPreviousPage()"><i class="left chevron icon"></i></a>';
var nextPage = '<a class="icon item" onclick="jumpToNextPage()"><i class="right chevron icon"></i></a>';
var currentPage = 1;
var numberOfPages = 1;
var wordSettings = '<button class="circular ui tiny teal icon button"><i class="teal edit icon"></i></button>'+
					'<button class="circular ui tiny red icon button"><i class="red remove icon"></i></button>';
var wordSettings = '<i onclick="editWord(%n%)" class="teal edit icon"></i>'+
					'<i onclick="deleteWord(%n%)" class="red remove icon"></i>';
var wordsList = [];
var listList = [];
var wordEditID = -1;
var wordDeleteID = -1;

if (localStorage.hasOwnProperty("currentPage")) {
  currentPage = localStorage.currentPage;
}
if (localStorage.hasOwnProperty("wordsPerPage")) {
  wordsPerPage = localStorage.wordsPerPage;
}

$(document).ready(function() {
  //Fetch the word lists:
  var firstL = 0;
  var lastL = 10;
  getWordLists(firstL, lastL);
  
});

function getWordLists(firstL, lastL) {
  $.get("API.php?token=" + token + "&action=listlist&first=" + firstL + "&last=" + lastL, function (data) {
    var res = jQuery.parseJSON(data);
		listList = res.lists;
    $('#wordLists').html("");
    
    var listExists = false;
    var selectedList = -1;
    if (localStorage.hasOwnProperty("selectedList")) {
      selectedList = localStorage.selectedList;
    }
    
    for (var i = 0; i < listList.length; i++) {
      $('#wordLists').
      append($("<option></option>").
      attr("value", listList[i].ListID).
      text(listList[i].ListName));
      if (selectedList == listList[i].ListID) {
        listExists = true;
      }
    }
    
    if (listExists) {
      $('#wordLists').val(selectedList);
    }
    
    selectWordsList();
    populateWords(wordsPerPage, currentPage);
  });
}

function selectWordsList(){
  $('#wordLists').change(function() {
    var listID = parseInt($('#wordLists').val());
    localStorage.selectedList = listID;
    populateWords(wordsPerPage, 1);
  });
    
  $('#wordsPerPage').val(wordsPerPage);
  $('#wordsPerPage').change(function() {
    var newWordsPerPage = parseInt($('#wordsPerPage').val());
    var newPageNumber = Math.floor(((currentPage-1) * wordsPerPage) / newWordsPerPage) + 1;
    populateWords(newWordsPerPage, newPageNumber);
  });
  
	$('#wordEditModal').modal({closable:true}).modal('setting', 'transition', 'horizontal flip');
	$('#wordDeleteModal').modal({closable:true}).modal('setting', 'transition', 'horizontal flip');
	$('#shareListModal').modal({closable:true}).modal('setting', 'transition', 'horizontal flip');
  
  $("#insertList").click(function () {
    var list = $("#listName").val().trim();
    if (word.length == 0) {
      $("#listName").addClass("error");
    } else {
      $("#listName").removeClass("error");
      $("#insertList").addClass("loading");
      $.post("API.php?token=" + token + "&action=insertlist", {list:list}, function (data) {
        $("#insertList").removeClass("loading");
        var res = jQuery.parseJSON(data);
				if (res.status == "OK") {
          $("#listName").val("");
          getWordLists(0, 10);
        }
      });
    }
  });
  
  $("#shareList").click(function () {
    $('#shareListMessage').addClass('hidden');
    $('#shareListMessage').html('');
    $('#shareUser').val('');
    $('#shareListModal').modal('show');
  });
  
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
      
			var list = $('#wordLists').val();
			$.post("API.php?token=" + token + "&action=insert", {word:word, translation:translation, description:description, list:list}, function (data) {
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
}
function populateWords(setWordsPerPage, jump) {
  wordsPerPage = setWordsPerPage;
  localStorage.wordsPerPage = wordsPerPage;
	var tbl = document.getElementById("wordlist");
	$("#wordTable").addClass("loading");
  var list = $('#wordLists').val();
	$.get("API.php?token=" + token + "&action=wordcount&list="+list, function (data) {
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
          if (i == jump) {
            pageNumbers += pageJumpDisabled.replace("%n%", i).replace("%n%", i).replace("%n%", i);
          }else {
            pageNumbers += pageJump.replace("%n%", i).replace("%n%", i).replace("%n%", i);
          }
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
  var list = $('#wordLists').val();
	$.post("API.php?token=" + token + "&action=wordlist", {list: list ,first: firstW, last: lastW}, function (data) {
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
    localStorage.currentPage = currentPage;
		$("#pgJump" + currentPage).addClass("disabled");
    console.log(n);
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
	$.post("API.php?token=" + token + "&action=updateword", 
  {word:$('#editword').val().trim(), id:wordsList[wordEditID].ID, 
	translation:$('#edittranslation').val().trim(), description:$('#editdescription').val().trim()}, function (data) {
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

function cancelShareList() {
  $('#shareListModal').modal('hide');
  $('#shareUser').val("");
}

function submitShareList() {
  var usr = $('#shareUser').val().trim().toLowerCase();
  var list = parseInt($('#wordLists').val());
  $('#submitShareList').addClass('loading');
  $('#cancelShareList').addClass('loading');
  $('#shareUserField').addClass('loading');
  $.post("API.php?token=" + token + "&action=sharelistuser", {user:usr, list: list}, function (data) {
    $('#submitShareList').removeClass('loading');
    $('#cancelShareList').removeClass('loading');
    $('#shareUserField').removeClass('loading');
    var res = jQuery.parseJSON(data);
		if (res.status == "OK") {
      $('#shareListModal').modal('hide');
    } else {
      $('#shareListMessage').html(res.status);
      $('#shareListMessage').removeClass('hidden');
    }
  });
  
}