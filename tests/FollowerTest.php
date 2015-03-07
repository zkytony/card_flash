<?php 
require_once "../models.php";
require_once "../database.php";
require_once "../tables.php";

/* Test class for user following functionality */
class FollowerTest extends PHPUnit_Framework_Testcase 
{
  private $con;
  private $user1;
  private $user2;
  private $user3;

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

    // create user1
    $info1 = array(
      'email' => 'abc@123.com',
      'first' => 'Wa',
      'last' => 'Haha',
      'password' => 'howoow3',
      'birth' => '03-02-1988'
    );
    $success_1 = User::register($info1, $this->con);
    $this->assertEquals(true, $success_1);
    $this->user1 = User::sign_in($info1['email'], $info1['password'], $this->con);

    // create user2
    $info2 = array(
      'email' => 'brer@gmail.com',
      'first' => 'Kobe',
      'last' => 'Bryant',
      'password' => 'dsbi3e',
      'birth' => '08-08-1978'
    );
    $success_2 = User::register($info2, $this->con);
    $this->assertEquals(true, $success_2);
    $this->user2 = User::sign_in($info2['email'], $info2['password'], $this->con);

    // create user3
    $info3 = array(
      'email' => '23i545@joke.dd.dsb.com',
      'first' => 'Chris',
      'last' => 'Jam',
      'password' => '1u3425',
      'birth' => '07-12-1967'
    );
    $success_3 = User::register($info3, $this->con);
    $this->assertEquals(true, $success_3);
    $this->user3 = User::sign_in($info3['email'], $info3['password'], $this->con);

    // let user1 and user3 follow user2
    $flwrid_user1 = Follower::follow($this->user2->get_id(), $this->user1->get_id(), $this->con);
    $flwrid_user3 = Follower::follow($this->user2->get_id(), $this->user3->get_id(), $this->con);
  }

  public function testFollow() {
    // see if the number of followers is correct for user2
    $result = select_from("users", "*", "WHERE `userid` = '{$this->user2->get_id()}'", $this->con);
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals(2, $row['followers']);
    }

    // if the nubmer of following is correct for user1 and user3
    $result = select_from("users", "*", "WHERE `userid` = '{$this->user1->get_id()}'", $this->con);
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals(1, $row['following']);
    }

    $result = select_from("users", "*", "WHERE `userid` = '{$this->user3->get_id()}'", $this->con);
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals(1, $row['following']);
    }
  }

  public function testUserFollowers() {
    // see if the user1 and user2 are added as followers to the table
    $followers_user2 = Follower::followers($this->user2->get_id(), $this->con);
    $hasUser1 = in_array($this->user1->get_id(), $followers_user2);
    $hasUser3 = in_array($this->user3->get_id(), $followers_user2);
    $this->assertEquals(true, $hasUser1);
    $this->assertEquals(true, $hasUser3);    
  }

  public function testUserFollowing() {
    $following_user1 = Follower::following($this->user1->get_id(), $this->con);
    $hasUser2 = in_array($this->user2->get_id(), $following_user1);
    $this->assertEquals(true, $hasUser2);

    $following_user3 = Follower::following($this->user3->get_id(), $this->con);
    $hasUser2 = in_array($this->user2->get_id(), $following_user1);
    $this->assertEquals(true, $hasUser2);
  }

  public function testUnfollow() {
    // user1 unfollows user2
    Follower::unfollow($this->user2->get_id(), $this->user1->get_id(), $this->con);

    // see if the number of followers is correct for user2
    $result = select_from("users", "*", "WHERE `userid` = '{$this->user2->get_id()}'", $this->con);
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals(1, $row['followers']);
    }

    // if the nubmer of following is correct for user1
    $result = select_from("users", "*", "WHERE `userid` = '{$this->user1->get_id()}'", $this->con);
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals(0, $row['following']);
    }

    // make sure user2 does not have user1 as a follower any more
    $followers_user2 = Follower::followers($this->user2->get_id(), $this->con);
    $hasUser1 = in_array($this->user1->get_id(), $followers_user2);
    $hasUser3 = in_array($this->user3->get_id(), $followers_user2);
    $this->assertEquals(false, $hasUser1);
    $this->assertEquals(true, $hasUser3);    

    // make sure user1 is not following user2 any more
    $following_user1 = Follower::following($this->user1->get_id(), $this->con);
    $hasUser2 = in_array($this->user2->get_id(), $following_user1);
    $this->assertEquals(false, $hasUser2);
  }

  public function tearDown() {
    // delete followers
    delete_from("followers", "", "", $this->con);
    // delete this user
    delete_from("users", "", "", $this->con);
  }
}
?>
