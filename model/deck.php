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
  // $circleid is not NULL if this deck is related to a circle
  // Checks if the user already has a deck with same title
  // Returns the deckid of the added deck
  public static function add($title, $tags, $userid, $open, $circleid, $con) {
    if (!Deck::deck_exists($title, $userid, $con)) {
      // For the sake of activity, we want to keep time consistent. So we will use PHP date() to get current time, and
      // use MYSQL's STR_TO_DATE() to convert it to MySQL datetime format
      $datetime = date("H:i:s,m-d-Y"); // the format is specified in activity.php:add_activity()
      
      $result = select_from('decks',"`deckid`","",$con);
      $num_rows = $result->num_rows;
      $deckid = 'deck_' . $num_rows;
      $deckid = ensure_unique_id($deckid, "decks", "deckid", $con);

      $columns = "`deckid`,`title`,`userid`,`create_time`,`deleted`,`open`,`subscribers`";
      $values = "'$deckid','$title','$userid',STR_TO_DATE(\"{$datetime}\", \"%H:%i:%s,%m-%d-%Y\"), '0', '$open', '0'";
      insert_into('decks', $columns, $values, $con);

      // add the tags to 'tags' table:
      Tag::add($tags, $deckid, $con);

      // Add user creates / deletes a deck activity
      $type = 1;
      $data = array(
	'userid' => $userid,
	'deckid' => $deckid,
	'time' => $datetime,
	'circleid' => NULL
      );
      $data['newdeck']['new'] = '1';
      Activity::add_activity($type, $data, $con);
      
      return $deckid;
    } else {
      return NULL;
    }
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

  // Returns the userid of the owner of the deck
  // Returns NULL if the deck does not exist
  public static function owner_id($deckid, $con) {
    $result = select_from("decks", "*", "WHERE `deckid` = '$deckid'", $con);
    while ($row = mysqli_fetch_assoc($result)) {
      return $row['userid'];
    }
    return NULL;
  }

  // Returns true if this deck is open to public
  public static function is_open($deckid, $con) {
    $result = select_from("decks", "`open`", "WHERE `deckid` = '$deckid'", $con);    
    while ($row = mysqli_fetch_assoc($result)) {
      return $row['open'];
    }
    return false;
  }

  // given a deck title, and a userid, check if this the deck exists
  public static function deck_exists($title, $userid, $con) {
    $result = select_from("decks", "`deckid`", "WHERE `title` = '$title' AND `userid` = '$userid'", $con);
    while ($row = mysqli_fetch_assoc($result)) {
      return true;
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
  
  // returns the number of subscribers to a specified deck
  public static function num_subscribers($deckid, $con) {
    $num = 0;
    $result = select_from("decks", "`subscribers`", "WHERE `deckid` = '$deckid'", $con);
    while ($row = mysqli_fetch_assoc($result)) {
      $num = $row['subscribers'];
    }
    return $num;
  }

  // Given deckid, folder name, and userid, add the deck to
  // that specific folder. If the name+userid combination does not exist
  // in the table, then we are creating a new folder.
  // Then it will replace the value of `folderid` to a new folderid
  // Returns true if we create a new folder; false if doesn't
  public static function add_to_folder($deckid, $folder_name, $userid, $con) {
    $folderid = "";
    $created_new = false;
    if (!Deck::folder_exists_for_user($folder_name, $userid, $con)) {
      // folder does not exists, create a new one
      $folderid = make_id("folder", "folders", "folderid", $con);
      $columns = "`folderid`, `name`, `userid`";
      $values = "'$folderid', '$folder_name', '$userid'";
      insert_into("folders", $columns, $values, $con);
      $created_new = true;
    } else {
      // folder already exists. Get the folderid
      $folderid = Deck::get_folderid($folder_name, $userid, $con);
    }
    
    // update decks table, change the `folderid` column to current folderid
    update_table("decks", array("`folderid`"), array("'$folderid'"), "WHERE `deckid` = '$deckid'", $con);
    return $created_new;
  }

  // Deletes the deck from a folder
  // Returns true if the deletion is successful; false otherwise -- probably the deck is not in that specific folder
  public static function delete_from_folder($deckid, $folder_name, $userid, $con) {
    $folderid = Deck::get_folderid($folder_name, $userid, $con);
    if (!is_null($folderid)) {
      // update decks table, change the `folderid` column to NULL
      update_table("decks", array("`folderid`"), array("NULL"), "WHERE `deckid` = '$deckid'", $con);
      return true;
    }
    return false;
  }

  // Creates a folder; Returns the folderid
  // Returns NULL if the creation faild - the folder already exists for the user
  public static function add_folder($folder_name, $userid, $con) {
    if (!Deck::folder_exists_for_user($folder_name, $userid, $con)) {
      $folderid = make_id("folder", "folders", "folderid", $con);
      $columns = "`folderid`, `name`, `userid`";
      $values = "'$folderid', '$folder_name', '$userid'";
      insert_into("folders", $columns, $values, $con);
      return $folderid;
    }
    return NULL;
  }

  // Deletes a folder; Returns true if successful; false otherwise -- probably the folder does not exist
  public static function delete_folder($folder_name, $userid, $con) {
    $folderid = Deck::get_folderid($folder_name, $userid, $con);
    if (!is_null($folderid)) {
      delete_from("folders", "WHERE `folderid` = '$folderid'", "", $con);
      
      // delete all `folderid` value for the decks that were in this folder
      update_table("decks", array("`folderid`"), array("NULL"), "WHERE `folderid` = '$folderid'", $con);
      return true;
    }
    return false;
  }

  // Given folder name, userid, determine if the user has a folder with this name
  // Returns true if he does have a folder with the given name
  public static function folder_exists_for_user($folder_name, $userid, $con) {
    $result = select_from("folders", "`folderid`", "WHERE `name` = '$folder_name' AND `userid` = '$userid'", $con);
    return $result->num_rows > 0;
  }

  public static function get_folderid($folder_name, $userid, $con) {
    $result = select_from("folders", "`folderid`", "WHERE `name` = '$folder_name' AND `userid` = '$userid'", $con);
    while ($row = mysqli_fetch_assoc($result)) {
      return $row['folderid'];
    }
    return NULL;
  }
  
  public static function folderid_for_deck($deckid, $con) {
    $result = select_from("decks", "`folderid`", "WHERE `deckid` = '$deckid'", $con);
    while ($row = mysqli_fetch_assoc($result)) {
      return $row['folderid'];
    }
  }

  // Returns an array storing the information about a deck given the deckid
  // Information contains:
  // title, create_time, open, subscribers
  // Returns NULL if not found any information given the userid
  public static function deck_info($deckid, $con) {
    $result = select_from("decks", 
			  "`title`, `create_time`, `open`, `subscribers`",
			  "WHERE `deckid` = '$deckid'", $con);
    while ($row = mysqli_fetch_assoc($result)) {
      return $row;
    }
    return NULL;
  }

  // Likes a deck - increments the count of likes in `like` column
  // $circleid is not NULL if this comment is related to a circle
  public static function like($userid, $deckid, $circleid, $con) {
    // For the sake of activity, we want to keep time consistent. So we will use PHP date() to get current time, and
    // use MYSQL's STR_TO_DATE() to convert it to MySQL datetime format
    $datetime = date("H:i:s,m-d-Y"); // the format is specified in activity.php:add_activity()

    update_table("decks", array("`like`"), array("`like`+1"), "WHERE `deckid` = '$deckid'", $con);

    // Add user likes activity (11)
    $act_type = 11;
    $data = array(
      'userid' => $userid,
      'time' => $datetime,
      'circleid' => $circleid
    );
    $data['likes']['type'] = 1; // type 1 for liking a deck
    $data['likes']['targetid'] = $deckid;
    Activity::add_activity($act_type, $data, $con);
  }

  // Unlikes a deck - increments the count of likes in `like` column
  // Assume that a person cannot unlike if he has not yet liked
  public static function unlike($userid, $deckid, $con) {
    update_table("decks", array("`like`"), array("`like`-1"), "WHERE `deckid` = '$deckid'", $con);
  }

  // Increment the number of flips of a particular deck by the given $number
  public static function add_flips($deckid, $number, $con) {
    update_table("decks", array("`flips`"), array("`flips`+$number"), "WHERE `deckid` = '$deckid'", $con);   b 
  }

  // Increment the number of views of a particular deck by the given $number
  public static function add_views($deckid, $number, $con) {
    update_table("decks", array("`views`"), array("`views`+$number"), "WHERE `deckid` = '$deckid'", $con);   b     
  }

  // Returns the number of flip counts
  // Returns NULL if the deckid does not represent any existing deck
  public static function flip_count($deckid, $con) {
    $result = select_from("decks", "`flips`", "WHERE `deckid` = '$deckid'", $con);
    while ($row = mysqli_fetch_assoc($result)) {
      return $row['flips'];
    }
    return NULL;
  }
  
  // Returns the number of view counts
  // Returns NULL if the deckid does not represent any existing deck
  public static function view_count($deckid, $con) {
    $result = select_from("decks", "`flips`", "WHERE `deckid` = '$deckid'", $con);
    while ($row = mysqli_fetch_assoc($result)) {
      return $row['views'];
    }
    return NULL;
  }

  // Function to call when a user views a card in a deck.
  // This will add a activity to activity_user_visit_deck table
  // For the sake of speed, it is suggested to call this function only
  // when the user exits the page or something like that.
  public static function view($userid, $deckid, $cardid, $con) {

    // For the sake of activity, we want to keep time consistent. So we will use PHP date() to get current time, and
    // use MYSQL's STR_TO_DATE() to convert it to MySQL datetime format
    $datetime = date("H:i:s,m-d-Y"); // the format is specified in activity.php:add_activity()

    Deck::add_views($deckid, 1, $con);

    // Add user view deck activity (12)
    $act_type = 12;
    $data = array(
      'userid' => $userid,
      'deckid' => $deckid,
      'cardid' => $cardid,
      'time' => $datetime,
      'circleid' => $circleid
    );
    Activity::add_activity($act_type, $data, $con);
  }
}
?>
