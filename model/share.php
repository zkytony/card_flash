<?php

require_once "../database.php";
require_once "../functions.php";

/*
   ---------------
   The Share class
   ---------------
 */
class Share
{
  private $shareid;
  private $info;
  private $exist;
  
  function __construct($shareid, $con) {
    $result = select_from("shares", "*", "WHERE `shareid` = '$shareid'", $con);
    $this->exist = $result->num_rows == 1;
    if ($this->exist) {
      $this->shareid = $shareid;
      $this->info = array();
      while ($rows = mysqli_fetch_assoc($result)) {
        $this->info = $rows; // In PHP, arrays are assigned in copy
        break; // only can be 1 match
      }
    }  
  }

  // Returns the shareid given the deckid and userid;
  // Returns NULL if no such combination
  public static function get_shareid($deckid, $userid, $con) {
    $result = select_from("shares", "*", 
                          "WHERE `userid` = '$userid' AND `deckid` = '$deckid'",
                          $con);
    while($row = mysqli_fetch_assoc($result)) {
      return $row['shareid']; // should be only one match
    }
    // no row
    return NULL;
  }
  
  // Returns an integer representing the sharing status between 
  // the given deck and user
  // 0: not shared to
  // 1: shared as visitor
  // 2: shared as editor
  // Note that if the user is the owner of the deck, 0 will be
  // returned
  public static function check_status($deckid, $userid, $con) {
    $result = select_from("shares", "*", 
                          "WHERE `userid` = '$userid' AND `deckid` = '$deckid'",
                          $con);
    while($row = mysqli_fetch_assoc($result)) {
      return $row['type']; // should be only one match
    }
    // no row:
    return 0;
  }

  // $circleid is not NULL if this deck is related to a circle
  // $type's values
  // 1: shared as visitor
  // 2: shared as editor
  // Only process if $type is valid
  // Returns shareid if successful; 
  // Returns NULL if unsuccessful; reason can be: already shared,
  // or trying to share to the owner
  // in the same type; Updates the type of sharing if it is currently
  // different
  public static function share_to($deckid, $from_userid, $to_userid, $type, $circleid, $con) {
    try {
      if ($type == 1 or $type == 2) {
        $status = Share::check_status($deckid, $to_userid, $con);
        if (!Deck::is_owner_of($deckid, $to_userid, $con)) {

	  // For the sake of activity, we want to keep time consistent. So we will use PHP date() to get current time, and
	  // use MYSQL's STR_TO_DATE() to convert it to MySQL datetime format
	  $datetime = date("H:i:s,m-d-Y"); // the format is specified in activity.php:add_activity()

	  // Add the deck shared activity (4)
	  $type = 4;
	  $data = array(
	    'deckid' => $deckid,
	    'time' => $datetime
	  );
	  if (!is_null($circleid)) $data['circleid'] = $circleid;
	  if ($status == 0 || $status != $type) {
	    $data['deckshare']['from_user'] = $from_user;
	    $data['deckshare']['to_user'] = $to_user;
	    $data['deckshare']['sharing'] = true;
	  }

          if ($status == 0) {
            $result = select_from("shares", "*", "", $con);
            $shareid = "share_" . $result->num_rows;
            $shareid = ensure_unique_id($shareid, "shares", "shareid", $con);

            insert_into("shares", "`shareid`, `deckid`, `userid`, `type`",
                        "'$shareid', '$deckid', '$to_userid', '$type'", $con);
            return $shareid;
          } else if ($status != $type) {
            update_table("shares", array("`type`"), array("'$type'"),
                         "WHERE `userid` = '$to_userid' AND `deckid` = '$deckid'",
                         $con);
            return Share::get_shareid($deckid, $to_userid, $con);
          } else {
            return NULL;
          }
        } else {
          return NULL;
        }
      } else {
        throw 99;
      }
    } catch (int $exp) {
      switch ($exp) {
        case 99:
          echo "Error $exp: Sorry. Wrong type $type.";
          break;
      }
    }
  }

  // Deletes the deckid and userid combination
  // Set $deckid to empty string if want to clear all deck sharing
  // to the user with userid
  // Set $userid to empty string if want to clear all sharing of
  // the deck;
  // Does not allow both empty
  public static function unshare($deckid, $userid, $con) {
    try {
      if ($deckid == "" and $userid == "") throw 88;
      
      $restrict_str = "";
      if ($deckid == "") {
        $restrict_str = "WHERE `userid` = '$userid'";
      } else if ($userid == "") {
        $restrict_str = "WHERE `deckid` = '$deckid'";
      } else {
        $restrict_str = "WHERE `userid` = '$userid' AND `deckid` = '$deckid'";
      }
        delete_from("shares", $restrict_str, "", $con);
    } catch (int $exp) {
      echo "Error $exp: Cannot have both empty";
    }
  }

  // Returns an array of userids that a deck shares to with type
  public static function shared_users($deckid, $type, $con) {
    $result = select_from("shares", "*", "WHERE `deckid` = '$deckid'", $con);
    $userids = array();
    while ($row = mysqli_fetch_assoc($result)) {
      $userids[] = $row['userid'];
    }
    return $userids;
  }

  // Returns an array of deckids that a user is shared with type
  public static function shared_decks($userid, $type, $con) {
    $restrict_str = "WHERE `userid` = '$userid'";
    if ($type == 1 or $type == 2) {
      $restrict_str .= " AND `type` = '$type'";
    }
    $result = select_from("shares", "*", $restrict_str, $con);
    $deckids = array();
    while ($row = mysqli_fetch_assoc($result)) {
      $deckids[] = $row['deckid'];
    }
    return $deckids;
  }
}
?>
