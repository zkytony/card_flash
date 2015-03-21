<?php

require_once "../database.php";
require_once "../functions.php";

/* 
   ----------------
   The Card class
   ----------------
 */
class Card
{
  private $cardid;
  private $info;
  private $exist;
  
  function __construct($cardid, $con) {
    $result = select_from("cards", "*", "WHERE `cardid` = '$cardid'", $con);
    $this->exist = $result->num_rows == 1;
    if ($this->exist) {
      $this->cardid = $cardid;
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

  // Adds a card to a deck
  // $title, $sub, $content are information of the card
  // $circleid is not NULL if this card is related to a circle
  // $type is the type of the card:
  //     0 - Normal
  //     1 - User Card
  //     2 - Status Card
  // Returns the cardid of the added card
  // Note: We do not want the user to create a deck that can
  // create all these type of cards. User Card and Status Card
  // should be created directly, and added to a specific deck
  // fixed for each user
  public static function add($title, $sub, $content, $deckid, $userid, $type, $circleid, $con) {
    try {
      if ($type != 0 && $type != 1 && $type != 2) {
        throw 98;
      }

      // For the sake of activity, we want to keep time consistent. So we will use PHP date() to get current time, and
      // use MYSQL's STR_TO_DATE() to convert it to MySQL datetime format
      $datetime = date("H:i:s,m-d-Y"); // the format is specified in activity.php:add_activity()
      
      $result=select_from("cards", "`cardid`", "", $con);
      $num_rows=$result->num_rows;

      $cardid='card_' . $num_rows;
      $cardid=ensure_unique_id($cardid, "cards", "cardid", $con);

      $columns="`cardid`,`title`,`sub`,`content`,";
      $columns.="`userid`,`deckid`,`create_time`,`deleted`, `type`";
      $values="'$cardid','$title','$sub','$content',"
             ."'$userid','$deckid',STR_TO_DATE(\"{$datetime}\", \"%H:%i:%s,%m-%d-%Y\"), '0', '$type'";
      insert_into("cards", $columns, $values, $con);

      // A card added to deck activity (2)
      $type = 2;
      $data = array(
	'userid' => $userid,
	'deckid' => $deckid,
	'cardid' => $cardid,
	'circleid' => $circleid,
	'time' => $datetime
      );
      $data['newcard']['new'] = '1';
      Activity::add_activity($type, $data, $con);

      return $cardid;
    } catch (int $exp) {
      echo "Error $exp: You are not giving the right type";
    }
  }

  // Edits a card
  public static function edit($cardid, $title, $sub, $content, $con) {
    update_table("cards", array("`title`","`sub`","`content`"),
                 array("'$title'","'$sub'","'$content'"), 
                 "WHERE `cardid` = '$cardid'", $con);
  }

  // Returns an array of Card objects associated with the given deckid
  public static function get_cards($deckid, $active, $con) {
    $restrict_str = "WHERE `deckid`='$deckid' ";
    if ($active) {
      $restrict_str .= "AND `deleted` = '0'";
    }
    $result = select_from("cards", "`cardid`", $restrict_str, $con);
    
    $cards = array();
    while ($row = mysqli_fetch_assoc($result)) {
      $cards[] = new Card($row['cardid'], $con);
    }
    return $cards;
  }
 
  public static function delete($cardid, $con) {
    $restrict_str="WHERE `cardid` = '$cardid'";
    update_table("cards", array("`deleted`"), array("'1'"), 
                 $restrict_str, $con);
    return true;
  }

  // Likes a card - increments the count of likes in `like` column
  // $circleid is not NULL if this comment is related to a circle
  public static function like($userid, $cardid, $circleid, $con) {
    // For the sake of activity, we want to keep time consistent. So we will use PHP date() to get current time, and
    // use MYSQL's STR_TO_DATE() to convert it to MySQL datetime format
    $datetime = date("H:i:s,m-d-Y"); // the format is specified in activity.php:add_activity()

    update_table("cards", array("`like`"), array("`like`+1"), "WHERE `cardid` = '$cardid'", $con);

    // Add user likes activity (11)
    $act_type = 11;
    $data = array(
      'userid' => $userid,
      'time' => $datetime,
      'circleid' => $circleid
    );
    $data['likes']['type'] = 0; // type 1 for liking a card
    $data['likes']['targetid'] = $cardid;
    Activity::add_activity($act_type, $data, $con);
  }

  // Unlikes a card - increments the count of likes in `like` column
  // Assume that a person cannot unlike if he has not yet liked
  public static function unlike($userid, $cardid, $con) {
    update_table("cards", array("`like`"), array("`like`-1"), "WHERE `cardid` = '$cardid'", $con);
  }
}
?>
