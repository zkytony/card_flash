<?php 

require_once "../database.php";
require_once "../functions.php";

/*
   ---------------
   The Favorite class
   ---------------
 */
class Favorite
{
  private $favid;
  private $info;
  private $exist;

  function __construct($favid, $con) {
    $result = select_from("favorites", "*", "WHERE `favid` = '$favid'", $con);
    $this->exist = $result->num_rows == 1;
    if ($this->exist) {
      $this->favid = $favid;
      $this->info = array();
      while ($rows = mysqli_fetch_assoc($result)) {
        $this->info = $rows; // In PHP, arrays are assigned in copy
        break; // only can be 1 match
      }
    }
  }

  public function get_id() {
    return $this->favid;
  }

  public static function get_favid($deckid, $userid, $con) {
    $result = select_from("favorites", "*", 
                          "WHERE `fav_userid` = '$userid' AND `deckid` = '$deckid'",
                          $con);
    while($row = mysqli_fetch_assoc($result)) {
      return $row['favid']; // should be only one match
    }
    // no row
    return NULL;
  }

  // Called when userid favorites to deckid
  // $circleid is not NULL if the deck to be favorited is related to a circle
  public static function favorite($deckid, $userid, $circleid, $con) {
    $favid = Favorite::get_favid($deckid, $userid, $con);
    if (!is_null($favid)) {
      return $favid;
    } else if (!Deck::is_owner_of($deckid, $userid, $con) and  Deck::is_open($deckid, $con)) {

      // For the sake of activity, we want to keep time consistent. So we will use PHP date() to get current time, and
      // use MYSQL's STR_TO_DATE() to convert it to MySQL datetime format
      $datetime = date("H:i:s,m-d-Y"); // the format is specified in activity.php:add_activity()

      $result = select_from("favorites", "*", "", $con);
      $favid = "fav_" . $result->num_rows;
      $favid = ensure_unique_id($favid, "favorites", "favid", $con);

      insert_into("favorites",
                  "`favid`, `deckid`, `fav_userid`",
                  "'$favid', '$deckid', '$userid'",
                  $con);
      Deck::favorite_add_one($deckid, $con);
      User::favorites_add_one($userid, $con);

      // Add user favorites deck activity (5)
      $type = 5;
      $data = array(
	'userid' => $userid,
	'deckid' => $deckid,
	'time' => $datetime,
	'circleid' => $circleid
      );
      $data['favorite']['favorites'] = true;
      Activity::add_activity($type, $data, $con);

      return $favid;
    }
    return NULL;
  }

  public static function unfavorite($deckid, $userid, $con) {
    delete_from("favorites", 
                "WHERE `fav_userid` = '$userid' AND `deckid` = '$deckid'", 
                "", $con);
    if (mysqli_affected_rows($con) > 0) {
      User::favorites_subtract_one($userid, $con);
      Deck::favorite_subtract_one($deckid, $con);
    }
  }

  // Returns an array of the users that are favorate the deck
  public static function favorites($deckid, $con) {
    $return_ids = array();
    $result = select_from("favorites", "`fav_userid`", "WHERE `userid` = '$userid'", $con);
    while ($row = mysqli_fetch_assoc($result)) {
      $return_ids[] = $row['fav_userid'];
    }
    return $return_ids;
  }

  // Returns an array of all the decks that a user is favorites
  public static function users_favorites($fav_userid, $con) {
    $return_ids = array();
    $result = select_from("favorites", "`userid`", "WHERE `fav_userid` = '$fav_userid'", $con);
    while ($row = mysqli_fetch_assoc($result)) {
      $return_ids[] = $row['deckid'];
    }
    return $return_ids;
  }
}
?>
