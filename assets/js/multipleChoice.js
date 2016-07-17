var multipleChoiceList = [];
var listList = [];
var wordMainDiv = '<i class="book icon"></i>';
var wordTranslationDiv = '<i class="translate icon"></i>';
var wordDescriptionDiv = '<i class="file text outline icon"></i>';
var firstL = 0;
var lastL = 50;
var answerChosen = false;

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
			inventory = [ {ID: 123, correct: true}, {ID: 123, correct: true}
			] --> keeping the track of which ones were answered correctly or not.
			*/
			var inventoryObj = {};
			inventoryObj["wordIndex"] = 0;
			multipleChoiceList = jQuery.parseJSON(data).list;
			localStorage.setItem("multipleChoiceList", JSON.stringify(multipleChoiceList));
			
      var invObj = {};
			for (var i = 0; i < multipleChoiceList.length; i++) {
        //var inv = {};
        //inv[multipleChoiceList[i].ID.toString()] = false;//multipleChoiceList[i].ID;
        //inv["correct"] = false;
				//(inventoryObj["inventory"]).push(multipleChoiceList[i].ID.toString());
        //inventoryObj["inventory"][multipleChoiceList[i].ID.toString()] = false;
        invObj[multipleChoiceList[i].ID.toString()] = false;
			}
      inventoryObj["inventory"] = invObj;
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
  if (localStorage.hasOwnProperty("multipleChoiceList") && localStorage.hasOwnProperty("multipleChoiceInventory")) {
    var pr = jQuery.parseJSON(localStorage.multipleChoiceInventory);
    if (pr.wordIndex < (Object.keys(pr.inventory).length)) {
      showWordModal(pr.wordIndex);	
    } else {
      $('#multipleChoiceModal').modal('hide');
      finishPractice();
    }
  }
}

function markPracticeListWord(wID, res) {
  if (localStorage.hasOwnProperty("multipleChoiceList") && localStorage.hasOwnProperty("multipleChoiceInventory")) {
    var pr = jQuery.parseJSON(localStorage.multipleChoiceInventory);
    pr.inventory[wID] = res;
    if (pr.wordIndex < (Object.keys(pr.inventory).length)) {
      pr.wordIndex++;
    } 
    localStorage.multipleChoiceInventory = JSON.stringify(pr);
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
  var answers = w.Answers;
  var question = w.Question;
  var step = w.Step;
  var wID = w.ID;
  var correct = w.Correct;
  var listID = w.ListID;
    
  $('#wordMain').html('<i class="book icon"></i>' + question.split('\\').join(''));
  var answerChoices = '';
  for (var i = 0; i < answers.length; i++) {    
    var entry = answers[i].split('\\').join('');
    answerChoices += '<button class="ui black basic fluid button" style="text-align:left;" onclick="answerClicked(' + wID+','+correct+','+i+')" id="answerButton' + i + '">' 
    + entry + '</button><p/>'
  }
  $('#multipleChoices').html(answerChoices);
  if (! $('#multipleChoiceModal').modal('is active')) $('#multipleChoiceModal').modal('show');
	$('#modalHeader').html('<i class="book icon"></i> ' + (n+1) + ' <i class="checkered flag icon"></i> ' + w.Step);
	answerChosen = false;
	$('#unfinishedPractice').css('display', 'block');
}

function finishPractice() {
	$('#unfinishedPractice').css('display', 'none');
	$('#startPractice').css('display', 'none');
	$('#practiceResult').css('display', 'block');
	var pr = jQuery.parseJSON(localStorage.multipleChoiceInventory);
  pr = pr.inventory;
	var correct = 0;
  var incorrect = 0;
	for (var property in pr) {
    if (pr.hasOwnProperty(property)) {
        if (pr[property]) {
          correct++;
        } else {
          incorrect++;
        }
    }
  }
	$('#correctInventory').html(correct);
	$('#incorrectInventory').html(incorrect);
	
}

function submitPracticeResult() {
	var pr = jQuery.parseJSON(localStorage.multipleChoiceInventory).inventory;
	var cr = [];
	var incr = [];
	for (var property in pr) {
    if (pr.hasOwnProperty(property)) {
        if (pr[property]) {
          cr.push(parseInt(property));
        } else {
          incr.push(parseInt(property));
        }
    }
  }
	console.log(cr, incr);
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


function answerClicked(wID, correct, clicked) {
  console.log(wID, correct, clicked);
  if (!answerChosen) {
    answerChosen = true;
    markPracticeListWord(wID, (correct == clicked));
    if (correct == clicked) {
      $('#answerButton' + correct).attr("class", "ui green fluid button");
    } else {
      $('#answerButton' + correct).attr("class", "ui green fluid button");
      $('#answerButton' + clicked).attr("class", "ui red fluid button");
    }
  }
}