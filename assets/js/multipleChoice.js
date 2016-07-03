var multipleChoiceList = [];
var listList = [];
var wordMainDiv = '<i class="book icon"></i>';
var wordTranslationDiv = '<i class="translate icon"></i>';
var wordDescriptionDiv = '<i class="file text outline icon"></i>';
var firstL = 0;
var lastL = 50;

if (localStorage.hasOwnProperty("multipleChoiceList") && localStorage.hasOwnProperty("multipleChoiceInventory")) {
	multipleChoiceList = jQuery.parseJSON(localStorage.multipleChoiceList);
	$('#unfinishedPractice').css('display', 'block');
} else {
	$('#unfinishedPractice').css('display', 'none');
}

function decreaseWordNumber() {
	if (parseInt($('#wordnumber').val()) > 1) $('#wordnumber').val(parseInt($('#wordnumber').val()) - 1);
}

function increaseWordNumber() {
	if (parseInt($('#wordnumber').val()) < 50) $('#wordnumber').val(parseInt($('#wordnumber').val()) + 1);
}

function checkWordNumber() {
	var v = $('#wordnumber').val();
	var fv = "";
	for (var i = 0; i < v.length; i++) {
		if (!isNaN(parseInt(v[i]))) fv += v[i];
	}
	var vi = parseInt(fv);
	if (vi < 1) 
		vi = 1;
	else if (vi > 50) 
		vi = 50;
	
	$('#wordnumber').val(vi)
}

$(document).ready(function() {
  

  $.get("/API.php?token=" + token + "&action=listlist&first=" + firstL + "&last=" + lastL, function (data) {
    var res = jQuery.parseJSON(data);
    listList = res.results;
    for (var i = 0; i < listList.length; i++) {
      $('#listItems').append($("<div></div>").
        attr("data-value", listList[i].value).
        text(listList[i].name.split('\\').join('')).addClass("item"));
    }
    $('#listSelection').dropdown();
    $('#clearListSelection') .on('click', function() {
      $('#listSelection').dropdown('restore defaults');
    });
    
  });

	$('#multipleChoiceModal').modal({closable:true}).modal('setting', 'transition', 'horizontal flip');
	$("#start").click(function(){
		$("#start").addClass("loading");
    var lists = $('#lists').val().split(',');
		//$.get("/API.php?token=" + token + "&action=multiplechoicepracticelist&count=" + $('#wordnumber').val(), function (data) {
    $.post("/API.php?token=" + token + "&action=multiplechoicepracticelist&count=" + $('#wordnumber').val() + "&choices="+ $('#multiChoices').val(), {lists:lists}, function (data) {
			$("#start").removeClass("loading");
      
      
			var inventory = "";
			/*
			wordIndex = the index of the last word checked from multipleChoiceList
			inventory = [
				0, 1,...
			] --> keeping the track of which ones were answered correctly or not.
			*/
			var inventoryObj = {};
			inventoryObj["wordIndex"] = 0;
			multipleChoiceList = jQuery.parseJSON(data).list;
			localStorage.setItem("multipleChoiceList", JSON.stringify(multipleChoiceList));
			inventoryObj["inventory"] = [];
			for (var i = 0; i < multipleChoiceList.length; i++) {
				inventoryObj["inventory"][i] = 0;
			}
			inventory = JSON.stringify(inventoryObj);
			localStorage.setItem("multipleChoiceInventory", inventory);
			
			showWordModal(0);
			
		});
	});
	
});

function checkWord() {
	$('#wordTranslation').css('visibility', 'visible');
	if ($('#wordDescription').html().length > '<i class="file text outline icon"></i>'.length ) $('#wordDescription').css('visibility', 'visible');
}

function nextWord() {
	if(markPracticeListWord(1)) {
		var ind = getCurrentWordIndex();
		showWordModal(ind);	
	} else {
		$('#multipleChoiceModal').modal('hide');
		finishPractice();
	}
}

function markPracticeListWord(res) {
	var pr = jQuery.parseJSON(localStorage.multipleChoiceInventory);
	pr.inventory[pr.wordIndex] = res;
	if (pr.wordIndex < (pr.inventory.length - 1)) {
		pr.wordIndex++;
		localStorage.multipleChoiceInventory = JSON.stringify(pr);
		return true;
	} else {
		localStorage.multipleChoiceInventory = JSON.stringify(pr);
		return false;
	}
}


function getCurrentWordInfo() {
	return getWordInfo(getCurrentWordIndex());
}

function getWordInfo(n) {
	if (localStorage.hasOwnProperty("multipleChoiceList") && localStorage.hasOwnProperty("multipleChoiceInventory")) {
		return jQuery.parseJSON(localStorage.multipleChoiceList)[n];
	}	
}

function getCurrentWordIndex() {
	if (localStorage.hasOwnProperty("multipleChoiceList") && localStorage.hasOwnProperty("multipleChoiceInventory")) {
		return parseInt(jQuery.parseJSON(localStorage.multipleChoiceInventory).wordIndex);
	}	
}

function showWordModal(n) {
	var w = getWordInfo(n);
  console.log(w);
	$('#modalHeader').html('<i class="book icon"></i> ' + (n+1) + ' <i class="checkered flag icon"></i> ' + w.Step);
	//$('#modalHeader').transition('pulse');
	$('#wordMain').html('<i class="book icon"></i>' + w.Word.split('\\').join(''));
	$('#wordTranslation').html('<i class="translate icon"></i>' + w.Translation.split('\\').join('')).css('visibility', 'hidden');
	$('#wordDescription').html('<i class="file text outline icon"></i>' + w.Description.split('\\').join('')).css('visibility', 'hidden');
	if (! $('#multipleChoiceModal').modal('is active')) $('#multipleChoiceModal').modal('show');
	$('#unfinishedPractice').css('display', 'block');
}

function finishPractice() {
	$('#unfinishedPractice').css('display', 'none');
	$('#startPractice').css('display', 'none');
	$('#practiceResult').css('display', 'block');
	var pr = jQuery.parseJSON(localStorage.multipleChoiceInventory);
	var score = 0;
	for (var i = 0; i < pr.inventory.length; i++) {
		score += pr.inventory[i];
	}
	$('#correctInventory').html(score);
	$('#incorrectInventory').html((pr.inventory.length - score));
	
}

function submitPracticeResult() {
	var pr = jQuery.parseJSON(localStorage.multipleChoiceInventory);
	var w = jQuery.parseJSON(localStorage.multipleChoiceList);
	var cr = [];
	var incr = [];
	
	for (var i = 0; i < pr.inventory.length; i++) {
    
		if (pr.inventory[i] == 1) {
			cr.push(w[i].ID);
		} else {
			incr.push(w[i].ID);
		}
	}
	
	$('#submitPractice').addClass('loading');
	$.post("/API.php?token=" + token + "&action=submitpractice", {correct:cr, incorrect:incr},function (data) {
		$('#submitPractice').removeClass('loading');
		var res = jQuery.parseJSON(data);
		if (res.submit) {
			localStorage.removeItem("multipleChoiceList");
			localStorage.removeItem("multipleChoiceInventory");
			location.reload();
		} else {
			alert("Something went wrong! Please try again later.")
		}
	});
	
	
}

function continuePractice() {
	showWordModal (getCurrentWordIndex());
}