 <?php 

require_once "../database.php";
require_once "../functions.php";

/* 
   ----------------
   The Activity class
   ----------------
 */
class Activity
{
  // timeline table is the main table for retrieving activity information
  // Activity types:
  // 0 - user register
  // 1 - user creates a deck
  // 2 - card(s) added to a deck
  // 3 - tag(s) of a deck is changed
  // 4 - a deck is shared to other users
  // 5 - a user favorites to a deck
  // 6 - a user joins a group
  // 7 - a user follows another user
  // 8 - a deck's information is edited (title, description, open, close)
  // 9 - a card's information is edited (title, subtitle, content)
  // 10 - user comments on something
  // 11 - user likes something
  // 12 - user views cards in a deck
  // 13 - user updates his board
  private $timeid;
  // An array that stores information about this user
  private $info; 
  private $exist;

  function __construct($timeid, $con) {
    $result = select_from("timeline", "*", "WHERE `timeid` = '$timeid'", $con);
    $this->exist = $result->num_rows == 1;
    if ($this->exist) {
      $this->timeid = $timeid;
      $this->info = array();
      while ($rows = mysqli_fetch_assoc($result)) {
        $this->info = $rows; // In PHP, arrays are assigned in copy
        break; // only can be 1 match
      }
    }
  }

  public function get_info() {
    return $this->info;
  }

  public function get_id() {
    return $this->timeid;
  }

  // Since different information is needed for different activity
  // table, $data will be an array of the information necessary
  // The keys are specific Strings that will be defined below:
  // 'time' : the time string that activity happens in datetime format
  //        Format: %H:%i:%s,%m-%d-%Y. Example: 02:53:23,4-5-1995
  // 'userid' : the userid of the user that is the subject of the activity
  // 'deckid' : the deckid of the associated deck, if there is one
  // 'cardid' : the deckid of the associated card, if there is one
  // 'circleid' : the circleid of the associated circle, if there is one
  // for more specific data, pass them in by 2D array as defined here:
  // 'newdeck' : specific data for new deck activity (of course, this includes delete deck activity)
  //      - 'new' :  (BOOL) creating or deleting?
  // 'newcard' : specific dta for new card activity (...)
  //      - 'new' :  (BOOL) creating or deleting?
  // 'tagchange' : specific data for tags changed activity
  //      - 'added_tags' : (STRING) tags separated by comma
  // 'deckshare' : specific data for deck share activity
  //      - 'from_user' : the userid from whom the deck is shared
  //      - 'to_user' : the userid to whom the deck is shared
  //      - 'sharing' : (BOOL) true if 'share', false if 'unshare'
  // 'favorite' : specific data for favorite deck activity
  //      - 'favorites' : (BOOL) true if favorite; false if unfavorite;
  // 'joingroup' : specific data for user join group activity
  //      - 'init' : (BOOL) true if the user is the creator
  // 'userfollow' : specific data for user follow activity
  //      - 'targetid' : the userid who is followed
  //      - 'following' : (BOOL) true if following; false if unfollow
  // 'comments' : specific data for user comments activity
  //      - 'commentid' : the comment's id in comments table
  //      - 'type' : the type of comment; 0 for card, 1 for deck
  //      - 'targetid' : the id of that target that this comment points to
  // 'likes' : specific data for user likes activity
  //      - 'type' : the type of thing that is liked.
  //      - 'targetid' : the id of the thing that is liked
  // 'board' : specific data for user update board activity
  //      - 'boardid' : the id of the board where things are put
  //      - 'type' : the type of thing that added or removed
  //      - 'targetid' : the id of the thing that is added or removed
  //      - 'add' : true if user adds stuff to the board
  // Returns the timeid for this activity in timeline table, if successfully added;
  // otherwise, returns NULL
  public static function add_activity($type, $data, $con) {

    $id = '';
    $tablename = '';
    $need_insert_timeline = true;

    switch ($type) {

      case 0: // user register
        $tablename = "activity_user_register";
        $id = make_id("reg", $tablename, "actid", $con);
        $columns = "`actid`, `userid`, `time`";
        $values = "'$id', '{$data['userid']}', STR_TO_DATE(\"{$data['time']}\", \"%H:%i:%S,%m-%d-%Y\")";
        insert_into($tablename, $columns, $values, $con);
        break;

      case 1: // user creates / deletes a deck
        $tablename = "activity_deck_new_del";
        $id = make_id("dkn", $tablename, "actid", $con);
        $columns = "`actid`, `userid`, `deckid`, `circleid`, `new`, `time`";
        $values = "'$id', '{$data['userid']}', '{$data['deckid']}', "
                ."'{$data['circleid']}', '{$data['newdeck']['new']}', STR_TO_DATE(\"{$data['time']}\", \"%H:%i:%S,%m-%d-%Y\")";
        insert_into($tablename, $columns, $values, $con);
        break;

      case 2: // card(s) added to a card
        $tablename = "activity_card_new_del";
        $id = make_id("cdn", $tablename, "actid", $con);
        $columns = "`actid`, `userid`, `deckid`, `cardid`, `circleid`, `new`, `time`";
        $values = "'$id', '{$data['userid']}', '{$data['deckid']}', "
                ."'{$data['cardid']}', '{$data['circleid']}', '{$data['newcard']['new']}', STR_TO_DATE(\"{$data['time']}\", \"%H:%i:%S,%m-%d-%Y\")";
        insert_into($tablename, $columns, $values, $con);
        break;

      case 3: // tag(s) of a deck is changed
        $tablename = "activity_tags_changed";
        $id = make_id("tch", $tablename, "actid", $con);
        $columns = "`actid`, `userid`, `deckid`, `added_tags`, `circleid`, `time`";
        $values = "'$id', '{$data['userid']}', '{$data['deckid']}', "
                ."'{$data['tagchange']['added_tags']}', '{$data['circleid']}', STR_TO_DATE(\"{$data['time']}\", \"%H:%i:%S,%m-%d-%Y\")";
        insert_into($tablename, $columns, $values, $con);
        break;

      case 4: // a deck is shared to other users
        $tablename = "activity_deck_share";
        $id = make_id("dks", $tablename, "actid", $con);
        $columns = "`actid`, `from_userid`, `to_userid`, `deckid`, `circleid`, `sharing`, `time`";
        $values = "'$id', '{$data['deckshare']['from_userid']}', '{$data['deckshare']['to_userid']}', '{$data['deckid']}', "
                ."'{$data['circleid']}', '{$data['deckshare']['sharing']}', STR_TO_DATE(\"{$data['time']}\", \"%H:%i:%S,%m-%d-%Y\")";
        insert_into($tablename, $columns, $values, $con);
        break;

      case 5: // a user favorites a deck
        $tablename = "activity_deck_favorites";
        $id = make_id("sub", $tablename, "actid", $con);
        $columns = "`actid`, `userid`, `deckid`, `circleid`, `favorites`, `time`";
        $values = "'$id', '{$data['userid']}', '{$data['deckid']}', '{$data['circleid']}', '{$data['favorite']['favorites']}', STR_TO_DATE(\"{$data['time']}\", \"%H:%i:%S,%m-%d-%Y\")";
        insert_into($tablename, $columns, $values, $con);
        break;

      case 6: // a user joins a group
        $tablename = "activity_group_join";
        $id = make_id("jgp", $tablename, "actid", $con);
        $columns = "`actid`, `userid`, `circleid`, `init`, `time`";
        $values = "'$id', '{$data['userid']}', '{$data['circleid']}', '{$data['joingroup']['init']}', STR_TO_DATE(\"{$data['time']}\", \"%H:%i:%S,%m-%d-%Y\")";
        insert_into($tablename, $columns, $values, $con);
        break;

      case 7: // a user follows another user
        $tablename = "activity_user_follow";
        $id = make_id("fol", $tablename, "actid", $con);
        $columns = "`actid`, `userid`, `targetid`, `following`, `time`";
        $values = "'$id', '{$data['userid']}', '{$data['userfollow']['targetid']}', '{$data['userfollow']['following']}', STR_TO_DATE(\"{$data['time']}\", \"%H:%i:%S,%m-%d-%Y\")";
        insert_into($tablename, $columns, $values, $con);
        break;

      case 8: // a deck's information is edited
        $tablename = "activity_deck_edited";
        $id = make_id("dup", $tablename, "actid", $con);
        $columns = "`actid`, `userid`, `deckid`, `circleid`, `time`";
        $values = "'$id', '{$data['userid']}', '{$data['deckid']}', '{$data['circleid']}', STR_TO_DATE(\"{$data['time']}\", \"%H:%i:%S,%m-%d-%Y\")";
        insert_into($tablename, $columns, $values, $con);
        break;

      case 9: // a card's information is edited
        $tablename = "activity_card_edited";
        $id = make_id("cup", $tablename, "actid", $con);
        $columns = "`actid`, `userid`, `cardid`, `circleid`, `time`";
        $values = "'$id', '{$data['userid']}', '{$data['cardid']}', '{$data['circleid']}', STR_TO_DATE(\"{$data['time']}\", \"%H:%i:%S,%m-%d-%Y\")";
        insert_into($tablename, $columns, $values, $con);
        break;

      case 10: // user makes a comment
	$tablename = "activity_user_comments";
        $id = make_id("uct", $tablename, "actid", $con);
        $columns = "`actid`, `userid`, `commentid`, `type`,`targetid`,`circleid`,`time`";
        $values = "'$id', '{$data['userid']}', '{$data['comments']['commentid']}', '{$data['comments']['type']}','{$data['comments']['targetid']}','{$data['circleid']}', STR_TO_DATE(\"{$data['time']}\", \"%H:%i:%S,%m-%d-%Y\")";
        insert_into($tablename, $columns, $values, $con);
	break;

      case 11: // user likes something
	$tablename = "activity_user_likes";
	$id = make_id("ulk", $tablename, "actid", $con);
	$columns = "`actid`,`userid`,`type`,`targetid`,`circleid`,`time`";
	$values = "'$id','{$data['userid']}','{$data['likes']['type']}','{$data['likes']['targetid']}','{$data['circleid']}', STR_TO_DATE(\"{$data['time']}\", \"%H:%i:%S,%m-%d-%Y\")";
        insert_into($tablename, $columns, $values, $con);
	break;

      case 12: // user views a deck
	$tablename = "activity_user_view_deck";

	// Check if the user has viewed this deck before:
	$result = select_from($tablename, "`actid`", "WHERE `userid` = '{$data['userid']}' AND `deckid` = '{$data['deckid']}'", $con);
	if ($result->num_rows > 0) { // already has
	  $actid = '';
	  while ($row = mysqli_fetch_assoc($result)) {
	    $actid = $row['actid'];
	  }
	  update_table($tablename, array("`cardid`","`time`"),
		       array("'{$data['cardid']}'", "STR_TO_DATE(\"{$data['time']}\", \"%H:%i:%S,%m-%d-%Y\")"),
		       "WHERE `actid` = '$actid'", $con);
	  
	  $need_insert_timeline = false; // Since no duplicate refid in timeline table, we only need to update the time for that refid in the timeline table
	  update_table("timeline", array("`time`"),
		       array("STR_TO_DATE(\"{$data['time']}\", \"%H:%i:%S,%m-%d-%Y\")"),
		       "WHERE `refid` = '$actid'", $con);

	  // Need to return the timeid here
	  $result = select_from("timeline", "`timeid`", "WHERE `refid` = '$actid'", $con);
	  while ($row = mysqli_fetch_assoc($result)) {
	    return $row['timeid'];
	  }
	} else {
          $id = make_id("uvd", $tablename, "actid", $con);
          $columns = "`actid`, `userid`, `deckid`, `cardid`,`circleid`,`time`";
          $values = "'$id', '{$data['userid']}', '{$data['deckid']}','{$data['cardid']}','{$data['circleid']}', STR_TO_DATE(\"{$data['time']}\", \"%H:%i:%S,%m-%d-%Y\")";
          insert_into($tablename, $columns, $values, $con);
	}
	break;

      case 13: // user updates board
	$tablename = "activity_user_updates_board";
	$id = make_id("upb", $tablename, "actid", $con);
	$columns = "`actid`,`userid`,`boardid`,`type`,`targetid`,`add`,`circleid`,`time`";
	$values = "'$id','{$data['userid']}','{$data['board']['boardid']}','{$data['board']['add']}','{$data['board']['targetid']}','{$data['board']['type']}','{$data['circleid']}', STR_TO_DATE(\"{$data['time']}\", \"%H:%i:%S,%m-%d-%Y\")";
        insert_into($tablename, $columns, $values, $con);
	break;

      default:
        return NULL;
    }

    if ($need_insert_timeline) {
      // now $id is the id for that particular activity table
      // And $tablename is the name of the particular table
      $timeid = make_id("time", "timeline", "timeid", $con);
      $columns = "`timeid`, `userid`, `refid`, `reftable`, `type`, `time`";
      $values = "'$timeid', '{$data['userid']}', '$id', '$tablename', '$type', STR_TO_DATE(\"{$data['time']}\", \"%H:%i:%S,%m-%d-%Y\")";
      insert_into('timeline', $columns, $values, $con);
      return $timeid;
    } else {
      return NULL;
    }
  }

  // Given start time (string) and end time (string),
  // returns an 2D array of human understandable sentence elements 
  // that describes each activities happened within the given time range.
  // returns a 2D array containing the data of the activities retrieved
  // The activity data are obtained from the specific tables that the
  // particular types of activities are stored.
  // NOTE: time string is of this form: "H:i:s,m-d-Y". E.g. 3:42:32,5-3-2001
  public static function range($start, $end, $userid, $con) {
    $data = Activity::within($start, $end, $userid, $con);
    return Activity::collect($data, $con);
  }

  // Given start time (string) and end time (string),
  // returns an array of the necessary information about
  // a given user's activities happening in the period.
  // Specifically, it contains:
  // `timeid`, `userid`, `refid`, `reftable`, `type`, `time`
  // If end time is set to NULL, then this function returns
  // all activities up to the current time
  // NOTE: time string is of this form: "H:i:s,m-d-Y". E.g. 3:42:32,5-3-2001
  private static function within($start, $end, $userid, $con) {
    // Convert $start and $end to mysql-friendly string:
    $start = str_to_date($start, "%H:%i:%s,%m-%d-%Y"); // str_to_date is a function from database.php
    $end = str_to_date($end, "%H:%i:%s,%m-%d-%Y");

    // select the rows from timeline table
    if (is_null($end)) $end = date("H:i:s,m-d-Y"); // get the current date time
    $result = select_from("timeline", "*", "WHERE `userid` = '$userid' AND "
					  ."`time` BETWEEN $start AND $end", $con);
    $data = array(); // the returning array
    while ($row = mysqli_fetch_assoc($result)) {
      $timeid = $row['timeid'];
      $data[$timeid] = $row; // PHP automatically copies the array
      $data[$timeid]['type'] = $row['type']; // For later filtering purposes
    }
    return $data;
  }

  // Based on the data given from Activity::within(), this function
  // returns a 2D array containing the data of the activities retrieved
  // The activity data are obtained from the specific tables that the
  // particular types of activities are stored.
  private static function collect($data, $con) {
    $details = array(); // the array to return
   
    // iterate through key and value
    foreach ($data as $timeid => $arr) {
      $details[$timeid] = Activity::get_activity_data($arr['reftable'], $arr['refid'], $con);
      $details[$timeid]['type'] = $arr['type'];
    }
    return $details;
  }

  public static function get_activity_data($reftable, $refid, $con) {
    $result = select_from($reftable, '*', "WHERE `actid` = '$refid'", $con);
    $activity_data = array();
    while ($row = mysqli_fetch_assoc($result)) {
      return $row;
    }
  }
}

?>
