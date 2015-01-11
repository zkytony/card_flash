<?php 

require_once "../database.php";
require_once "../functions.php";

/*
   ---------------
   The Follower class
   ---------------
 */
class Follower
{
  private $flwrid;
  private $info;
  private $exist;

  function __construct($flwrid, $con) {
    $result = select_from("followers", "*", "WHERE `flwrid` = '$flwrid'", $con);
    $this->exist = $result->num_rows == 1;
    if ($this->exist) {
      $this->flwrid = $flwrid;
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
  
  public static function get_flwrid($userid, $flwr_userid, $con) {
    $result = select_from("followers", "*", 
                          "WHERE `userid` = '$userid' AND `flwr_userid` = '$flwr_userid'",
                          $con);
    while($row = mysqli_fetch_assoc($result)) {
      return $row['flwrid']; // should be only one match
    }
    // no row
    return NULL;
  }

  // Given follower's userid ($flwr_userid) and the id of the user that
  // is subject to be followed, the following is added to the table
  public static function follow($userid, $flwr_userid, $con) {
    $flwrid = Follower::get_flwrid($userid, $flwr_userid, $con);
    if ($flwrid != NULL) {
      // already followed
      return $flwrid;
    } else {
      // not followed
      $result = select_from("followers", "*", "", $con);
      $flwrid = "flwr_" . $result->num_rows;
      $flwrid = ensure_unique_id($flwrid, "followers", "flwrid", $con);

      insert_into("followers",
                  "`flwrid`, `userid`, `flwr_userid`",
                  "'$flwrid', '$userid', '$flwr_userid'",
                  $con);
      User::follower_add_one($userid, $con);
      User::following_add_one($flwr_userid, $con);
      return $flwrid;
    }
  }

  // Make the follower unfollow the user
  public static function unfollow($userid, $flwr_userid, $con) {
    delete_from("followers", 
                "WHERE `userid` = '$userid' AND `flwr_userid` = '$flwr_userid'", 
                "", $con);
    if (mysqli_affected_rows($con) > 0) {
      User::follower_subtract_one($userid, $con);
      User::following_subtract_one($flwr_userid, $con);
    }
  }

  // Returns an array of the followers' id of a user
  public static function followers($userid, $con) {
    $return_ids = array();
    $result = select_from("followers", "`flwrid`", "WHERE `userid` = '$userid'", $con);
    while ($row = mysqli_fetch_assoc($result)) {
      $return_ids[] = $row['flwrid'];
    }
    return $return_ids;
  }

  // Returns an array of all the users that a user is following
  public static function following($flwr_userid, $con) {
    $return_ids = array();
    $result = select_from("followers", "`userid`", "WHERE `flwr_userid` = '$flwr_userid'", $con);
    while ($row = mysqli_fetch_assoc($result)) {
      $return_ids[] = $row['userid'];
    }
    return $return_ids;
  }

}
?>