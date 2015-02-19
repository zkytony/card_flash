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

      // For the sake of activity, we want to keep time consistent. So we will use PHP date() to get current time, and
      // use MYSQL's STR_TO_DATE() to convert it to MySQL datetime format
      $datetime = date("H:i:s,m-d-Y"); // the format is specified in activity.php:add_activity()

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

      // Add user follows another user activity (7)
      $type = 7;
      $data = array(
	'userid' => $flwr_userid,
	'time' => $datetime
      );
      $data['userfollow']['following'] = true;
      $data['userfollow']['targetid'] = $userid;
      Activity::add_activity($type, $data, $con);

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

  // Returns an array of the followers' userid of a user
  public static function followers($userid, $con) {
    $return_ids = array();
    $result = select_from("followers", "`flwr_userid`", "WHERE `userid` = '$userid'", $con);
    while ($row = mysqli_fetch_assoc($result)) {
      $return_ids[] = $row['flwr_userid'];
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
