<?php 
require_once "../models.php";
require_once "../database.php";
require_once "../tables.php";

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
    $info = array(
        'email' => 'abc@123.com',
        'first' => 'Chen',
        'last' => 'Bomb',
        'password' => '123',
        'birth' => '03-02-1995'
    );
    $success = User::register($info, $this->con);
    $this->assertEquals(true, $success);
    
    // see if this user is activate and online
    $result = select_from("users", "*", 
                          "WHERE `email` = '{$info['email']}' AND `password` = '{$info['email']}'", $this->con);
    while($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals('1', $row['activate']);
      $this->assertEquals('abc@123.com', $row['email']);
      $this->assertEquals('Chen', $row['first']);
      $this->assertEquals('Bomb', $row['last']);
      $this->assertEquals('1959-03-02', $row['birth']);
    }

    // delete this user
    delete_from("users", "", '', $this->con);
  }

  /*
     Tests User::register() function in the case
     when the user is not able to be registered because there
     exists user with same email and is activate
   */
  public function testRegisterUnavailable() {
    $info = array(
        'email' => 'abc@123.com',
        'first' => 'Chen',
        'last' => 'Bomb',
        'password' => '123',
        'birth' => '03-02-1995'
    );
    $success = User::register($info, $this->con);
    $this->assertEquals(true, $success);
    
    // register with same email again:
    $success = User::register($info, $this->con);
    $this->assertEquals(false, $success);
    
    // delete this user
    delete_from("users", "", '', $this->con);
  }

  /*
     Tests User::register() function in the case
     when the user is not able to be registered because there
     exists user with same email and is activate
   */
  public function testRegisterForNotActivateEmail() {
    $info = array(
        'email' => 'abc@123.com',
        'first' => 'Chen',
        'last' => 'Bomb',
        'password' => '123',
        'birth' => '03-02-1995'
    );
    $success = User::register($info, $this->con);
    $this->assertEquals(true, $success);
    
    // now deactivate the user
    User::deactivate($info['email'], $info['password'], $this->con);
    
    $result = select_from("users", "*", 
                          "WHERE `email` = '{$info['email']}' AND `password` = '{$info['password']}'", $this->con);
    while($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals('0', $row['activate']);
      $this->assertEquals('0', $row['online']);
    }

    // now register again -- should be successful
    $success = User::register($info, $this->con);
    $this->assertEquals(true, $success);

    // delete this user
    delete_from("users", "", "", $this->con);
  }

  /*
     Test User::sign_in() If the user is registered
   */
  public function testSignInUserRegistered() {
    $info = array(
        'email' => 'abc@123.com',
        'first' => 'Chen',
        'last' => 'Bomb',
        'password' => '123',
        'birth' => '03-02-1995'
    );
    $success = User::register($info, $this->con);
    $this->assertEquals(true, $success);
    
    $user = User::sign_in($info['email'], $info['password'], $this->con);
    // assert if user is not null and is instance of User
    $this->assertEquals(false, is_null($user));
    $this->assertEquals(true, $user instanceof User);

    // assert if user exists, and is activate and online
    $this->assertEquals(true, $user->exist());
    $user_info = $user->get_info();
    $this->assertEquals('1', $user_info['activate']);
    $this->assertEquals('1', $user_info['online']);

    // delete this user
    delete_from("users", "", '', $this->con);    
  }

  public function testLogOut() {
    $info = array(
        'email' => 'abc@123.com',
        'first' => 'Chen',
        'last' => 'Bomb',
        'password' => '123',
        'birth' => '03-02-1995'
    );
    $success = User::register($info, $this->con);
    $this->assertEquals(true, $success);

    $user = User::sign_in($info['email'], $info['password'], $this->con);
    $user->logout($this->con);
    $this->assertEquals('0', $user->get_info()['online']);
  
    $result = select_from("users", "*", 
                          "WHERE `email` = '{$info['email']}' AND `password` = '{$info['email']}'", $this->con);
    while($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals('0', $row['online']);
    }

    // delete this user
    delete_from("users", "", '', $this->con);
  }

  public function testIdFromName() {
    $info = array(
        'email' => 'abc@123.com',
        'first' => 'Chen',
        'last' => 'Bomb',
        'password' => '123',
        'birth' => '03-02-1995'
    );
    $success = User::register($info, $this->con);
    $this->assertEquals(true, $success);
    $user = User::sign_in($info['email'], $info['password'], $this->con);
    $userid_exp = $user->get_id();
    
    $userid_get = User::id_from_email($info['email'], $this->con);
    $this->assertEquals($userid_exp, $userid_get);
    // delete this user
    delete_from("users", "", '', $this->con);    
  }

  public function tearDown() {
    delete_from("users", "", "", $this->con);
  }
}
?>
