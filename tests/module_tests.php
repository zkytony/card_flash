<?php

require_once "../modules.php";
require_once "../database.php";

class UserTest extends PHPUnit_Framework_TestCase
{
  private $con;

  public function setUp() {
    $this->con = connect();
    init_tables($this->con);
  }
  
  public function testRegsiterAvailble() {
    $this->con = connect();
    $username = 'user1';
    $password = 'dummy';
    $success = User::register($username, $password, $this->con);
    $this->assertEqual($success, true);
    
    // see if this user is activate and online
    $result = select_from("users", "*", 
                          "WHERE `username` = '$username' AND `password` = '$password'", $this->con);
    while($row = mysqli_fetch_assoc($result)) {
      $this->assertEqual($row['activate'], '1');
      $this->assertEqual($row['online'], '1');
    }
  }
}

?>