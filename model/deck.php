<?php

require_once "../database.php";
require_once "../functions.php";

/* 
   ----------------
   The Deck class
   ----------------
 */
class Deck
{
  private $deckid;
  private $info;
  private $exist;
  
  function __construct($deckid, $con) {
    $result = select_from("decks", "*", "WHERE `deckid` = '$deckid'", $con);
    $this->exist = $result->num_rows == 1;
    if ($this->exist) {
      $this->deckid = $deckid;
      $this->info = array();
      while ($rows = mysqli_fetch_assoc($result)) {
        $this->info = $rows; // In PHP, arrays are assigned in copy
        break; // only can be 1 match
      }
    }  
  }

  public function get_id() {
    return $this->deckid;
  }
  
  public function get_info() {
    return $this->info;
  }

  // returns an array of tags' of this deck
  public function get_tags($active, $con) {
    return Tag::get_tags($this->deckid, $active, $con);
  }

  // returns an array of Card objects associated with this deck
  public function get_cards($active, $con) {
    return Card::get_cards($this->deckid, $active, $con);
  }

  // static function for adding a deck
  // $tags is an array of tags of the deck
  // Returns the deckid of the added deck
  public static function add($title, $tags, $userid, $con) {
    $result = select_from('decks',"`deckid`","",$con);
    $num_rows = $result->num_rows;
    $deckid = 'deck_' . $num_rows;
    $deckid = ensure_unique_id($deckid, "decks", "deckid", $con);

    $columns = "`deckid`,`title`,`userid`,`create_time`,`deleted`";
    $values = "'$deckid','$title','$userid',NOW(), '0'";
    insert_into('decks', $columns, $values, $con);

    // add the tags to 'tags' table:
    Tag::add($tags, $deckid, $con);
    return $deckid;
  }

  // Edits a card with given information
  // tags is expected to be an array of string
  public static function edit($title, $tags, $deckid, $con) {
    update_table("decks", array("`title`"),
                 array("'$title'"), 
                 "WHERE `deckid` = '$deckid'", $con);

    // delete all current tags
    delete_from("tags", "WHERE `deckid` = '$deckid'", '', $con);
    // add the new tags wanted
    Tag::add($tags, $deckid, $con);
  }

  // Returns an array of Deck objects that the user with userid has
  // If $acitve is true, then this returns only decks that are not
  // marked as delelted
  public static function get_decks_of($userid, $active, $con) {
    $restrict_str = "WHERE `userid`='$userid' ";
    if ($active) {
      $restrict_str .= "AND `deleted` = '0'";
    }
    $restrict_str .= "ORDER BY `create_time`";
    $result = select_from("decks", "`deckid`", $restrict_str, $con);
    
    $decks = array();
    while ($row = mysqli_fetch_assoc($result)) {
      $decks[] = new Deck($row['deckid'], $con);
    }
    return $decks;
  }
  
  // Delete the deck. Mark it as deleted by setting the corresponding
  // value in the `deleted` column; The associated Cards and Tags
  // will be deleted as well
  public static function delete($deckid, $con) {
    // first mark all cards in this deck as deleted
    $result=select_from("cards", "`cardid`", 
                        "WHERE `deckid` = '$deckid'", $con);
    while ($row=mysqli_fetch_assoc($result))
    {
      Card::delete($row['cardid'], $con);
    }

    // then, mark all tags of this deck as deleted
    $result=select_from("tags", "`tagid`",
                        "WHERE `deckid` = '$deckid'", $con);
    while ($row=mysqli_fetch_assoc($result))
    {
      Tag::delete($row['tagid'], $con);
    }
    
    // then, mark the deck as deleted
    $restrict_str="WHERE `deckid` = '$deckid'";
    update_table("decks", array("`deleted`"), array("'1'"), 
                 $restrict_str, $con);
    return true;
  }

  // Because of foreign keys, the cards and tags will be
  // also deleted
  public static function delete_completely($deckid, $con) {
    delete_from("decks", "WHERE `deckid` = '$deckid'", '', $con);
  }

  // Returns true if $userid is the owner of $deckid
  public static function is_owner_of($deckid, $userid, $con) {
    $result = select_from("decks", "*", "WHERE `deckid` = '$deckid'", $con);
    while ($row = mysqli_fetch_assoc($result)) {
      if ($row['userid'] == $userid) {
        return true;
      }
    }
    return false;
  }

  // Returns true if this deck is open to public
  public static function is_open($deckid, $con) {
    $result = select_from("decks", "`open`", "WHERE `deckid` = '$deckid'", $con);    
    while ($row = mysqli_fetch_assoc($result)) {
      return $row['open'];
    }
    return false;
  }

  // adds one to the number of subscribers this deck has
  public static function subscriber_add_one($deckid, $con) {
    $restrict_str="WHERE `deckid`='$deckid'";
    update_table("decks", array("`subscribers`"), array("`subscribers`+1"), $restrict_str, $con);
  }

  // subtracts one to the number of subscriberrs this deck has
  public static function subscriber_subtract_one($deckid, $con) {
    $restrict_str="WHERE `deckid`='$deckid'";
    update_table("decks", array("`subscribers`"), array("`subscribers`-1"), $restrict_str, $con);
  }
}
?>