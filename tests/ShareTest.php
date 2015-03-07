<?php
require_once "../models.php";
require_once "../database.php";
require_once "../tables.php";

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
?>
