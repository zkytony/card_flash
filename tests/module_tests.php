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
    $user_info = $user->get_info();
    $this->assertEquals('1', $user_info['activate']);
    $this->assertEquals('1', $user_info['online']);

    // delete this user
    delete_from("users", "WHERE `username` = '$username' AND `password` = '$password'", '1', $this->con);    
  }

  public function testLogOut() {
    $username = 'user1';
    $password = 'dummy';
    $success = User::register($username, $password, $this->con);
    $this->assertEquals(true, $success);

    $user = User::sign_in($username, $password, $this->con);
    $user->logout($this->con);
    $this->assertEquals('0', $user->get_info()['online']);
  
    $result = select_from("users", "*", 
                          "WHERE `username` = '$username' AND `password` = '$password'", $this->con);
    while($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals('0', $row['online']);
    }

    // delete this user
    delete_from("users", "WHERE `username` = '$username' AND `password` = '$password'", '1', $this->con);    
  }

  public function testIdFromName() {
    $username = 'user1';
    $password = 'dummy';
    $success = User::register($username, $password, $this->con);
    $this->assertEquals(true, $success);
    $user = User::sign_in($username, $password, $this->con);
    $userid_exp = $user->get_id();
    
    $userid_get = User::id_from_name($username, $this->con);
    $this->assertEquals($userid_exp, $userid_get);
    // delete this user
    delete_from("users", "WHERE `username` = '$username' AND `password` = '$password'", '1', $this->con);    
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

    $username = 'user1';
    $password = 'dummy';
    $success = User::register($username, $password, $this->con);
    $this->assertEquals(true, $success);
    $this->user = User::sign_in($username, $password, $this->con);
  }
  
  public function testAddDeck() {
    $title = "Deck1";
    $tags = array("aaa", "bbb", "ccc");
    
    $deckid = $this->user->add_deck($title, $tags, $this->con);
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
    $deckid = $this->user->add_deck($title, $tags, $this->con);

    $title = "Card99";
    $sub = "One card";
    $content = "<h1>Hi</h1>";
    $cardid = $this->user->add_card($title, $sub, $content, $deckid, $this->con);

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
    $deckid = $this->user->add_deck($title, $tags, $this->con);

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
    $deckid_1 = $this->user->add_deck($title, $tags, $this->con);

    // add the deck 2
    $title = "Deck2";
    $tags = array("aaa", "bbb", "ddd");
    $deckid_2 = $this->user->add_deck($title, $tags, $this->con);

    // add the deck 3
    $title = "Deck1";
    $tags = array("aaa", "ccc", "eee");
    $deckid_3 = $this->user->add_deck($title, $tags, $this->con);

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

  public function tearDown() {
    // delete this user
    $userid = $this->user->get_id();
    delete_from("users", "WHERE `userid` = '$userid'", '1', $this->con);
  }
}

/* Test class for deck sharing functionality */
class ShareTest extends PHPUnit_Framework_Testcase
{
  private $con;
  private $user;
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

    $username = 'user1';
    $password = 'dummy';
    $success = User::register($username, $password, $this->con);
    $this->assertEquals(true, $success);
    $this->user = User::sign_in($username, $password, $this->con);

    $this->deckids = array();
    // add the deck 1
    $title = "Deck1";
    $tags = array("aaa", "bbb", "ccc");
    $this->deckids[] = $this->user->add_deck($title, $tags, $this->con);

    // add the deck 2
    $title = "Deck2";
    $tags = array("aaa", "bbb", "ddd");
    $this->deckids[] = $this->user->add_deck($title, $tags, $this->con);

    // add the deck 3
    $title = "Deck1";
    $tags = array("aaa", "ccc", "eee");
    $this->deckids[] = $this->user->add_deck($title, $tags, $this->con);
  }

  public function testShareToNew() {
    $shareid_1 = Share::share_to($this->deckids[0], $this->user->get_id(), 1, $this->con);
    $shareid_2 = Share::share_to($this->deckids[1], $this->user->get_id(), 2, $this->con);
    $shareid_3 = Share::share_to($this->deckids[2], $this->user->get_id(), 1, $this->con);

    $deckids_get = Share::shared_decks($this->user->get_id(), 1, $this->con);
    $deckids_exp = array($this->deckids[0], $this->deckids[2]);

    $this->assertEquals($deckids_exp, $deckids_get);

    $userids_get = Share::shared_users($this->deckids[1], 2, $this->con);
    $userids_exp = array($this->user->get_id());
    $this->assertEquals($userids_exp, $userids_get);
    
    // test if update works
    $shareid_2_new = Share::share_to($this->deckids[1], $this->user->get_id(), 1, $this->con);
    $this->assertEquals($shareid_2, $shareid_2_new);
    $deckids_get = Share::shared_decks($this->user->get_id(), 1, $this->con);
    $deckids_exp = array($this->deckids[0], $this->deckids[1], $this->deckids[2]);
    $this->assertEquals($deckids_exp, $deckids_get);
  }

  public function testUnshare() {
    $shareid_1 = Share::share_to($this->deckids[0], $this->user->get_id(), 1, $this->con);
    $shareid_2 = Share::share_to($this->deckids[1], $this->user->get_id(), 2, $this->con);
    $shareid_3 = Share::share_to($this->deckids[2], $this->user->get_id(), 1, $this->con);

    Share::unshare($this->deckids[0], $this->user->get_id(), $this->con);
    $status = Share::check_status($this->deckids[0], $this->user->get_id(), $this->con);
    $this->assertEquals(0, $status);
  }

  public function tearDown() {
    // delete this user
    $userid = $this->user->get_id();
    delete_from("users", "WHERE `userid` = '$userid'", '1', $this->con);
    // delete decks
    delete_from("decks", "", "", $this->con);
    // delete tags
    delete_from("tags", "", "", $this->con);
  }

}

?>