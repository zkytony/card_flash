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

// Returns an id that is guaranteed to be unique in the specified
// table, given the name of the column that stores the id
// Note:
// 1. each id is expected in this format: PREFIXnnn, where
// nnn is a number
// 2. $id_column is expected to be in this format: "`col`"
function ensure_unique_id($id, $tablename, $id_column, $con)
{
  $id_arr=break_id($id);

  $result=select_from($tablename, $id_column, "", $con);
  while($row=mysqli_fetch_assoc($result))
  {
    while($id == $row[$idcolumn])
    {
      $cur_id=break_id($row[$idcolumn]);
      $id_arr['number']=$cur_id['number']++;

      // put them together
      $id=$id_arr['prefix'] . $id_arr['number'];
    }
  }
  return $id;
}

// utility function for ensure_unique_id
// Returns an array with the prefix and number of an id, 
// expected in this format: PREFIXnnn, where nnn is a number
function break_id($id)
{
  $result=array();
  for ($i=0; $i<strlen($id); $i++)
  {
    if (!is_int($id[$i]))
    {
      $result['prefix']+=$id[$i];
    } else {
      $result['number']+=$id[$i];
    }
  }
  $result['number']=intval($result['number']);
  return $result;
}
?>