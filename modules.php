<?php
/*
   Contains essentials modules for this app
   User, Card, Deck, Tag
 */
require_once "database.php";
require_once "functions.php";

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

  public function get_info() {
    return $this->info;
  }
 
  public function exist() {
    return $this->exist;
  }

  public function get_id() {
    return $this->userid;
  }

  // Adds a deck
  // $tags is an array containing the tags of this deck
  // Also inserts necessary entries to 'tags' table
  // Returns the deckid of the added deck
  public function add_deck($title, $tags, $con) {
    return Deck::add($title, $tags, $this->userid, $con);
  }

  // Adds a card to a specified user's deck
  // Returns the cardid of the added card
  public function add_card($title, $sub, $content, $deckid, $con) {
    return Card::add($title, $sub, $content, $deckid, $this->userid, $con);
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
} // end of User class

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

  // Adds a card to a deck
  // $title, $sub, $content are information of the card
  // Returns the cardid of the added card
  public static function add($title, $sub, $content, $deckid, $userid, $con) {
    $result=select_from("cards", "`cardid`", "", $con);
    $num_rows=$result->num_rows;

    $cardid='card_' . $num_rows;
    $cardid=ensure_unique_id($cardid, "cards", "cardid", $con);

    $columns="`cardid`,`title`,`sub`,`content`,";
    $columns.="`userid`,`deckid`,`create_time`,`deleted`";
    $values="'$cardid','$title','$sub','$content',"
           ."'$userid','$deckid',NOW(), '0'";
    insert_into("cards", $columns, $values, $con);
    return $cardid;
  }    
}

/*
   The Deck class
 */
/* 
   ----------------
   The Deck class
   ----------------
 */
class Deck
{
  private $deckid;
  private $info;
  private $exist;
  
  function __construct($deckid, $con) {
    $result = select_from("decks", "*", "WHERE `deckid` = '$deckid'", $con);
    $this->exist = $result->num_rows == 1;
    if ($this->exist) {
      $this->deckid = $deckid;
      $this->info = array();
      while ($rows = mysqli_fetch_assoc($result)) {
        $this->info = $rows; // In PHP, arrays are assigned in copy
        break; // only can be 1 match
      }
    }  
  }

  // static function for adding a deck
  // $tags is an array of tags of the deck
  // Returns the deckid of the added deck
  public static function add($title, $tags, $userid, $con) {
    $result = select_from('decks',"`deckid`","",$con);
    $num_rows = $result->num_rows;
    $deckid = 'deck_' . $num_rows;
    $deckid = ensure_unique_id($deckid, "decks", "deckid", $con);

    $columns="`deckid`,`title`,`userid`,`create_time`,`deleted`";
    $values="'$deckid','$title','$userid',NOW(), '0'";
    insert_into('decks', $columns, $values, $con);

    // add the tags to 'tags' table:
    Tag::add($tags, $deckid, $con);
    return $deckid;
  }
}

/* 
   ----------------
   The Tag class
   ----------------
 */
class Tag
{
  private $tagid;
  private $info;
  private $exist;
  
  function __construct($tagid, $con) {
    $result = select_from("tags", "*", "WHERE `tagid` = '$tagid'", $con);
    $this->exist = $result->num_rows == 1;
    if ($this->exist) {
      $this->tagid = $tagid;
      $this->info = array();
      while ($rows = mysqli_fetch_assoc($result)) {
        $this->info = $rows; // In PHP, arrays are assigned in copy
        break; // only can be 1 match
      }
    }  
  }

  // Adds an array of tags of a deck to 'tags' table
  public static function add($tags, $deckid, $con) {
    $result = select_from('tags', '*', "", $con);
    $count = $result->num_rows;
    for ($i = 0; $i < sizeof($tags); $i++)
    {
      $tagid = "tag_" . $count;
      $tagid = ensure_unique_id($tagid, "tags", "tagid", $con);
      $count++;
      
      // insert this tag-deckid relationship to the table
      $columns="`tagid`, `tag`, `deckid`, `deleted`";
      $values="'$tagid', '$tags[$i]', '$deckid', '0'";
      insert_into('tags', $columns, $values, $con);
    }
  }
}
?>