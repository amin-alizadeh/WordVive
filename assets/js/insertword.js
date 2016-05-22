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
var dupWord = [];
var wordEditID = -1;
var wordDeleteID = -1;
var firstL = 0;
var lastL = 50;
var forceInsertWord = false;
$("#duplicateWord").css("visibility", "hidden");

if (localStorage.hasOwnProperty("currentPage")) {
  currentPage = parseInt(localStorage.currentPage);
}
if (localStorage.hasOwnProperty("wordsPerPage")) {
  wordsPerPage = parseInt(localStorage.wordsPerPage);
}

$(document).ready(function() {
  
  $('#translation').focusout(function(){beutifyInput($('#translation'))});
  $('#description').focusout(function(){beutifyInput($('#description'))});
  $('#edittranslation').focusout(function(){beutifyInput($('#edittranslation'))});
  $('#editdescription').focusout(function(){beutifyInput($('#editdescription'))});
  
  //Fetch the word lists:  
  getWordLists(firstL, lastL);
  selectWordsList();
  
});

function getWordLists(firstL, lastL) {
  
  $.get("API.php?token=" + token + "&action=listlist&first=" + firstL + "&last=" + lastL, function (data) {
    var res = jQuery.parseJSON(data);
		listList = res.results;
    $('#wordLists').html("");
    
    var listExists = false;
    var selectedList = -1;
    if (localStorage.hasOwnProperty("selectedList")) {
      selectedList = parseInt(localStorage.selectedList);
    }
    
    for (var i = 0; i < listList.length; i++) {
      $('#wordLists').
      append($("<option></option>").
      attr("value", listList[i].value).
      text(listList[i].name));
      if (selectedList == listList[i].value) {
        listExists = true;
      }
    }
    
    if (listExists) {
      $('#wordLists').val(selectedList);
    }
    $('#wordLists').dropdown();
    
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
          localStorage.selectedList = res.ListID;
          getWordLists(firstL, lastL);
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
    var wordBase = word.toLowerCase().split(' ').join('').split('+').join('').split('/').join('');
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
			$.post("API.php?token=" + token + "&action=insert", 
      {word:word, translation:translation, description:description, list:list, wordbase:wordBase, force: false}, 
      function (data) {
				$("#insert").removeClass("loading");
        $("#duplicateWord").css("visibility", "hidden");
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
        } else if (res.status == "Duplicate") {
          //$("#duplicateWord").css("visibility", "visible");
          $('#editCheckbox').css('visibility', 'visible');
          $("#modalHeader").html("Duplicate");
          $('#editCheckboxObj').attr('checked', false);
          if (! $('#wordEditModal').modal('is active')) $('#wordEditModal').modal('show');
          wordEditID = res.word.ID;
          dupWord = res.word;
          editDuplicateWord();
                    
				} else {
					alert ("Something went wrong! Try again later.");
				}
			});
		}
		
	});
  
  $("#searchWordSubmit").click(function () {
    jumpToPage(1);
  });
  
  $("#clearSearchWord").click(function () {
    $("#searchWord").val('');
    jumpToPage(1);
  });
  
}
function populateWords(setWordsPerPage, jump) {
  wordsPerPage = setWordsPerPage;
  localStorage.wordsPerPage = wordsPerPage;
	var tbl = document.getElementById("wordlist");
	$("#wordTable").addClass("loading");
  var list = $('#wordLists').val();
  if (jump < 1) jump = 1;
  if(jump > numberOfPages) jump = numberOfPages;
  jumpToPage(jump);
	
}


function jumpToPage(n) {
  if (n > 0) {
    $("#wordTable").addClass("loading");
    var list = $('#wordLists').val();
    var firstW = (n - 1) * wordsPerPage;
    var lastW = n * wordsPerPage
    var filter = $("#searchWord").val().toLowerCase().trim().split("*").join("%") + '%';
    if (filter.length<1){
      filter = "%";
    }
    $.post("API.php?token=" + token + "&action=wordlist", 
    {lists: list ,first: firstW, last: lastW, filter: filter}, 
    function (data) {
      $("#wordTable").removeClass("loading");
      //$("#searchWord").val('');
      var res = jQuery.parseJSON(data);
      wordsList = res.words;
      var wordsCount = res.wordcount;
      numberOfPages = (wordsCount == 0) ? 1: Math.ceil(wordsCount / wordsPerPage);
      if (n <= numberOfPages) {
        if (currentPage > 0) $("#pgJump" + currentPage).removeClass("disabled");
        currentPage = n;
        localStorage.currentPage = currentPage;
        $("#pgJump" + currentPage).addClass("disabled");
        var tbl = document.getElementById("wordlist");
        //var i = 0;
        //Prepare Table
        var tblLength = tbl.rows.length;
        for (var i = 1; i < tblLength - 1; i++) {
          tbl.deleteRow(1);
        }
        if (wordsList) {		
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
        }
        
        var pageNumbers = (numberOfPages > 1) ? paginationStart + previousPage : "";
        if (numberOfPages > 1) {
            var pageNumbers = (numberOfPages > 1) ? paginationStart + previousPage : "";
            var paginationCorrectionAdded = false;
            //console.log("---");
            for (var i = 1; i <= numberOfPages; i++) {
              //console.log("page: " + i + " of " + numberOfPages + " to " + jump + " then " + ((i < 2) || ((numberOfPages - i) < 2) || (Math.abs(jump - i) < 2)));
              if ((i < 2) || ((numberOfPages - i) < 2) || (Math.abs(n - i) < 2)) {
                if (i == n) {
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
          }
          pageNumbers += (numberOfPages > 1) ? nextPage + paginationEnd : "";
          $("#pageNavigation").html(pageNumbers);
      }
    });
	}
}

function jumpToNextPage() {
	jumpToPage(currentPage + 1);
}

function jumpToPreviousPage() {
	jumpToPage(currentPage - 1);
}

function editWord(id) {
  $("#modalHeader").html("Edit");
  $('#editCheckbox').css('visibility', 'hidden');
  $('#submitEditWord').css('display', 'inline');
  $('#submitInsertNewWord').css('display', 'none');
	if (! $('#wordEditModal').modal('is active')) $('#wordEditModal').modal('show');
	$('#editword').val(wordsList[id].Word);
	$('#edittranslation').val(wordsList[id].Translation);
	$('#editdescription').val(wordsList[id].Description);
	wordEditID = wordsList[id].ID;
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
  var word = $('#editword').val().trim();
  var wordBase = word.toLowerCase().split(' ').join('').split('+').join('').split('/').join('');
	$.post("API.php?token=" + token + "&action=updateword", 
  {word:word, id:wordEditID, 
	translation:$('#edittranslation').val().trim(), 
  description:$('#editdescription').val().trim(), wordbase:wordBase}, 
  function (data) {
		$("#modalDescription").removeClass("loading");
		$("#submitEditWord").removeClass("loading");
		$("#cancelEditWord").removeClass("loading");
		$('#wordEditModal').modal('hide');
		var res = jQuery.parseJSON(data);
		if (res.status == "OK") {
      jumpToPage(currentPage);
      $("#word").val("");
      $("#translation").val("");
      $("#description").val("");
		}
	});
}

function submitInsertNewWord() {
  $("#modalDescription").addClass("loading");
	$("#submitEditWord").addClass("loading");
	$("#cancelEditWord").addClass("loading");
  $("#submitInsertNewWord").addClass("loading");
  
  var list = $('#wordLists').val();
  var word = $('#editword').val().trim();
  var wordBase = word.toLowerCase().split(' ').join('').split('+').join('').split('/').join('');
  var translation = $('#edittranslation').val().trim();
  var description = $('#editdescription').val().trim();
  $.post("API.php?token=" + token + "&action=insert", 
  {word:word, translation:translation, description:description, list:list, wordbase:wordBase, force: true}, 
  function (data) {
    $("#modalDescription").removeClass("loading");
		$("#submitEditWord").removeClass("loading");
		$("#cancelEditWord").removeClass("loading");
    $("#submitInsertNewWord").removeClass("loading");
		$('#wordEditModal').modal('hide');
		var res = jQuery.parseJSON(data);
		if (res.status == "OK") {
      $("#word").val("");
      $("#translation").val("");
      $("#description").val("");
      jumpToPage(currentPage);
      
		}
	});
}

function submitDelete() {
	$("#submitDeleteWord").addClass("loading");
	$("#cancelDeleteWord").addClass("loading");
	$.post("API.php?token=" + token + "&action=deleteword", {id:wordsList[wordDeleteID].ID}, function (data) {
		$("#submitDeleteWord").removeClass("loading");
		$("#cancelDeleteWord").removeClass("loading");
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
      showToast('Share List','List Successfully shared', 'success', 0);
    } else {
      $('#shareListMessage').html(res.status);
      $('#shareListMessage').removeClass('hidden');
    }
  });
  
}

function beutifyInput(obj) {
  obj.val(distinctStringList(obj.val()));
}

function editCheckboxState(ch) {
  if (ch.checked) {
    insertDuplicateWord();
    $('#submitEditWord').css('display', 'none');
    $('#submitInsertNewWord').css('display', 'inline');
  } else {
    editDuplicateWord();
    $('#submitEditWord').css('display', 'inline');
    $('#submitInsertNewWord').css('display', 'none');
  }
}

function editDuplicateWord() {
  var word = $("#word").val().trim();
  var wordBase = word.toLowerCase().split(' ').join('').split('+').join('').split('/').join('');
  var translation = $("#translation").val().trim();
  var description = $("#description").val().trim();
  $('#editword').val(dupWord.Word);
  $('#edittranslation').val(distinctStringList(translation + "," + dupWord.Translation));
  $('#editdescription').val(distinctStringList(description + "," + dupWord.Description)); 
}

function insertDuplicateWord() {
  var word = $("#word").val().trim();
  var wordBase = word.toLowerCase().split(' ').join('').split('+').join('').split('/').join('');
  var translation = $("#translation").val().trim();
  var description = $("#description").val().trim();
  $('#editword').val(word);
  $('#edittranslation').val(distinctStringList(translation));
  $('#editdescription').val(distinctStringList(description)); 
}