var practiceList = [];
var wordMainDiv = '<i class="book icon"></i>';
var wordTranslationDiv = '<i class="translate icon"></i>';
var wordDescriptionDiv = '<i class="file text outline icon"></i>';

if (localStorage.hasOwnProperty("practicelist")) {
	practiceList = jQuery.parseJSON(localStorage.practicelist);
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
	$('.ui.small.modal').modal({closable:true}).modal('setting', 'transition', 'horizontal flip');
	$("#start").click(function(){
		$("#start").addClass("loading");
		$.get("API.php?token=" + token + "&action=practicelist&count=" + $('#wordnumber').val(), function (data) {
			$("#start").removeClass("loading");
			var inventory = "";
			/*
			wordIndex = the index of the last word checked from practicelist
			inventory = [
				0, 1,...
			] --> keeping the track of which ones were answered correctly or not.
			*/
			var inventoryObj = {};
			inventoryObj["wordIndex"] = 0;
			practiceList = jQuery.parseJSON(data).practicelist;
			localStorage.setItem("practicelist", JSON.stringify(practiceList));
			inventoryObj["inventory"] = [];
			for (var i = 0; i < practiceList.length; i++) {
				inventoryObj["inventory"][i] = 0;
			}
			inventory = JSON.stringify(inventoryObj);
			localStorage.setItem("practicelistInventory", inventory);
			
			showWordModal(0);
			
		});
	});
	
});

function incorrectWord() {
	if(markPracticeListWord(0)) {
		var ind = getCurrentWordIndex();
		showWordModal(ind);	
	} else {
		$('.ui.small.modal').modal('hide');
		finishPractice();
	}
}

function correctWord() {
	if(markPracticeListWord(1)) {
		var ind = getCurrentWordIndex();
		showWordModal(ind);	
	} else {
		$('.ui.small.modal').modal('hide');
		finishPractice();
	}
}

function markPracticeListWord(res) {
	var pr = jQuery.parseJSON(localStorage.practicelistInventory);
	pr.inventory[pr.wordIndex] = res;
	if (pr.wordIndex < (pr.inventory.length - 1)) {
		pr.wordIndex++;
		localStorage.practicelistInventory = JSON.stringify(pr);
		return true;
	} else {
		localStorage.practicelistInventory = JSON.stringify(pr);
		return false;
	}
}

function checkWord() {
	$('#wordTranslation').css('visibility', 'visible');
	if ($('#wordDescription').html.length > '<i class="file text outline icon"></i>'.length ) $('#wordDescription').css('visibility', 'visible');
}

function getCurrentWordInfo() {
	return getWordInfo(getCurrentWordIndex());
}

function getWordInfo(n) {
	if (localStorage.hasOwnProperty("practicelist") && localStorage.hasOwnProperty("practicelistInventory")) {
		return jQuery.parseJSON(localStorage.practicelist)[n];
	}	
}

function getCurrentWordIndex() {
	if (localStorage.hasOwnProperty("practicelist") && localStorage.hasOwnProperty("practicelistInventory")) {
		return parseInt(jQuery.parseJSON(localStorage.practicelistInventory).wordIndex);
	}	
}

function showWordModal(n) {
	var w = getWordInfo(n);
	$('#modalHeader').html('<i class="book icon"></i> ' + (n+1) + ' <i class="checkered flag icon"></i> ' + w.Step);
	$('#modalHeader').transition('pulse');
	$('#wordMain').html('<i class="book icon"></i>' + w.Word);
	$('#wordTranslation').html('<i class="translate icon"></i>' + w.Translation).css('visibility', 'hidden');
	$('#wordDescription').html('<i class="file text outline icon"></i>' + w.Description).css('visibility', 'hidden');
	if (! $('.ui.small.modal').modal('is active')) $('.ui.small.modal').modal('show');
}

function finishPractice() {
	$('#startPractice').css('display', 'none');
	$('#practiceResult').css('display', 'block');
	var pr = jQuery.parseJSON(localStorage.practicelistInventory);
	var score = 0;
	for (var i = 0; i < pr.inventory.length; i++) {
		score += pr.inventory[i];
	}
	$('#correctInventory').html(score);
	$('#incorrectInventory').html((pr.inventory.length - score));
	
}

function submitPracticeResult() {
	var pr = jQuery.parseJSON(localStorage.practicelistInventory);
	var w = jQuery.parseJSON(localStorage.practicelist);
	var cr = "";
	var incr = "";
	
	for (var i = 0; i < pr.inventory.length; i++) {
		if (pr.inventory[i] == 1) {
			cr += "," + w[i].ID;
		} else {
			incr += "," + w[i].ID;
		}
	}
	if (cr.length > 0) cr = cr.substring(1, cr.length);
	if (incr.length > 0) incr = incr.substring(1, incr.length);
	$('#submitPractice').addClass('loading');
	$.post("API.php?token=" + token + "&action=submitpractice", {correct:cr, incorrect:incr},function (data) {
		$('#submitPractice').removeClass('loading');
		var res = jQuery.parseJSON(data);
		if (res.submit) {
			localStorage.removeItem("practicelist");
			localStorage.removeItem("practicelistInventory");
			window.location.href = 'insertword.html';
		} else {
			alert("Something went wrong! Please try again later.")
		}
	});
	
	
}