<?php 

require_once "../database.php";
require_once "../functions.php";

/*
   ---------------
   The Circle class
   ---------------
 */

class Circle
{
  private $circleid;
  private $info;
  private $exist;

  function __construct($circleid, $con) {
    $result = select_from("circles", "*", " WHERE `circleid` = '$circleid'", $con);
    $this->exist = $result->num_rows == 1;
    if ($this->exist) {
      $this->circleid = $circleid;
      $this->info = array();
      while ($rows = mysqli_fetch_assoc($result)) {
        $this->info = $rows; // In PHP, arrays are assigned in copy
        break; // only can be 1 match
      }
    }
  }

  public function get_id() {
    return $this->circleid;
  }

  public function get_info() {
    return $this->info;
  }

  public static function create($userid, $title, $forwhat, $con) {
    
    // For the sake of activity, we want to keep time consistent. So we will use PHP date() to get current time, and
    // use MYSQL's STR_TO_DATE() to convert it to MySQL datetime format
    $datetime = date("H:i:s,m-d-Y"); // the format is specified in activity.php:add_activity()

    // insert into circles

    $result = select_from("circles", "`circleid`", "", $con);
    $circleid = "crc_" . $result->num_rows;
    $circleid = ensure_unique_id($circleid, "circles", "circleid", $con);

    $columns = "`circleid`, `userid`, `title`, `forwhat`, `create_time`, `member_count`, `admin_count`";
    $values = "'$circleid', '$userid', '$title', '$forwhat',  STR_TO_DATE(\"{$datetime}\", \"%H:%i:%s,%m-%d-%Y\"), '0', '0'";
    insert_into("circles", $columns, $values, $con);

    // insert into members
    Circle::add_member($circleid, $userid, 0, $con);
    return $circleid;
  }

  public static function member_count_add_one($circleid, $admin, $con) {
    $restrict_str="WHERE `circleid`='$circleid'";
    $columns = array();
    $values = array();
    if ($admin) {
      $columns[] = "`admin_count`";
      $values[] = "`admin_count`+1";
    } else {
      $columns[] = "`member_count`";
      $values[] = "`member_count`+1";
    }
    update_table("circles", $columns, $values, $restrict_str, $con);
  }

  public static function member_count_subtract_one($circleid, $admin, $con) {
    $restrict_str="WHERE `circleid`='$circleid'";
    $columns = array();
    $values = array();
    if ($admin) {
      $columns[] = "`admin_count`";
      $values[] = "`admin_count`-1";
    } else {
      $columns[] = "`member_count`";
      $values[] = "`member_count`-1";
    }
    update_table("circles", $columns, $values, $restrict_str, $con);
  }

  // 0 - admin --> the ability to kick people out, and assign other people as admin
  // 1 - normal --> normal abilities: leave, invite others, see updates
  public static function add_member($circleid, $userid, $role, $con) {
    // For the sake of activity, we want to keep time consistent. So we will use PHP date() to get current time, and
    // use MYSQL's STR_TO_DATE() to convert it to MySQL datetime format
    $datetime = date("H:i:s,m-d-Y"); // the format is specified in activity.php:add_activity()

    $result = select_from("members", "*", "", $con);
    $memberid = "mem_" . $result->num_rows;
    $memberid = ensure_unique_id($memberid, "members", "memberid", $con);

    $columns = "`memberid`, `circleid`, `userid`, `role`, `join_time`";
    $values = "'$memberid', '$circleid', '$userid', '$role',  STR_TO_DATE(\"{$datetime}\", \"%H:%i:%s,%m-%d-%Y\")";
    insert_into("members", $columns, $values, $con);
    
    $admin = $role == 0;
    Circle::member_count_add_one($circleid, $admin, $con);
    return $memberid;
  }

  public static function remove_member($circleid, $userid, $con) {
    $admin = Circle::is_admin_of($circleid, $userid, $con);
    Circle::member_count_subtract_one($circleid, $admin, $con);
    delete_from("members", 
                "WHERE `circleid` = '$circleid' AND `userid` = '$userid'", '', $con);
  }

  public static function delete_circle($circleid, $con) {
    delete_from("circles", "WHERE `circleid` = '$circleid'", '', $con);
  }

  public static function is_admin_of($circleid, $userid, $con) {
    $result = select_from("members", "`role`", "WHERE `circleid` = '$circleid' AND `userid` = '$userid'", $con);
    while ($row = mysqli_fetch_assoc($result)) {
      return $row['role'] == 0;
    }
    return false;
  }

  public static function get_memberid($circleid, $userid, $con) {
    $result = select_from("members", "`memberid`", "WHERE `circleid` = '$circleid' AND `userid` = '$userid'", $con);
    while ($row = mysqli_fetch_assoc($result)) {
      return $row['memberid'];
    }
    return NULL;
  }

  // returns an array of userids of a particular circle, with a particular role
  // if $role is set to a negative number, then select all members
  // 0 - admin --> the ability to kick people out, and assign other people as admin
  // 1 - normal --> normal abilities: leave, invite others, see updates
  public static function get_members($circleid, $role, $con) {
    $restrict_str = "WHERE `circleid` = '$circleid' ";
    if ($role >= 0) {
      $restrict_str .= "AND `role` = '$role'";
    }

    $result = select_from("members", "`userid`", $restrict_str, $con);
    $id_arr = array();
    while ($row = mysqli_fetch_assoc($result)) {
      $id_arr[] = $row['userid'];
    }
    return $id_arr;
  }

  // Returns the number of members in a circle given its circleid
  // Specify the role of the member wanted:
  // 0 - admin
  // 1 - normal
  // Returns -1 if the role is invalid
  public static function num_members($circleid, $role, $con) {
    $result = "";
    if ($role == 0) {
      $result = select_from("circles", "`admin_count`", "WHERE `circleid` = '$circleid'", $con);
    } else if ($role == 1) {
      $result = select_from("circles", "`member_count`", "WHERE `circleid` = '$circleid'", $con);
    } else {
      return -1;
    }
    while ($row = mysqli_fetch_assoc($result)) {
      if ($role == 0) {
        return $row['admin_count'];
      } else {
        return $row['member_count'];
      }
    }
    return 0;
  }
}
?>
