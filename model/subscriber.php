<?php 

require_once "../database.php";
require_once "../functions.php";

/*
   ---------------
   The Subscriber class
   ---------------
 */
class Subscriber
{
  private $sbrid;
  private $info;
  private $exist;

  function __construct($sbrid, $con) {
    $result = select_from("subscribers", "*", "WHERE `sbrid` = '$sbrid'", $con);
    $this->exist = $result->num_rows == 1;
    if ($this->exist) {
      $this->sbrid = $sbrid;
      $this->info = array();
      while ($rows = mysqli_fetch_assoc($result)) {
        $this->info = $rows; // In PHP, arrays are assigned in copy
        break; // only can be 1 match
      }
    }
  }

  public function get_id() {
    return $this->sbrid;
  }

  public static function get_sbrid($deckid, $userid, $con) {
    $result = select_from("subscribers", "*", 
                          "WHERE `sbr_userid` = '$userid' AND `deckid` = '$deckid'",
                          $con);
    while($row = mysqli_fetch_assoc($result)) {
      return $row['sbrid']; // should be only one match
    }
    // no row
    return NULL;
  }

  // Called when userid subscribes to deckid
  // $circleid is not NULL if the deck to be subscribed is related to a circle
  public static function subscribe($deckid, $userid, $circleid, $con) {
    $sbrid = Subscriber::get_sbrid($deckid, $userid, $con);
    if (!is_null($sbrid)) {
      return $sbrid;
    } else if (!Deck::is_owner_of($deckid, $userid, $con) and  Deck::is_open($deckid, $con)) {

      // For the sake of activity, we want to keep time consistent. So we will use PHP date() to get current time, and
      // use MYSQL's STR_TO_DATE() to convert it to MySQL datetime format
      $datetime = date("H:i:s,m-d-Y"); // the format is specified in activity.php:add_activity()

      $result = select_from("subscribers", "*", "", $con);
      $sbrid = "sbr_" . $result->num_rows;
      $sbrid = ensure_unique_id($sbrid, "subscribers", "sbrid", $con);

      insert_into("subscribers",
                  "`sbrid`, `deckid`, `sbr_userid`",
                  "'$sbrid', '$deckid', '$userid'",
                  $con);
      Deck::subscriber_add_one($deckid, $con);
      User::subscribing_add_one($userid, $con);

      // Add user subscribes deck activity (5)
      $type = 5;
      $data = array(
	'userid' => $userid,
	'deckid' => $deckid,
	'time' => $datetime,
	'circleid' => $circleid
      );
      $data['subscribe']['subscribing'] = true;
      Activity::add_activity($type, $data, $con);

      return $sbrid;
    }
    return NULL;
  }

  public static function unsubscribe($deckid, $userid, $con) {
    delete_from("subscribers", 
                "WHERE `sbr_userid` = '$userid' AND `deckid` = '$deckid'", 
                "", $con);
    if (mysqli_affected_rows($con) > 0) {
      User::subscribing_subtract_one($userid, $con);
      Deck::subscriber_subtract_one($deckid, $con);
    }
  }

  // Returns an array of the followers' userid of a user
  public static function subscribers($deckid, $con) {
    $return_ids = array();
    $result = select_from("subscribers", "`sbr_userid`", "WHERE `userid` = '$userid'", $con);
    while ($row = mysqli_fetch_assoc($result)) {
      $return_ids[] = $row['sbr_userid'];
    }
    return $return_ids;
  }

  // Returns an array of all the decks that a user is following
  public static function following($sbr_userid, $con) {
    $return_ids = array();
    $result = select_from("subscribers", "`userid`", "WHERE `sbr_userid` = '$sbr_userid'", $con);
    while ($row = mysqli_fetch_assoc($result)) {
      $return_ids[] = $row['deckid'];
    }
    return $return_ids;
  }
}
?>
