<?PHP
/*
  Parameters:
  Connection
  Query
  Bindings
  Arguments
updateRows($conn, $sql, $bindings, $args)  
*/
function fetchRows(){
  error_reporting(E_ALL+E_NOTICE);
  $args = func_get_args();
  $conn = array_shift($args);
  $sql = array_shift($args);
  
  // Keep the column types for bind_param.
  // $colTypes = array_shift($args);

  // Column types were originally passed here as a second
  // argument, and stored in the statement object, I suppose.
  if (!$query = $conn->prepare($sql)){ //, $colTypes)) {
    die('Please check your sql statement : unable to prepare');
  }
  if (count($args)){
    // Just a quick hack to pass references in order to
    // avoid errors.
    foreach ($args as &$v) {
      $v = &$v;
    }
    // Replace the bindParam function of the original
    // abstraction layer.
    call_user_func_array(array($query,'bind_param'), $args); //'bindParam'), $args);
  }

  $query->execute();

  $meta = $query->result_metadata();
  while ($field = $meta->fetch_field()) {
    $params[] = &$row[$field->name];
  }
  
  call_user_func_array(array($query, 'bind_result'), $params);

  while ($query->fetch()) {
    
    $temp = array();
    foreach($row as $key => $val) {
      $temp[$key] = $val;
    } 
    $result[] = $temp;
  }

  $meta->free();
  $query->close(); 
  //self::close_db_conn(); 
  return $result;
}
/*Used for INSERT, UPDATE, DELETE*/
function modifyRows(){
  error_reporting(E_ALL+E_NOTICE);
  $args = func_get_args();
  $conn = array_shift($args);
  $sql = array_shift($args);
  
 
  if (!$query = $conn->prepare($sql)){ //, $colTypes)) {
    return false;
  }
  if (count($args)){
    // Just a quick hack to pass references in order to
    // avoid errors.
    foreach ($args as &$v) {
      $v = &$v;
    }
    // Replace the bindParam function of the original
    // abstraction layer.
    call_user_func_array(array($query,'bind_param'), $args); //'bindParam'), $args);
  }

  if (!$query->execute()) {
    $query->close(); 
    return false;
  }

  $query->close(); 
  //self::close_db_conn(); 
  return true;
}

?>