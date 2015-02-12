<?php
/* 
   functions that mostly serve as utility
   current functions:
   filter_html_tags();
*/

// Reads in a string containing html entities, filter out
// potentially harmful tags such as script;
function filter_html_tags($html_str)
{
  $harm_tags = array("script", "embed", "link", 
                     "listing", "meta", "noscript", 
                     "object", "plaintext", "xmp");

  for ($i=0; $i<sizeof($harm_tags); $i++)
  {
    // the regex for matching the tags and their children
    // '-' appearing in the front and the end is the delimeter
    $match = '-<' . $harm_tags[$i] . '[^>]*>';
    if ($harm_tags[$i] != "meta" && $harm_tags[$i] != "link")
    {
      $match .= '.*?</' . $harm_tags[$i] . '>?-i';
    } else {
      $match .= "-i"; // adding ending delimeter
    }
    $html_str = preg_replace($match, "WARNING", $html_str);
  }
  
  return $html_str;
}

function make_id($prefix, $tablename, $id_column, $con) {
  $result = select_from($tablename, "`$id_column`", "", $con);
  $num_rows = $result->num_rows;
  $id = $prefix . '_' . $num_rows;
  $id = ensure_unique_id($id, $tablename, $id_column, $con);
  return $id;
}

// Returns a unique id (PREFIX_nnn) in the specified table, given
// an id that 'might' make this id unique already;
function ensure_unique_id($id, $tablename, $id_column, $con)
{
  $id_arr=break_id($id);
  $result=select_from($tablename, "`$id_column`", 
                      "WHERE `$id_column` = '$id'", $con);

  while($result->num_rows != 0)
  {
    $id_arr['number']=$id_arr['number'] + 1;
    $id=$id_arr['prefix'] . '_' . $id_arr['number'];

    // :: This method should be improved -- queries shouldn't
    // be executed that many of times
    $result=select_from($tablename, "`$id_column`", 
                        "WHERE `$id_column` = '$id'", $con);
  }
  return $id;
}

// utility function for ensure_unique_id
// Returns an array with the prefix and number of an id, 
// expected in this format: PREFIX_nnn, where nnn is a number
function break_id($id)
{
  $id_split=preg_split("/_/", $id);
  $number = intval($id_split[1]);
  $result=array(
    'prefix' => $id_split[0],
    'number' => $number
  );
  return $result;
}
?>