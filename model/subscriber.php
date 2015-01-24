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
    $result = select_from("subscribers", "*", "WHERE `flwrid` = '$flwrid'", $con);
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
    return $this->flwrid;
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

  public static function subscribe($deckid, $userid, $con) {
    $sbrid = Subscriber::get_sbrid($deckid, $userid, $con);
    if ($sbrid != NULL) {
      return $sbrid;
    } else if (!Deck::is_owner_of($deckid, $userid, $con) and  Deck::is_open($deckid, $con)) {
      $result = select_from("subscribers", "*", "", $con);
      $sbrid = "sbr_" . $result->num_rows;
      $sbrid = ensure_unique_id($sbrid, "subscribers", "sbrid", $con);

      insert_into("subscribers",
                  "`sbrid`, `deckid`, `sbr_userid`",
                  "'$sbrid', '$deckid', '$userid'",
                  $con);
      Deck::subscriber_add_one($deckid, $con);
      User::subscribing_add_one($userid, $con);
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