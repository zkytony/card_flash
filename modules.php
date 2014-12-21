<?php
/*
   Contains essentials modules for this app
   User, Card, Deck, Tag
 */
require_once "database.php";
require_once "functions.php";

class User
{
  private $userid;
  // An array that stores information about this user
  private $info; 
  private $exist;
  
  // fetch the user information from database using the userid
  function __construct($userid, $con) {
    $result = select_from("users", "*", "WHERE `userid` = '$userid'", $con);
    $this->exist = $result->num_rows == 1;
    if ($this->exist) {
      $this->userid = $userid;
      $this->info = array();
      while ($rows = mysqli_fetch_assoc($result)) {
        $this->info = $rows; // In PHP, arrays are assigned in copy
        break; // only can be 1 match
      }
    }
  }

  // logout current user by marking 'online' as '0'
  public function logout($con) {
    update_table("users", array("`online`"), array("'0'"), 
                 "WHERE `userid` = '$this->userid'", $con);
    $this->info['online'] = '0';
  }

  // log in this user
  // Differ from sign_in, which is a static function
  public function login($con) {
    update_table("users", array("`online`"), array("'1'"), 
                 "WHERE `userid` = '$this->userid'", $con);    
    $this->info['online'] = '1';
  }

  public function get_info() {
    return $this->info;
  }
 
  public function exist() {
    return $this->exist;
  }

  // Register a user with username and password. 
  // If more info is needed to register an user, we should modify this function.
  // NOTE: If the user exists but is inactivate, then we will register
  // the user by changing the password
  // ------------------------------
  // Remember to prevent SQL / HTMl injection in $username and $password
  // Returns true if registration is successful
  public static function register($username, $password, $con) {
    $column = "`username`, `activate`";
    $result = select_from('users', $column, "",  $con);

    $available = true;
    $change_password = false;
    while ($row = mysqli_fetch_assoc($result)) {
      if ($row['username'] == $username && $row['activate'] == true) {
        $available = false;
        break;
      } elseif ($row['username'] == $username && $row['activate'] == false) {
        $available = true;
        $change_password = true;
        break;
      }
    }

    if (!$available) {
      return false;
    } else {
      // register the user -- don't set online = 1 yet
      if (!$change_password) {
        $userid = 'user_' . $result->num_rows; // result has been obtained previously
        // ensure uniqueness
        $userid = ensure_unique_id($userid, "users", "userid", $con); 

        $columns = "`userid`,`username`,`password`,`register_time`,`activate`, `online`";
        $values = "'$userid','$username','$password', NOW(), '1', '0'";
        insert_into('users', $columns, $values, $con); // insert into 'users'
      } else {
        $columns = array("`password`", "`activate`", "`online`", "`register_time`");
        $values = array("'$password'", "'1'", "'0'", "NOW()");
        update_table('users', $columns, $values, "", $con);
      }
      return true;
    }
  }

  // Sign in a user. Returns a User object of that user if successful
  // Returns null object otherwise
  public static function sign_in($username, $password, $con) {
    $restrict_str = "WHERE username = '$username' AND password = '$password'";
    $columns = "`userid`, `activate`";
    $result = select_from("users", $columns, $restrict_str, $con);

    $success = false;
    $userid = "";
    while($row = mysqli_fetch_assoc($result)) {
      if ($row['activate']) {
        $success = true;
        $userid = $row['userid'];
      } else {
        $success = false;
      }
      break; // only can be 1 match
    }

    if ($success) {
      // make user online
      update_table("users", array("`online`"), array("'1'"), 
                   "WHERE `userid` = '$userid'", $con);
      return new User($userid, $con);
    } else {
      return NULL;
    }
  }

  // Input should be an instance of User class
  // Deactivates this user by marking activate as false
  public static function deactivate($username, $password, $con) {
    $columns = array("`activate`", "`online`");
    $values = array("'0'", "'0'");
    $restrict_str = "WHERE username = '$username' AND password = '$password'";
    update_table("users", $columns, $values, $restrict_str, $con);
  }

  // Input should be an instance of User class
  // Deletes this user from database
  public static function delete($username, $password, $con) {
    $restrict_str = "WHERE username = '$username' AND password = '$password'";
    delete_from("users", $restrict_str, 1, $con);
  }

  // Remember to prevent SQL / HTMl injection in $username and $password
  public static function check_exist_active($username, $password, $con) {
    $restrict_str = "WHERE username = '$username' AND password = '$password'";
    $result = select_from("users", "`userid`, `activate`", $restrict_str, $con);

    $success = false;
    while($rows = mysqli_fetch_assoc($result)) {
      if ($rows['activate']) {
        $success = true;
      } else {
        $success = false;
      }
      break; // only can be 1 match
    }
    return success;
  }
}
?>