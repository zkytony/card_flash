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
  // 5 - a user subscribes to a deck
  // 6 - a user joins a group
  // 7 - a user follows another user
  // 8 - a deck's information is edited (title, description, open, close)
  // 9 - a card's information is edited (title, subtitle, content)

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
  // 'subscribe' : specific data for subscribe deck activity
  //      - 'subscribing' : (BOOL) true if subscribing; false if unsubscribe;
  // 'joingroup' : specific data for user join group activity
  //      - 'init' : (BOOL) true if the user is the creator
  // 'userfollow' : specific data for user follow activity
  //      - 'targetid' : the userid who is followed
  //      - 'following' : (BOOL) true if following; false if unfollow
  //
  // Returns the timeid for this activity in timeline table, if successfully added;
  // otherwise, returns NULL
  public static function add_activity($type, $data, $con) {

    $id = '';
    $tablename = '';

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

      case 5: // a user subscribes to a deckb
        $tablename = "activity_deck_subscribe";
        $id = make_id("sub", $tablename, "actid", $con);
        $columns = "`actid`, `userid`, `deckid`, `circleid`, `subscribing`, `time`";
        $values = "'$id', '{$data['userid']}', '{$data['deckid']}', '{$data['circleid']}', '{$data['subscribe']['subscribing']}', STR_TO_DATE(\"{$data['time']}\", \"%H:%i:%S,%m-%d-%Y\")";
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

      default:
        return NULL;
    }

    // now $id is the id for that particular activity table
    // And $tablename is the name of the particular table
    $timeid = make_id("time", "timeline", "timeid", $con);
    $columns = "`timeid`, `userid`, `refid`, `reftable`, `type`, `time`";
    $values = "'$timeid', '{$data['userid']}', '$id', '$tablename', '$type', STR_TO_DATE(\"{$data['time']}\", \"%H:%i:%S,%m-%d-%Y\")";
    insert_into('timeline', $columns, $values, $con);

    return $timeid;
  }

  // Given start time (string) and end time (string),
  // returns an array of the necessary information about
  // a given user's activities happening in the period.
  // Specifically, it contains:
  // `timeid`, `userid`, `refid`, `reftable`, `type`, `time`
  // If end time is set to NULL, then this function returns
  // all activities up to the current time
  public static function within($start, $end, $userid, $con) {
    // select the rows from timeline table
    if (is_null($end)) $end = date("H:i:s,m-d-Y"); // get the current date time
    $result = select_from("timeline", "*", "WHERE `userid` = '$userid' AND "
					  ."`time` BETWEEN '$start' AND '$end'", $con);
    $data = array(); // the returning array
    while ($row = mysqli_fetch_assoc($result)) {
      $timeid = $row['timeid'];
      $data[$timeid] = $row; // PHP automatically copies the array
    }
  }

  // Based on the data given from Activity::within(), this function
  // returns an array of more specific data, but it translates the activity
  // data into 4 values: subject, action, object, additional
  // Thus, the resulting array will have keys:
  // timeid => (
  //    'time', 
  //    'subject', 
  //    'action'. 
  //    'object', 
  //    'additional'
  //  )
  // The purpose is to better present the activity in a more understandable way
  // Assuming $data is an array generated by Activity::within()
  public static function collect($data, $con) {
    $details = array(); // the array to return
    
    $actions = array(
      'registered', // 0
      'created', // 1
      'added', // 2
      'tagged', // 3
      'shared', // 4
      'subscribed', // 5
      'joined', // 6
      'followed', // 7
      'edited', // 8
      'edited', // 9
    );

    // iterate through key and value
    foreach ($data as $timeid => $arr) {
      $details[$timeid] = array(
	'time' => $arr['time'],
	'subject' => '',
	'action' => '',
	'object' => '',
	'additional' => '',
      );

      $user_info = User::user_info($arr['userid'], $con);
      $details[$timeid]['subject'] = $user_info['first'] . ' ' . $user_info['last'];

      switch ($arr['type']) {
	case 0: // user register
	  $details[$timeid]['action'] = $actions[0];
	  // No object or additional is needed for this activity
	  break;
	case 1: // user creates / deletes a deck
	  $details[$timeid]['action'] = $actions[1];
	  break;
	case 2: // card(s) added to a card
	  $details[$timeid]['action'] = $actions[2];
	  break;
	case 3: // tag(s) of a deck is changed
	  $details[$timeid]['action'] = $actions[3];
	  break;
	case 4: // a deck is shared to other users
	  $details[$timeid]['action'] = $actions[4];
	  break;
	case 5: // a user subscribes to a deckb
	  $details[$timeid]['action'] = $actions[5];
	  break;
	case 6: // a user joins a group
	  $details[$timeid]['action'] = $actions[6];
	  break;
	case 7: // a user follows another user
	  $details[$timeid]['action'] = $actions[7];
	  break;
	case 8: // a deck's information is edited
	  $details[$timeid]['action'] = $actions[8];
	  break;
	case 9: // a card's information is edited
	  $details[$timeid]['action'] = $actions[9];
	  break;

      }
    }
  }
}

?>
