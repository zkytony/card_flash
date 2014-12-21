<?php

/*
   Unit tests for classes in modules.php
 */

require_once "../modules.php";
require_once "../database.php";

/*
   Unit test for User class
   Note: keep the temp_flashcard clean before you test
 */
class UserTest extends PHPUnit_Framework_TestCase
{
  private $con;

  public function setUp() {
    $db = array (
      "hostname"=>"localhost",
      "database"=>"temp_flashcard",
      "username"=>"kaiyu",
      "password"=>"123abc",
    );
    $this->con = mysqli_connect($db['hostname'], $db['username'], 
                                $db['password'], $db['database']);
      
    init_tables($this->con);
  }
  
  /*
     Tests User::register() function in the case
     when the user is able to be registered
   */
  public function testRegsiterAvailble() {
    $username = 'user1';
    $password = 'dummy';
    $success = User::register($username, $password, $this->con);
    $this->assertEquals(true, $success);
    
    // see if this user is activate and online
    $result = select_from("users", "*", 
                          "WHERE `username` = '$username' AND `password` = '$password'", $this->con);
    while($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals('1', $row['activate']);
      $this->assertEquals('0', $row['online']);
    }

    // delete this user
    delete_from("users", "WHERE `username` = '$username' AND `password` = '$password'", '1', $this->con);
  }

  /*
     Tests User::register() function in the case
     when the user is not able to be registered because there
     exists user with same username and is activate
   */
  public function testRegisterUnavailable() {
    $username = 'user1';
    $password = 'dummy';
    $success = User::register($username, $password, $this->con);
    $this->assertEquals(true, $success);
    
    // register with same username again:
    $success = User::register($username, $password, $this->con);
    $this->assertEquals(false, $success);
    
    // delete this user
    delete_from("users", "WHERE `username` = '$username' AND `password` = '$password'", '1', $this->con);
  }

  /*
     Tests User::register() function in the case
     when the user is not able to be registered because there
     exists user with same username and is activate
   */
  public function testRegisterForNotActivateUsername() {
    $username = 'user1';
    $password = 'dummy';
    $success = User::register($username, $password, $this->con);
    $this->assertEquals(true, $success);
    
    // now deactivate the user
    User::deactivate($username, $password, $this->con);
    
    $result = select_from("users", "*", 
                          "WHERE `username` = '$username' AND `password` = '$password'", $this->con);
    while($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals('0', $row['activate']);
      $this->assertEquals('0', $row['online']);
    }

    // now register again -- should be successful
    $success = User::register($username, $password, $this->con);
    $this->assertEquals(true, $success);

    // delete this user
    delete_from("users", "WHERE `username` = '$username' AND `password` = '$password'", '1', $this->con);    
  }

  /*
     Test User::sign_in() If the user is registered
   */
  public function testSignInUserRegistered() {
    $username = 'user1';
    $password = 'dummy';
    $success = User::register($username, $password, $this->con);
    $this->assertEquals(true, $success);
    
    $user = User::sign_in($username, $password, $this->con);
    // assert if user is not null and is instance of User
    $this->assertEquals(false, is_null($user));
    $this->assertEquals(true, $user instanceof User);

    // assert if user exists, and is activate and online
    $this->assertEquals(true, $user->exist());
    $user_info = $user->getInfo();
    $this->assertEquals('1', $user_info['activate']);
    $this->assertEquals('1', $user_info['online']);

    // delete this user
    delete_from("users", "WHERE `username` = '$username' AND `password` = '$password'", '1', $this->con);    
  }
}

?>