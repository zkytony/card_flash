<?php

require_once "../database.php";
require_once "../functions.php";

/* 
   ----------------
   The Tag class
   ----------------
 */
class Tag
{
  private $tagid;
  private $info;
  private $exist;
  
  function __construct($tagid, $con) {
    $result = select_from("tags", "*", "WHERE `tagid` = '$tagid'", $con);
    $this->exist = $result->num_rows == 1;
    if ($this->exist) {
      $this->tagid = $tagid;
      $this->info = array();
      while ($rows = mysqli_fetch_assoc($result)) {
        $this->info = $rows; // In PHP, arrays are assigned in copy
        break; // only can be 1 match
      }
    }  
  }

  // Adds an array of tags of a deck to 'tags' table
  public static function add($tags, $deckid, $con) {
    $result = select_from('tags', '*', "", $con);
    $count = $result->num_rows;
    for ($i = 0; $i < sizeof($tags); $i++)
    {
      $tagid = "tag_" . $count;
      $tagid = ensure_unique_id($tagid, "tags", "tagid", $con);
      $count++;
      
      // insert this tag-deckid relationship to the table
      $columns="`tagid`, `tag`, `deckid`, `deleted`";
      $values="'$tagid', '$tags[$i]', '$deckid', '0'";
      insert_into('tags', $columns, $values, $con);
    }
  }

  public static function get_tags($deckid, $active, $con) {
    $restrict_str = "WHERE `deckid`='$deckid' ";
    if ($active) {
      $restrict_str .= "AND `deleted` = '0'";
    }
    $result = select_from("tags", "`tag`", $restrict_str, $con);
    
    $tags = array();
    while ($row = mysqli_fetch_assoc($result)) {
      $tags[] = $row['tag'];
    }
    return $tags;
  }

  public static function delete($tagid, $con) {
    $restrict_str = "WHERE `tagid` = '$tagid'";
    update_table("tags", array("`deleted`"), array("'1'"), 
                 $restrict_str, $con);
    return true;
  }

  // Given a tag in string, returns an array of deckids
  // that has the tag
  public static function get_decks_with_tag($tag, $con) {
    $restrict_str = "WHERE `tag` = '$tag' ORDER BY `deckid`";
    $result = select_from("tags", "*", $restrict_str, $con);
    $deckids = array();
    while($row = mysqli_fetch_assoc($result)) {
      $deckids[] = $row['deckid'];
    }
    return $deckids;
  }
}

?>