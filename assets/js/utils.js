var push_apply = Function.apply.bind([].push);
var slice_call = Function.call.bind([].slice);

Object.defineProperty(Array.prototype, "pushArrayMembers", {
    value: function() {
        for (var i = 0; i < arguments.length; i++) {
            var to_add = arguments[i];
            for (var n = 0; n < to_add.length; n+=300) {
                push_apply(this, slice_call(to_add, n, n+300));
            }
        }
    }
});

function distinctStringList(wordList) {
  if (wordList.length == 0) return wordList;
  var list = wordList.trim().split(/\s\s+/).join(' ').split(/(,|;)\s*/).join('').split(/,|;/); //Remove extra spaces
  var distinctWordString = "";
  var lastChecked = "";
  var i = 0;
  list.forEach(function(item,index,arr){
    arr[index] = item.trim();
  });
  var i = 0;
  for (; i < list.length; i++) {
    for (var j = i+1; j < list.length;) {
      if (list[i].toLowerCase() == list[j].toLowerCase()) {
        list.splice(j,1);
      } else {
        j++;
      }
    }
    distinctWordString += list[i] + ', ';
  }
  distinctWordString = distinctWordString.substring (0, distinctWordString.length - 2);
  
  return distinctWordString;
}

function showToast(heading, message, type, duration) {
  if (duration == -1) {
    duration = false;
  } else if (duration == 0) {
    duration = 3000;
  }
  $.toast({
    heading: heading,
    text: message,
    icon: type,
    loader: false,        // Change it to false to disable loader
    loaderBg: '#9EC600',  // To change the background
    hideAfter: duration,
    position: 'top-right'
  })
}

/*
array1.pushArrayMembers(array2, array3);
*/