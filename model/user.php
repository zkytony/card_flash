<?php

require_once "../database.php";
require_once "../functions.php";

/* 
   ----------------
   The User class
   ----------------
 */
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

  // refresh the object by re-obtain data from table
  public function refresh($con) {
    $result = select_from("users", "*", "WHERE `userid` = '$userid'", $con);
    $this->exist = $result->num_rows == 1;
    if ($this->exist) {     
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
 
  public function exist() {
    return $this->exist;
  }

  public function get_id() {
    return $this->userid;
  }

  public function update_current_deck($deckid, $con) {
    $restrict_str="WHERE `userid`='$this->userid'";
    update_table("users", array("`current_deckid`"), array("'$deckid'"), $restrict_str, $con);
    $this->info['deckid'] = $deckid;
  }

  // Adds a deck
  // $tags is an array containing the tags of this deck
  // Also inserts necessary entries to 'tags' table
  // Returns the deckid of the added deck
  public function add_deck($title, $tags, $con) {
    $deckid = Deck::add($title, $tags, $this->userid, $con);
    $this->update_current_deck($deckid, $con);
    return $deckid;
  }

  // Adds a card to a specified user's deck
  // Returns the cardid of the added card
  public function add_card($title, $sub, $content, $deckid, $type, $con) {
    return Card::add($title, $sub, $content, $deckid, $this->userid, $type, $con);
  }

  // Returns an array containing the Deck objects representing
  // the decks that this user has
  public function get_decks($active, $con) {
    return Deck::get_decks_of($this->userid, $active, $con);
  }

  // Register a user, with information provided as an array like this:
  // $info = array(
  //      'email' => email@email.com
  //      'first' => First Name
  //      'last' => Last Name
  //      'password' => Pswd
  //      'birth' => '%m-%d-%Y')
  // );
  // If more info is needed to register an user, we should modify this function.
  // NOTE: If the user exists but is inactivate, then we will register
  // the user by changing the password
  // ------------------------------
  // Remember to prevent SQL / HTMl injection in $email and $password
  // Returns true if registration is successful
  public static function register($info, $con) {
    $column = "`email`, `activate`";
    $result = select_from('users', $column, "",  $con);

    $available = true;
    $change_password = false;
    while ($row = mysqli_fetch_assoc($result)) {
      if ($row['email'] == $info['email'] && $row['activate'] == true) {
        $available = false;
        break;
      } elseif ($row['email'] == $info['email'] && $row['activate'] == false) {
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

        $columns = "`userid`,`email`,`first`,`last`,`password`,`birth`,`register_time`,`activate`, `online`, `followers`,`following`";
        $values = "'$userid','{$info['email']}','{$info['first']}','{$info['last']}','{$info['password']}',STR_TO_DATE(\"{$info['birth']}\", \"%m-%d-%Y\"), NOW(), '1', '0', '0', '0'";
        insert_into('users', $columns, $values, $con); // insert into 'users'
      } else {
        $columns = array("`first`","`last`","`password`", "`birth`", "`activate`", "`online`", "`register_time`", "`followers`", "`following`");
        $values = array("'{$info['first']}'", "'{$info['last']}'", "'{$info['password']}'", "STR_TO_DATE(\"{$info['birth']}\", \"%m-%d-%Y\")", "'1'", "'0'", "NOW()", "'0'", "'0'");
        update_table('users', $columns, $values, "", $con);
      }
      return true;
    }
  }

  // Sign in a user. Returns a User object of that user if successful
  // Returns null object otherwise
  public static function sign_in($email, $password, $con) {
    $restrict_str = "WHERE email = '$email' AND password = '$password'";
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
  // His decks will also be marked as deleted (### NEED SELECT JOIN)
  public static function deactivate($email, $password, $con) {
    $columns = array("`activate`", "`online`");
    $values = array("'0'", "'0'");
    $restrict_str = "WHERE email = '$email' AND password = '$password'";
    update_table("users", $columns, $values, $restrict_str, $con);
  }

  // Input should be an instance of User class
  // Deletes this user from database; Because of foreign key,
  // the assoiciated deck and cards should also be deleted
  public static function delete_completely($email, $password, $con) {
    $restrict_str = "WHERE email = '$email' AND password = '$password'";
    delete_from("users", $restrict_str, 1, $con);
  }

  // Remember to prevent SQL / HTMl injection in $email and $password
  public static function check_exist_active($email, $password, $con) {
    $restrict_str = "WHERE email = '$email' AND password = '$password'";
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
    return $success;
  }

  // Returns a userid with the email given
  // Throws an exception if obtained more than one userid
  public static function id_from_email($email, $con) {
    $result = select_from("users", "`userid`", "WHERE `email` = '$email'", $con);
    try {
      if ($result->num_rows > 1) {
        throw 45;
      } else if ($result->num_rows == 0) {
        return "";
      } else {
        while ($row = mysqli_fetch_assoc($result)) {
          return $row['userid'];
        }
      }
    } catch (int $ex) {
        echo "Error $ex: Duplicate email in database";
    }
  }

  // adds one to the number of followers this user has
  public static function follower_add_one($userid, $con) {
    $restrict_str="WHERE `userid`='$userid'";
    update_table("users", array("`followers`"), array("`followers`+1"), $restrict_str, $con);
  }

  // subtracts one to the number of followers this user has
  public static function follower_subtract_one($userid, $con) {
    $restrict_str="WHERE `userid`='$userid'";
    update_table("users", array("`followers`"), array("`followers`-1"), $restrict_str, $con);
  }

  // adds one to the number of followers this user has
  public static function following_add_one($userid, $con) {
    $restrict_str="WHERE `userid`='$userid'";
    update_table("users", array("`following`"), array("`following`+1"), $restrict_str, $con);
  }

  // subtracts one to the number of followers this user has
  public static function following_subtract_one($userid, $con) {
    $restrict_str="WHERE `userid`='$userid'";
    update_table("users", array("`following`"), array("`following`-1"), $restrict_str, $con);
  }

  // adds one to the number of decks subscribing this user has
  public static function subscribing_add_one($userid, $con) {
    $restrict_str="WHERE `userid`='$userid'";
    update_table("users", array("`subscribing`"), array("`subscribing`+1"), $restrict_str, $con);
  }

  // subtracts one to the decks subscribing this user has
  public static function subscribing_subtract_one($userid, $con) {
    $restrict_str="WHERE `userid`='$userid'";
    update_table("users", array("`subscribing`"), array("`subscribing`-1"), $restrict_str, $con);
  }

  // Returns the number of followers a user has
  public function num_followers($userid, $con) {
    $num = 0;
    $result = select_from("users", "`followers`", "WHERE `userid` = '$userid'", $con);
    while ($row = mysqli_fetch_assoc($result)) {
      $num = $result['followers'];
    }
    return $num;
  }

  // Returns the number of people that a user is following
  public function num_following($userid, $con) {
    $num = 0;
    $result = select_from("users", "`following`", "WHERE `userid` = '$userid'", $con);
    while ($row = mysqli_fetch_assoc($result)) {
      $num = $result['following'];
    }
    return $num;
  }

  // Returns the number of people that a user is subscribing
  public function num_subscribing($userid, $con) {
    $num = 0;
    $result = select_from("users", "`subscribing`", "WHERE `userid` = '$userid'", $con);
    while ($row = mysqli_fetch_assoc($result)) {
      $num = $result['subscribing'];
    }
    return $num;
  }

} // end of User class

?>