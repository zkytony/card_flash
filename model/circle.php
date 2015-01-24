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
    $result = select_from("circles", "*", "WHERE `circleid` = '$circleid'", $con);
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
    // insert into circles

    $result = select_from("circles", "*", "", $con);
    $circleid = "crc_" . $result->num_rows;
    $circleid = ensure_unique_id($circleid, "circles", "circleid", $con);

    $columns = "`circleid`, `userid`, `title`, `forwhat`, `create_time`, `member_count`, `admin_count`";
    $values = "'$circleid', '$userid', '$title', '$forwhat', NOW(), '1', '1'");
    insert_into("circles", $columns, $values, $con);

    // insert into members

    $result = select_from("members", "*", "", $con);
    $memberid = "crc_" . $result->num_rows;
    $memberid = ensure_unique_id($memberid, "members", "memberid", $con);

    $columns = "`memberid`, `circleid`, `userid`, `role`, `join_time`, `last_activity_time`";
    $values = "'$memberid', '$circleid', '$userid', '0', NOW(), NOW()";
    insert_into("members", $columns, $values, $con);
    
    Circle::member_count_add_one($circleid, true, $con);
  }

  public static function member_count_add_one($circleid, $admin, $con) {
    $restrict_str="WHERE `circleid`='$circleid'";
    $columns = array("`member_count`");
    $values = array("`member_count`+1");
    if ($admin) {
      $columns[] = "`admin_count`";
      $values[] = "`admin_count`+1");
    }
    update_table("users", $columns, $values, $restrict_str, $con);
  }

  public static function member_count_subtract_one($circleid, $admin, $con) {
    $restrict_str="WHERE `circleid`='$circleid'";
    $columns = array("`member_count`");
    $values = array("`member_count`-1");
    if ($admin) {
      $columns[] = "`admin_count`";
      $values[] = "`admin_count`-1");
    }
    update_table("users", $columns, $values, $restrict_str, $con);
  }

  public static function add_member($circleid, $userid, $role, $con) {
    $result = select_from("members", "*", "", $con);
    $memberid = "crc_" . $result->num_rows;
    $memberid = ensure_unique_id($memberid, "members", "memberid", $con);

    $columns = "`memberid`, `circleid`, `userid`, `role`, `join_time`, `last_activity_time`";
    $values = "'$memberid', '$circleid', '$userid', '$role', NOW(), NOW()";
    insert_into("members", $columns, $values, $con);
    
    $admin = false;
    if ($role == '0') $admin = true;
    Circle::member_count_add_one($circleid, $admin, $con);
  }

  public static function remove_member($circleid, $userid, $con) {
    delete_from("members", 
                "WHERE `circleid` = '$circleid' AND `userid` = '$userid'", $con);
    $admin = Circle::is_admin_of($circleid, $userid, $con);
    Circle::member_count_subtract_one($circleid, $admin, $con);
  }

  public static function delete_circle($circleid, $con) {
    delete_from("circles", "WHERE `circleid` = '$circleid'", $con);
  }

  public static function is_admin_of($circleid, $userid, $con) {
    $result = select_from("members", "`role`", "WHERE `circleid` = '$circleid' AND `userid` = '$userid'", $con);
    while ($row = mysqli_fetch_assoc($result)) {
      return $row['role'] == 0;
    }
    return false;
  }

}
?>