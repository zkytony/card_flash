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
  // $circleid is not NULL if the board is actually associated with a circle
  public static function create_board($userid, $circleid, $con) {
    // use MYSQL's STR_TO_DATE() to convert it to MySQL datetime format
    $datetime = date("H:i:s,m-d-Y"); // the format is specified in activity.php:add_activity()

    $boardid = make_id("board", "boards", "boardid", $con);
    insert_into("boards", "`boardid`,`userid`,`create_time`",
		"'$boardid', '$userid', STR_TO_DATE(\"$datetime\", \"%H:%i:%S,%m-%d-%Y\")",
		$con);
    return $boardid;
  }

  // Returns the brdwthid
  // add something to the board
  // $type is the type of "thing"
  // 0 - card
  // 1 - deck
  // $circleid is not NULL if the board is actually associated with a circle
  public static function add($boardid, $type, $targetid, $circleid, $con) {
    $brdwthid = Board::exists_on_board($boardid, $type, $targetid, $con);

    if (is_null($brdwthid)) {
      // use MYSQL's STR_TO_DATE() to convert it to MySQL datetime format
      $datetime = date("H:i:s,m-d-Y"); // the format is specified in activity.php:add_activity()

      $brdwthid = make_id("brdwth", "board_with", "brdwthid", $con);
      insert_into("board_with", "`brdwthid`,`boardid`,`type`,`targetid`,`circleid`",
		  "'$brdwthid','$boardid','$type','$targetid','$circleid'", $con);

      // Add user updates board activity
      $act_type = 13;
      $data = array(
	'userid' => $userid,
	'time' => $datetime,
	'circleid' => $circleid
      );
      $data['board']['type'] = $type;
      $data['board']['targetid'] = $targetid;
      $data['board']['add'] = true;
      Activity::add_activity($act_type, $data, $con);

    }
    return $brdwthid;
  }

  // Returns brdwthid if the thing is on the board already
  // Otherwise returns NULL
  public static function exists_on_board($boardid, $type, $targetid, $con) {
    $result = select_from("board_with", "`brdwthid`", 
			  "WHERE `boardid` = '$boardid' AND `targetid` = '$targetid'", $con);
    if ($result->num_rows > 0) {
      while ($row = mysqli_fetch_assoc($result)) {
	return $row['brdwthid'];
      }
    }
    return NULL;
  }

  // Remove a "thing" from the given board
  // $type:
  // 0 - card
  // 1 - deck
  public static function remove($boardid, $type, $targetid, $con) {
    // use MYSQL's STR_TO_DATE() to convert it to MySQL datetime format
    $datetime = date("H:i:s,m-d-Y"); // the format is specified in activity.php:add_activity()

    delete_from("board_with", "WHERE `boardid` = '$boardid' AND `targetid` = '$targetid'",
		"", $con);

    // Add user updates board activity
    $act_type = 13;
    $data = array(
      'userid' => $userid,
      'time' => $datetime,
      'circleid' => $circleid
    );
    $data['board']['type'] = $type;
    $data['board']['targetid'] = $targetid;
    $data['board']['add'] = false;
    Activity::add_activity($act_type, $data, $con);
  }

  public static function delete_board($boardid, $con) {
    delete_from("boards", "WHERE `boardid` = '$boardid'", "", $con);
  }
}
?>
