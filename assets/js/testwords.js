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