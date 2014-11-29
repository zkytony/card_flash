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

?>