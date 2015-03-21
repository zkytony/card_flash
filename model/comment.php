<?php 

require_once "../database.php";
require_once "../functions.php";

/* 
   ----------------
   The Comment class
   ----------------
 */
class Comment
{
  private $commentid;
  private $info;
  private $exist;

  function __construct($commentid, $con) {
    $result = select_from("comments", "*", "WHERE `commentid` = '$commentid'", $con);
    $this->exist = $result->num_rows == 1;
    if ($this->exist) {
      $this->commentid = $commentid;
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
    return $this->commentid;
  }

  // Make a comment.
  // Necessary information:
  // $userid - the user who made this comment
  // $reply_commentid - the comment id of the comment that this comment is replying. If this
  //     is a new comment, set the value to NULL
  // $type: the type of comment this is for. Currently we have:
  // 0 - commenting on a card
  // 1 - commenting on a deck
  // $targetid - the id of that target that this comment pointing to. For example,
  //     if this comment is for card, then targetid is a cardid.
  // $circleid is not NULL if this comment is related to a circle
  // Returns the commentid. If not successful, return NULL
  public static function comment($userid, $content, $reply_commentid, 
				 $type, $targetid, $circleid, $con) {

    if ($type != 0 && $type != 1) {
      return NULL;
    }

    // For the sake of activity, we want to keep time consistent. So we will use PHP date() to get current time, and
    // use MYSQL's STR_TO_DATE() to convert it to MySQL datetime format
    $datetime = date("H:i:s,m-d-Y"); // the format is specified in activity.php:add_activity()

    // Prevent injection:
    $content = mysqli_entities_fix_string($con, $content);

    $commentid = make_id("cmt", "comments", "commentid", $con);

    $columns = "`commentid`,`userid`,`type`,`targetid`,`content`";
    $values = "'$commentid','$userid','$type','$targetid','$content'";
    // see if we need reply_commentid:
    if (!is_null($reply_commentid)) {
      $columns .= ",`reply_commentid`";
      $values .= ",'$reply_commentid'";
    }
    insert_into("comments", $columns, $values, $con);

    // Add user comments activity
    $act_type = 10;
    $data = array(
      'userid' => $userid,
      'time' => $datetime,
      'circleid' => $circleid
    );
    $data['comments']['commentid'] = $commentid;
    $data['comments']['type'] = $type;
    $data['comments']['targetid'] = $targetid;
    Activity::add_activity($act_type, $data, $con);

    return $commentid;
  }

  // Delete a comment from database
  // Any comments that replies to this comment are also deleted
  // THIS NEEDS IMPROVEMENT about dealing with deleting replies
  public static function delete($commentid, $con) {
    delete_from("comments", "WHERE `commentid` = '$commentid'", "", $con);
    
    // Delete replying comments
    delete_from("comments", "WHERE `reply_commentid` = '$commentid'", "", $con);
    
  }

  // Returns the userid of the commenter
  // Returns NULL if this commentid cant relate to any user
  public static function commenter($commentid, $con) {
    $result = select_from("comments", "`userid`", "WHERE `commentid` = '$commentid'", $con);
    
    while ($row = mysqli_fetch_assoc($result)) {
      return $row['userid'];
    }
    return NULL;
  }

  // Returns the number of comments that a deck or a card has
  // $targetid - the id of that target that this comment pointing to. For example,
  //     if this comment is for card, then targetid is a cardid.
  public static function num_comments($targetid, $con) {
    $result = select_from("comments", "`commentid`", "WHERE `targetid` = '$targetid'", $con);
    return $result->num_rows;
  }

  // Returns the number of replies that a comment has
  // Based on reply_commentid
  // Notice: This will only count the number of replies directly to a comment.
  // In situations like this:
  // CommentA <- CommentB <- CommentC
  // If call this function to count number of replies that CommentA has, it will
  // be 1, because only CommentB is directly replying CommentA
  public static function num_replies($commentid, $con) {
    $result = select_from("comments", "`commentid`", "WHERE `reply_commentid` = '$commentid'", $con);
    return $result->num_rows;    
  }

  // Likes a comment - increments the count of likes in `like` column
  // $circleid is not NULL if this comment is related to a circle
  public static function like($userid, $commentid, $circleid, $con) {
    // For the sake of activity, we want to keep time consistent. So we will use PHP date() to get current time, and
    // use MYSQL's STR_TO_DATE() to convert it to MySQL datetime format
    $datetime = date("H:i:s,m-d-Y"); // the format is specified in activity.php:add_activity()

    update_table("comments", array("`like`"), array("`like`+1"), "WHERE `commentid` = '$commentid'" $con);

    // Add user likes activity (11)
    $act_type = 11;
    $data = array(
      'userid' => $userid,
      'time' => $datetime,
      'circleid' => $cricleid
    );
    $data['likes']['type'] = 2; // type 1 for liking a comment
    $data['likes']['targetid'] = $commentid;
    Activity::add_activity($act_type, $data, $con);
  }

  // Unlikes a comment - increments the count of likes in `like` column
  // Assume that a person cannot unlike if he has not yet liked
  public static function unlike($userid, $commentid, $con) {
    update_table("comments", array("`like`"), array("`like`-1"), "WHERE `commentid` = '$commentid'" $con);
  }
}
?>
