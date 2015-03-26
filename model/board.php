<?php 

require_once "../database.php";
require_once "../functions.php";

/*
   ----------------
   The Board class
   ----------------
 */
class Board
{
  private $boardid;
  private $info;
  private $exist;
  
  function __construct($boardid, $con) {
    $result = select_from("boards", "*", "WHERE `boardid` = '$boardid'", $con);
    $this->exist = $result->num_rows == 1;
    if ($this->exist) {
      $this->boardid = $boardid;
      $this->info = array();
      while ($rows = mysqli_fetch_assoc($result)) {
        $this->info = $rows; // In PHP, arrays are assigned in copy
        break; // only can be 1 match
      }
    }  
  }

  public function get_id() {
    return $this->boardid;
  }
  
  public function get_info() {
    return $this->info;
  }

  // Creates a board by adding a row in the boards table
  // This function may need to be called in User::register() function
  public static function create_board($userid, $con) {
    if (!Board::already_has($userid, $con)) {
      // use MYSQL's STR_TO_DATE() to convert it to MySQL datetime format
      $datetime = date("H:i:s,m-d-Y"); // the format is specified in activity.php:add_activity()

      $boardid = make_id("board", "boards", "boardid", $con);
      insert_into("boards", "`boardid`,`userid`,`create_time`",
		  "'$boardid', 'userid', STR_TO_DATE(\"$datetime\", \"%H:%i:%S,%m-%d-%Y\")",
		  $con);
      return $boardid;
    } else {
      return NULL; // boarder already exists for this user
    }
  }

  public static function already_has($userid, $con) {
    $result = select_from("boards", "`boardid`", "WHERE `userid` = '$userid'", $con);
    return $result->num_rows == 1; // expect to be 1
  }

  public static function add($userid, $type, $targetid, $con) {
    // because each user has only one board thenwe can use a user as
    // the condition for queries
  }

  public static function remove($userid, $type, $targetid, $con) {
  }
}
?>
