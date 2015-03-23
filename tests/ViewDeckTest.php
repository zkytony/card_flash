<?php 
require_once "../models.php";
require_once "../database.php";
require_once "../tables.php";

/*
   Unit test for Viewing deck
   Note: keep the temp_flashcard database clean before you test
 */

class LikeTest extends PHPUnit_Framework_Testcase
{
  private $con;
  private $user1;
  private $deckid1;
  private $cardid1;
  private $cardid2;
  private $cardid3;
 
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

    // Create a deck fro user1
    $title = "Seattle Beauty";
    $tags = array("advanced", "popular", "cool");
    $this->deckid1 = $this->user1->add_deck($title, $tags, true, $this->con);

    // Add card1
    $title = "UW Cherry Blossom";
    $sub = "One card";
    $content = "<h1>Hi there</h1>";
    $this->cardid1 = $this->user1->add_card($title, $sub, $content, $this->deckid1, 0, NULL, $this->con);

    // Add card2
    $title = "Hawaii Volcano";
    $sub = "One card";
    $content = "<h1>Haahahaha</h1>";
    $this->cardid2 = $this->user1->add_card($title, $sub, $content, $this->deckid1, 0, NULL, $this->con);

    // Add card3
    $title = "ShangHai Pudong";
    $sub = "One card";
    $content = "<h1>Ni meimei</h1>";
    $this->cardid3 = $this->user1->add_card($title, $sub, $content, $this->deckid1, 0, NULL, $this->con);

  }

  public function testViewOnce() {
    // Suppose user1 views card1
    Deck::view($this->user1->get_id(), $this->deckid1, $this->cardid1, NULL, $this->con);
    
    // View count:
    $view_count = Deck::view_count($this->deckid1, $this->con);
    $this->assertEquals('1', $view_count);

    // Activity:
    $result = select_from("activity_user_view_deck", "*", "WHERE `userid` = '{$this->user1->get_id()}' AND `deckid` = '{$this->deckid1}'", $this->con);
    $actid = '';
    $time = '';
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals($this->cardid1, $row['cardid']);
      $actid = $row['actid'];
      $time = $row['time'];
    }
    
    // check the timeline table
    $result = select_from("timeline", "*", "WHERE `reftable` = 'activity_user_likes' AND `refid` = '$actid'", $this->con);
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals($time, $row['time']); // check if the time mataches
    }
  }

  public function testViewSeveralTimes() {
    // User views card1 and then card2 and then card3

    // Suppose user1 views card1
    Deck::view($this->user1->get_id(), $this->deckid1, $this->cardid1, NULL, $this->con);

    // Suppose user1 views card2
    Deck::view($this->user1->get_id(), $this->deckid1, $this->cardid2, NULL, $this->con);

    // View count:
    $view_count = Deck::view_count($this->deckid1, $this->con);
    $this->assertEquals('2', $view_count);

    // Activity:
    $result = select_from("activity_user_view_deck", "*", "WHERE `userid` = '{$this->user1->get_id()}' AND `deckid` = '{$this->deckid1}'", $this->con);
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals($this->cardid2, $row['cardid']);
    }

    // Suppose user1 views card3
    Deck::view($this->user1->get_id(), $this->deckid1, $this->cardid3, NULL, $this->con);

    // View count:
    $view_count = Deck::view_count($this->deckid1, $this->con);
    $this->assertEquals('3', $view_count);

    // Activity:
    $result = select_from("activity_user_view_deck", "*", "WHERE `userid` = '{$this->user1->get_id()}' AND `deckid` = '{$this->deckid1}'", $this->con);
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals($this->cardid3, $row['cardid']);
    }
  }

  /*
   * @after     
   */
  public function tearDown() {
    // delete this user
    delete_from("users", "", "", $this->con);
  }
}
?>
