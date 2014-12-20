<?php
/*
   Contains essentials modules for this app
   User, Card, Deck, Tag
 */
require_once "database.php";

class User
{
  private $userid;
  // An array that stores information about this user
  private $info; 
  private $exist;
  
  // fetch the user information from database using the userid
  function __construct($userid) {
    $con = connect();
    $result = select_from("users", "*", "WHERE `userid` = '$userid'", $con);
    $exist = $result->num_rows() == 1;
    if ($exist) {
      $info = array();
      while ($rows = mysqli_fetch_assoc($result)) {
        $info = $rows; // In PHP, arrays are assigned in copy
        break; // only can be 1 match
      }
    }
  }

  // Register a user with username and password. 
  // If more info is needed to register an user, we should modify this function.
  // NOTE: If the user exists but is inactivate, then we will register
  // the user by changing the password
  // ------------------------------
  // Remember to prevent SQL / HTMl injection in $username and $password
  // Returns true if registration is successful
  public static function register($username, $password, $con) {
    $column="`username`, `activate`";
    $result=select_from('users', $column, "",  $con);

    $available=true;
    $change_password=false;
    while ($row=mysqli_fetch_assoc($result)) {
      if ($row['username'] == $username && $row['activate'] == true) {
        $available=false;
        break;
      } elseif ($row['username'] == $username && $row['activate'] == false) {
        $available=true;
        $change_password=true;
        break;
      }
    }

    if (!$available) {
      return false;
    } else {
      if (!$change_password) {
        $userid=substr($username, 0, 3) . $result->num_rows; // result has been obtained previously
        // ensure uniqueness
        $userid=ensure_unique_id($userid, "users", "userid", $con); 

        $columns="`userid`,`username`,`password`,`register_time`,`activate`";
        $values="'$userid','$username','$password', NOW(), '1'";
        insert_into('users', $columns, $values, $con); // insert into 'users'
      } else {
        $columns=array("`password`", "`activate`", "`register_time`");
        $values=array("'$password'", "'1'", "NOW()");
        update_table('users', $columns, $values, "", $con);
      }
      return true;
    }
  }

  // Input should be an instance of User class
  // Deactivates this user by marking activate as false
  public static function deactivate(User $user, $con) {
    $columns = array("`activate`");
    $values = array("`0`");
    $restrict_str = "WHERE `userid` = '$user->userid'";
    update_table("users", $columns, $values, $restrict_str, $con);
  }

  // Input should be an instance of User class
  // Deletes this user from database
  public static function delete(User $user, $con) {
    delete_from("users", "WHERE `userid` = '$user->userid'", 1, $con);
  }

  // Remember to prevent SQL / HTMl injection in $username and $password
  public static function check_exist_active($username, $password, $con) {
    $restrict_str="WHERE username='$username' AND password='$password'";
    $result=select_from("users", "`userid`, `activate`", $restrict_str, $con);

    $success=false;
    while($rows=mysqli_fetch_assoc($result)) {
      if ($rows['activate']) {
        $success=true;
      } else {
        $success=false;
      }
      break; // only can be 1 match
    }
    return success;
  }
}
?>