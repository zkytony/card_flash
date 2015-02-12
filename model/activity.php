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
  // 'userid' : the userid of the user that is the subject of the activity
  // 'deckid' : the deckid of the associated deck, if there is one
  // 'cardid' : the deckid of the associated card, if there is one
  // 'circleid' : the circleid of the associated circle, if there is one
  // for more specific data, pass them in by 2D array as defined here:
  // 'newdeck' : specific data for new deck activity (of course, this includes delete deck activity)
  //      - 'first' : (BOOL) first deck?
  //      - 'new' :  (BOOL) creating or deleting?
  // 'newcard' : specific dta for new card activity (...)
  //      - 'new' :  (BOOL) creating or deleting?
  public static add_activity($type, $data, $con) {
    switch ($type) {

      case 0: // user register
        $tablename = "activity_user_register";
        $id = make_id("reg", $tablename, "actid", $con);
        $columns = "`actid`, `userid`, `time`";
        $values = "'$id', '{$data['userid']}', '{$data['time']}";
        insert_into($tablename, $columns, $values, $con);
        break;

      case 1: // user creates / deletes a deck
        $tablename = "activity_deck_new_del";
        $id = make_id("dkn", $tablename, "actid", $con);
        $columns = "`actid`, `userid`, `deckid`, `first`, `circleid`, `new`, `time`";
        $values = "'$id', '{$data['userid']}', '{$data['deckid']}', "
                ."'{$data['newdeck']['first']}', '{$data['circleid']}', '{$data['newdeck']['new']}', '{$data['time']}";
        insert_into($tablename, $columns, $values, $con);
        break;

      case 2: // card(s) added to a deck
        $tablename = "activity_card_new_del";
        $id = make_id("cdn", $tablename, "actid", $con);
        $columns = "`actid`, `userid`, `deckid`, `cardid`, `circleid`, `new`, `time`";
        $values = "'$id', '{$data['userid']}', '{$data['deckid']}', "
                ."'{$data['cardid']}', '{$data['circleid']}', '{$data['newcard']['new']}', '{$data['time']}";
        insert_into($tablename, $columns, $values, $con);
        break;

      case 3: // tag(s) of a deck is changed
        $tablename = "activity_tags_changed";
        $id = make_id("tch", $tablename, "actid", $con);
        $columns = "`actid`, `userid`, `deckid`, `cardid`, `circleid`, `new`, `time`";
        $values = "'$id', '{$data['userid']}', '{$data['deckid']}', "
                ."'{$data['tagchange']['prev_tag']}', '{$data['tagchange']['now_tag']}', '{$data['circleid']}', '{$data['newcard']['new']}', '{$data['time']}";
        insert_into($tablename, $columns, $values, $con);

        break;

      case 4: // a deck is shared to other users
        $tablename = "activity_deck_share";
        break;

      case 5: // a user subscribes to a deck
        $tablename = "activity_deck_subscribe";
        break;

      case 6: // a user joins a group
        $tablename = "activity_group_join";
        break;

      case 7: // a user follows another user
        $tablename = "activity_user_follow";
        break;

      case 8: // a deck's information is edited
        $tablename = "activity_deck_edited";
        break;

      case 9: // a card's information is edited
        $tablename = "activity_card_edited";
        break;

      default:
        return NULL;
        break;
    }
  }
}

?>