<?php

/*
   Unit tests for classes in models.php
 */

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

class DeckAndCardTest extends PHPUnit_Framework_TestCase
{
  private $con;
  private $user;

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

    $info = array(
        'email' => 'abc@123.com',
        'first' => 'Chen',
        'last' => 'Bomb',
        'password' => '123',
        'birth' => '03-02-1995'
    );
    $success = User::register($info, $this->con);
    $this->assertEquals(true, $success);
    $this->user = User::sign_in($info['email'], $info['password'], $this->con);
  }
  
  public function testAddDeck() {
    $title = "Deck1";
    $tags = array("aaa", "bbb", "ccc");
    
    $deckid = $this->user->add_deck($title, $tags, true, $this->con);
    $result = select_from("decks", "*", "WHERE `deckid` = '$deckid'", $this->con);
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals($title, $row['title']);
      $this->assertEquals($this->user->get_id(), $row['userid']);
    }

    // see if `deckid` in users is updated
    $userid = $this->user->get_id();
    $result = select_from("users", "*", 
                          "WHERE `userid` = '$userid'", $this->con);
    while($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals($deckid, $row['current_deckid']);
    }

    $this->assertEquals($deckid, $this->user->get_info()['deckid']);

    $result = select_from("tags", "*", "WHERE `deckid` = '$deckid' ORDER BY `tag`", $this->con);
    $i = 0; // index
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals($tags[$i], $row['tag']);
      $i++;
    }    

    // delete this deck
    delete_from("decks", "WHERE `deckid` = '$deckid'", '1', $this->con);
    // delete the tags
    delete_from("tags", "WHERE `deckid` = '$deckid'", '', $this->con);
  }

  public function testAddCard() {
    // add the deck first
    $title = "Deck1";
    $tags = array("aaa", "bbb", "ccc");
    $deckid = $this->user->add_deck($title, $tags, true, $this->con);

    $title = "Card99";
    $sub = "One card";
    $content = "<h1>Hi</h1>";
    $cardid = $this->user->add_card($title, $sub, $content, $deckid, 0, NULL, $this->con);

    $result = select_from("cards", "*", "WHERE `cardid` = '$cardid'", $this->con);
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals($title, $row['title']);
      $this->assertEquals($sub, $row['sub']);
      $this->assertEquals($content, $row['content']);
    }
    
    // delete this deck
    delete_from("decks", "WHERE `deckid` = '$deckid'", '1', $this->con);
    // delete the tags
    delete_from("tags", "WHERE `deckid` = '$deckid'", '', $this->con);
    // delete this card
    delete_from("cards", "WHERE `cardid` = '$cardid'", '1', $this->con);
  }

  public function testUserGetDecks() {
    // add the deck first
    $title = "Deck1";
    $tags = array("aaa", "bbb", "ccc");
    $deckid = $this->user->add_deck($title, $tags, true, $this->con);

    $decks = $this->user->get_decks(true, $this->con);
    foreach ($decks as $deck) {
      $this->assertEquals($deckid, $deck->get_id());
    }

    // delete this deck
    delete_from("decks", "WHERE `deckid` = '$deckid'", '1', $this->con);
    // delete the tags
    delete_from("tags", "WHERE `deckid` = '$deckid'", '', $this->con);

  }

  public function testGetDecksWithTag() {
    // add the deck 1
    $title = "Deck1";
    $tags = array("aaa", "bbb", "ccc");
    $deckid_1 = $this->user->add_deck($title, $tags, true, $this->con);

    // add the deck 2
    $title = "Deck2";
    $tags = array("aaa", "bbb", "ddd");
    $deckid_2 = $this->user->add_deck($title, $tags, true, $this->con);

    // add the deck 3
    $title = "Deck3";
    $tags = array("aaa", "ccc", "eee");
    $deckid_3 = $this->user->add_deck($title, $tags, true, $this->con);

    $deckids_aaa = Tag::get_decks_with_tag("aaa", $this->con);
    $deckids_aaa_exp = array($deckid_1, $deckid_2, $deckid_3);
    $this->assertEquals($deckids_aaa_exp, $deckids_aaa);

    $deckids_ccc = Tag::get_decks_with_tag("ccc", $this->con);
    $deckids_ccc_exp = array($deckid_1, $deckid_3);
    $this->assertEquals($deckids_ccc_exp, $deckids_ccc);
    
    // delete decks
    delete_from("decks", "", "", $this->con);
    // delete tags
    delete_from("tags", "", "", $this->con);
  }

  public function testDeleteDeck() {
    // add the deck 1
    $title = "Deck1";
    $tags = array("aaa", "bbb", "ccc");
    $deckid = $this->user->add_deck($title, $tags, true, $this->con);
    
    // add two cards to deck1
    $title = "Card99";
    $sub = "One card";
    $content = "<h1>Hi</h1>";
    $cardid = $this->user->add_card($title, $sub, $content, $deckid, 1, NULL, $this->con);

    $title = "Card100";
    $sub = "One card";
    $content = "<h1>Heya</h1>";
    $cardid = $this->user->add_card($title, $sub, $content, $deckid, 0, NULL, $this->con);

    $success = Deck::delete($deckid, $this->con);
    $result=select_from("decks", "*", 
                        "WHERE `deckid` = '$deckid'", $this->con);
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals(true, $row['deleted']);
    }

    // see if cards are marked
    $result=select_from("cards", "*", 
                        "WHERE `deckid` = '$deckid'", $this->con);
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals(true, $row['deleted']);
    }

    // see if tags are marked
    $result=select_from("tags", "*", 
                        "WHERE `deckid` = '$deckid'", $this->con);
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals(true, $row['deleted']);
    }

    // delete decks
    delete_from("decks", "", "", $this->con);
    // delete cards
    delete_from("cards", "", "", $this->con);
    // delete tags
    delete_from("tags", "", "", $this->con);
  }

  public function tearDown() {
    delete_from("cards", "", '', $this->con);
    delete_from("tags", "", '', $this->con);
    delete_from("decks", "", '', $this->con);
    delete_from("users", "", '', $this->con);
  }
}

/* Test class for deck sharing functionality */
class ShareTest extends PHPUnit_Framework_Testcase
{
  private $con;
  private $user1;
  private $user2;
  private $deckids;

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
    $info1 = array(
        'email' => 'abc@123.com',
        'first' => 'Chen',
        'last' => 'Bomb',
        'password' => '123',
        'birth' => '03-02-1995'
    );
    $success_1 = User::register($info1, $this->con);
    $this->assertEquals(true, $success_1);
    $this->user1 = User::sign_in($info1['email'], $info1['password'], $this->con);

    $info2 = array(
        'email' => 'wft@123.com',
        'first' => 'James',
        'last' => 'Yak',
        'password' => '123',
        'birth' => '11-30-1932'
    );
    $success_2 = User::register($info2, $this->con);
    $this->assertEquals(true, $success_2);
    $this->user2 = User::sign_in($info2['email'], $info2['password'], $this->con);

    // add the decks -- they are all user2's deck
    $this->deckids = array();
    // add the deck 1
    $title = "Deck1";
    $tags = array("aaa", "bbb", "ccc");
    $this->deckids[] = $this->user2->add_deck($title, $tags, true, $this->con);

    // add the deck 2
    $title = "Deck2";
    $tags = array("aaa", "bbb", "ddd");
    $this->deckids[] = $this->user2->add_deck($title, $tags, true, $this->con);

    // add the deck 3
    $title = "Deck3";
    $tags = array("aaa", "ccc", "eee");
    $this->deckids[] = $this->user2->add_deck($title, $tags, true, $this->con);
  }

  public function testShareToNew() {
    $shareid_1 = Share::share_to($this->deckids[0], $this->user1->get_id(), 1, NULL, $this->con);
    $shareid_2 = Share::share_to($this->deckids[1], $this->user1->get_id(), 2, NULL, $this->con);
    $shareid_3 = Share::share_to($this->deckids[2], $this->user1->get_id(), 1, NULL, $this->con);

    $deckids_get = Share::shared_decks($this->user1->get_id(), 1, $this->con);
    $deckids_exp = array($this->deckids[0], $this->deckids[2]);

    $this->assertEquals($deckids_exp, $deckids_get);

    $userids_get = Share::shared_users($this->deckids[1], 2, $this->con);
    $userids_exp = array($this->user1->get_id());
    $this->assertEquals($userids_exp, $userids_get);
    
    // test if update works
    $shareid_2_new = Share::share_to($this->deckids[1], $this->user1->get_id(), 1, NULL, $this->con);
    $this->assertEquals($shareid_2, $shareid_2_new);
    $deckids_get = Share::shared_decks($this->user1->get_id(), 1, $this->con);
    $deckids_exp = array($this->deckids[0], $this->deckids[1], $this->deckids[2]);
    $this->assertEquals($deckids_exp, $deckids_get);
  }

  public function testUnshare() {
    $shareid_1 = Share::share_to($this->deckids[0], $this->user1->get_id(), 1, NULL, $this->con);
    $shareid_2 = Share::share_to($this->deckids[1], $this->user1->get_id(), 2, NULL, $this->con);
    $shareid_3 = Share::share_to($this->deckids[2], $this->user1->get_id(), 1, NULL, $this->con);

    Share::unshare($this->deckids[0], $this->user1->get_id(), $this->con);
    $status = Share::check_status($this->deckids[0], $this->user1->get_id(), $this->con);
    $this->assertEquals(0, $status);
  }

  public function testShareToOwner() {
    $shareid = Share::share_to($this->deckids[0], $this->user2->get_id(), 1, NULL, $this->con);
    $this->assertEquals(NULL, $shareid);
  }

  public function testForeignKeyConstrainDeleteDeck() {
    $shareid_1 = Share::share_to($this->deckids[0], $this->user1->get_id(), 1, NULL, $this->con);
    $shareid_2 = Share::share_to($this->deckids[1], $this->user1->get_id(), 2, NULL, $this->con);
    $shareid_3 = Share::share_to($this->deckids[2], $this->user1->get_id(), 1, NULL, $this->con);

    // If Deck1 is deleted
    Deck::delete_completely($this->deckids[0], $this->con);

    $result = select_from("shares", "*", "WHERE `shareid` = '$shareid_1'", $this->con);
    $deleted = true;
    while ($row = mysqli_fetch_assoc($result)) {
      $deleted = false;
    }
    $this->assertTrue($deleted);

    // add the deck 1 back
    $title = "Deck1";
    $tags = array("aaa", "bbb", "ccc");
    $this->deckids[0] = $this->user2->add_deck($title, $tags, true, $this->con);

  }

  public function testForeignKeyConstrainDeleteUserSharedTo() {
    // notice, because of the previous test, deckids[0] here may be different from the previous
    $shareid_1 = Share::share_to($this->deckids[0], $this->user1->get_id(), 1, NULL, $this->con);
    $shareid_2 = Share::share_to($this->deckids[1], $this->user1->get_id(), 2, NULL, $this->con);
    $shareid_3 = Share::share_to($this->deckids[2], $this->user1->get_id(), 1, NULL, $this->con);

    // If User1 is deleted
    User::delete_completely($this->user1->get_info()['email'], $this->user1->get_info()['password'], $this->con);

    $result = select_from("shares", "*", "WHERE `shareid` = '$shareid_1'", $this->con);
    $deleted = true;
    while ($row = mysqli_fetch_assoc($result)) {
      $deleted = false;
    }
    $this->assertTrue($deleted);

    // register the user1 back again
    $info1 = array(
        'email' => 'abc@123.com',
        'first' => 'Chen',
        'last' => 'Bomb',
        'password' => '123',
        'birth' => '03-02-1995'
    );
    $success_1 = User::register($info1, $this->con);
    $this->assertEquals(true, $success_1);
    $this->user1 = User::sign_in($info1['email'], $info1['password'], $this->con);
  }

  public function testForeignKeyConstrainDeleteUserOwnDeck() {
    // notice, because of the previous test, deckids[0] here may be different from the previous
    $shareid_1 = Share::share_to($this->deckids[0], $this->user1->get_id(), 1, NULL, $this->con);
    $shareid_2 = Share::share_to($this->deckids[1], $this->user1->get_id(), 2, NULL, $this->con);
    $shareid_3 = Share::share_to($this->deckids[2], $this->user1->get_id(), 1, NULL, $this->con);

    // If User2 is deleted; User2 is the owner of the deck
    User::delete_completely($this->user2->get_info()['email'], $this->user2->get_info()['password'], $this->con);

    $result = select_from("shares", "*", "WHERE `shareid` = '$shareid_1'", $this->con);
    $deleted = true;
    while ($row = mysqli_fetch_assoc($result)) {
      $deleted = false;
    }
    $this->assertTrue($deleted);

    // register user2 back again
    $info2 = array(
        'email' => 'wft@123.com',
        'first' => 'James',
        'last' => 'Yak',
        'password' => '123',
        'birth' => '11-30-1932'
    );
    $success_2 = User::register($info2, $this->con);
    $this->assertEquals(true, $success_2);
    $this->user2 = User::sign_in($info2['email'], $info2['password'], $this->con);
  }  

  public function tearDown() {
    // delete this user
    delete_from("users", "", "", $this->con);
    // delete decks
    delete_from("decks", "", "", $this->con);
    // delete tags
    delete_from("tags", "", "", $this->con);
  }

}

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

/* Test class for user subscribing functionality */
class SubscriberTest extends PHPUnit_Framework_Testcase 
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

    // add the decks -- they are all user2's deck
    $this->deckids = array();
    // add the deck 1
    $title = "Deck1";
    $tags = array("aaa", "bbb", "ccc");
    $this->deckids[] = $this->user2->add_deck($title, $tags, true, $this->con);

    // add the deck 2
    $title = "Deck2";
    $tags = array("aaa", "bbb", "ddd");
    $this->deckids[] = $this->user2->add_deck($title, $tags, true, $this->con);

    // add the deck 3
    $title = "Deck3";
    $tags = array("ccc", "bbb", "ddd");
    $this->deckids[] = $this->user2->add_deck($title, $tags, false, $this->con);

    // let user1 and user3 subscribe to deck1, and user3 subscribe to deck2
    $this->sbrid1 = Subscriber::subscribe($this->deckids[0], $this->user1->get_id(), NULL, $this->con);
    $this->sbrid2 = Subscriber::subscribe($this->deckids[0], $this->user3->get_id(), NULL, $this->con);
    $this->sbrid3 = Subscriber::subscribe($this->deckids[1], $this->user3->get_id(), NULL, $this->con);
  }

  public function testSubscribeTable() {
    $result = select_from("subscribers", "*", "", $this->con);
    $has1 = false; // has sbrid1
    $has2 = false; // ...
    $has3 = false;
    while ($row = mysqli_fetch_assoc($result)) {
      if ($row['sbrid'] == $this->sbrid1) {
        $has1 = true;
        $this->assertEquals($this->deckids[0], $row['deckid']);
        $this->assertEquals($this->user1->get_id(), $row['sbr_userid']);
      } else if ($row['sbrid'] == $this->sbrid2) {
        $has2 = true;
        $this->assertEquals($this->deckids[0], $row['deckid']);
        $this->assertEquals($this->user3->get_id(), $row['sbr_userid']);
      } else if ($row['sbrid'] == $this->sbrid3) {
        $has3 = true;
        $this->assertEquals($this->deckids[1], $row['deckid']);
        $this->assertEquals($this->user3->get_id(), $row['sbr_userid']);
      }
    }
    $this->assertEquals(true, $has1);
    $this->assertEquals(true, $has2);
    $this->assertEquals(true, $has3);
  }

  public function testSubscribeUnopenDeck() {
    // deck3 is unopen, try to let user1 to subscribe it
    $sbrid = Subscriber::subscribe($this->deckids[2], $this->user1->get_id(), NULL, $this->con);
    // should return null
    $this->assertEquals(NULL, $sbrid);
  }

  public function testDeckAndUserSubscribingCount() {
    $sbg_num1 = User::num_subscribing($this->user1->get_id(), $this->con);
    $sbg_num2 = User::num_subscribing($this->user2->get_id(), $this->con);
    $sbg_num3 = User::num_subscribing($this->user3->get_id(), $this->con);

    $this->assertEquals(1, $sbg_num1); // sbg == subscribing
    $this->assertEquals(0, $sbg_num2);
    $this->assertEquals(2, $sbg_num3);

    $sb_num1 = Deck::num_subscribers($this->deckids[0], $this->con);
    $sb_num2 = Deck::num_subscribers($this->deckids[1], $this->con);
    $sb_num3 = Deck::num_subscribers($this->deckids[2], $this->con);

    $this->assertEquals(2, $sb_num1);
    $this->assertEquals(1, $sb_num2);
    $this->assertEquals(0, $sb_num3);
  }

  public function testUnsubscribe() {
    Subscriber::unsubscribe($this->deckids[0], $this->user1->get_id(), $this->con);

    $sbg_num1 = User::num_subscribing($this->user1->get_id(), $this->con);
    $this->assertEquals(0, $sbg_num1); // sbg == subscribing

    $sb_num1 = Deck::num_subscribers($this->deckids[0], $this->con);
    $this->assertEquals(1, $sb_num1);
  }

  public function tearDown() {
    delete_from("subscribers", "", "", $this->con);
    delete_from("decks", "", "", $this->con);
    delete_from("users", "", "", $this->con);
  }
}

// Unit test for Circle
class CircleTest extends PHPUnit_Framework_Testcase 
{
  private $con;
  private $user1;
  private $user2;
  private $user3;

  /*
   * @before
   */
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

    // Let user1 create a circle called "Jobs"
    $this->circleid1 = Circle::create($this->user1->get_id(), "Jobs", "A big shit", $this->con);   

    // add user2 to the circle as normal meber
    $this->memberid_user2 = Circle::add_member($this->circleid1, $this->user2->get_id(), 1, $this->con);

    // add user3 to the circle as normal meber
    $this->memberid_user3 = Circle::add_member($this->circleid1, $this->user3->get_id(), 0, $this->con);
  }
 
  public function testCircleCreate() {
    // test Circle create:
    $result = select_from("circles", "*", "WHERE `circleid` = '$this->circleid1'", $this->con);
    $this->assertTrue($result->num_rows == 1);
  }
  
  public function testAddMemberNormal() {
    $result = select_from("members", "*", "WHERE `memberid` = '$this->memberid_user2'", $this->con);
    $this->assertTrue($result->num_rows == 1);
  }

  public function testAddMemberAdmin() {
    $result = select_from("members", "*", "WHERE `memberid` = '$this->memberid_user3'", $this->con);
    $this->assertTrue($result->num_rows == 1);
  }

  // from the previous test, user2 should be a normal member to the circle
  public function testGetMembersNormal() {
    $arr = Circle::get_members($this->circleid1, 1, $this->con);
    $this->assertEquals(array($this->user2->get_id()), $arr);
  }

  // from the previous test, user3 should be a admin member to the circle
  // along with user1, the creator
  public function testGetMembersAdmin() {
    $arr = Circle::get_members($this->circleid1, 0, $this->con);
    $hasUser1 = false;
    $hasUser3 = false;
    for ($i = 0; $i < sizeof($arr); $i++) {
      if ($arr[$i] == $this->user1->get_id()) {
        $hasUser1 = true;
      }
      if ($arr[$i] == $this->user3->get_id()) {
        $hasUser3 = true;
      }
    }
    $this->assertTrue($hasUser1);
    $this->assertTrue($hasUser3);
  }

  public function testNumberOfMembers() {
    $norm = Circle::num_members($this->circleid1, 1, $this->con);
    $admin = Circle::num_members($this->circleid1, 0, $this->con);
    $this->assertEquals(1, $norm);
    $this->assertEquals(2, $admin);
  }

  public function testRemoveMember() {
    // Remove user3, who should be an admin
    Circle::remove_member($this->circleid1, $this->user3->get_id(), $this->con);
    $result = select_from("members", "*", 
                          "WHERE `circleid` = '$this->circleid1' AND `userid` = '{$this->user3->get_id()}'", $this->con);
    $this->assertEquals(0, $result->num_rows);

    $admin = Circle::num_members($this->circleid1, 0, $this->con);
    $this->assertEquals(1, $admin);
  }

  public function testDeleteCircle() {
    Circle::delete_circle($this->circleid1, $this->con);
    $result = select_from("circles", "*", "WHERE `circleid` = '$this->circleid1'", $this->con);
    $this->assertTrue($result->num_rows == 0);
    
    $norm = Circle::num_members($this->circleid1, 1, $this->con);
    $admin = Circle::num_members($this->circleid1, 0, $this->con);
    $this->assertEquals(0, $norm);
    $this->assertEquals(0, $admin);

    $arr_admin = Circle::get_members($this->circleid1, 0, $this->con);
    $this->assertEquals(0, sizeof($arr_admin));

    $arr_norm = Circle::get_members($this->circleid1, 1, $this->con);
    $this->assertEquals(0, sizeof($arr_norm));
  }

  /*
   * @after
   */
  public function tearDown() {
    delete_from("users", '', '', $this->con);
    delete_from("circles", '', '', $this->con);
  }
}

class ActivityTest extends PHPUnit_Framework_Testcase {

  private $con;
  private $user1;
  private $user2;
  private $user3;

  /*
   * @before
   */
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

    // Let user1 create a circle called "Jobs"
    $this->circleid1 = Circle::create($this->user1->get_id(), "Jobs", "A big shit", $this->con);   

    // add user2 to the circle as normal meber
    $this->memberid_user2 = Circle::add_member($this->circleid1, $this->user2->get_id(), 1, $this->con);

    // add user3 to the circle as normal meber
    $this->memberid_user3 = Circle::add_member($this->circleid1, $this->user3->get_id(), 0, $this->con);
  }

  public function testActivityUserRegister_0() {
    // since we have registered 3 users, we should be able to get their activity

    $refid = '';
    $reftable = "activity_user_register";    
    $time = '';
    // activity table
    $result = select_from($reftable, "*", "WHERE `userid` = '{$this->user1->get_id()}'", $this->con);
    $got_it = false;
    while ($row = mysqli_fetch_assoc($result)) {
      $refid = $row['actid'];
      $time = $row['time'];
      $got_it = true;
    }
    $this->assertTrue($got_it);

    // timeline table; Use userid and time to select the proper row
    $result = select_from("timeline", "*", "WHERE `refid` = '$refid'", $this->con);
    $got_it = false;
    $time_get = '';
    $reftable_get = '';
    while ($row = mysqli_fetch_assoc($result)) {
      $got_it = true;
      $time_get = $row['time'];
      $reftable_get = $row['reftable'];
    }
    $this->assertTrue($got_it);
    $this->assertEquals($time, $time_get); // time should match. This is important
    $this->assertEquals($reftable, $reftable_get);
  }

  /*
   * @after
   */
  public function tearDown() {
    delete_from("users", '', '', $this->con);
    delete_from("circles", '', '', $this->con);
  }
}
?>
