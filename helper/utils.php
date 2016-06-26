<?PHP
function arrayTypeInt($l) {
  $ar = array();
  for ($i = 0; $i < count($l); $i++) {
    array_push($ar, intval($l[$i]));
  }
  return $ar;
}

function forceArray($l) {
  if (! is_array($l)) {
    $list = $l;
    $l = array();
    if(! is_null($list)) {
      $l[0] = $list;
    }
  }
  return $l;
}

function listMarksBinds($l){
  $lmb = array();
  $l = forceArray($l);
  $listMarks = array();
  for ($i = 0; $i < count($l); $i++) {
    array_push($listMarks, "?");
  }
  
  $lmb["marks"] = implode(",", $listMarks);
  $lmb["binds"] = str_repeat("i", count($l));
  
  return $lmb;
}

?>