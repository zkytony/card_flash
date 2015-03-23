<?php 
require_once "../models.php";
require_once "../database.php";
require_once "../tables.php";

/*
   Unit test for Liking feature
   Note: keep the temp_flashcard database clean before you test
 */

class LikeTest extends PHPUnit_Framework_Testcase
{
  private $con;
  private $user1;
  private $deckid1;
  private $cardid1;
  private $commentid1;

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

    // Add a card to the deck
    $title = "UW Cherry Blossom";
    $sub = "One card";
    $content = "<h1>Hi there</h1>";
    $this->cardid1 = $this->user1->add_card($title, $sub, $content, $this->deckid1, 0, NULL, $this->con);

    // Let user1 to comment for his own card:
    $content = "Aloha!";
    $this->commentid1 = Comment::comment($this->user1->get_id(), $content,
					 NULL, 0, $this->cardid1, NULL, $this->con);    
  }

  public function testLikeDeckOrCardOrComment() {
    // user1 likes deck1
    Deck::like($this->user1->get_id(), $this->deckid1, NULL, $this->con);

    $result = select_from("decks", "`like`", "WHERE `deckid` = '$this->deckid1'", $this->con);
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals('1', $row['like']);
    }

    // user1 likes card1
    Card::like($this->user1->get_id(), $this->cardid1, NULL, $this->con);
    $result = select_from("cards", "`like`", "WHERE `cardid` = '$this->cardid1'", $this->con);
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals('1', $row['like']);
    }
    
    // user1 likes comment1
    Comment::like($this->user1->get_id(), $this->commentid1, NULL, $this->con);
    $result = select_from("comments", "`like`", "WHERE `commentid` = '$this->commentid1'", $this->con);
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals('1', $row['like']);
    }
  }

  public function testUnlike() {
    // user1 likes deck1
    Deck::like($this->user1->get_id(), $this->deckid1, NULL, $this->con);

    $result = select_from("decks", "`like`", "WHERE `deckid` = '$this->deckid1'", $this->con);
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals('1', $row['like']);
    }

    // user1 unlikes it
    Deck::unlike($this->user1->get_id(), $this->deckid1, $this->con);
    $result = select_from("decks", "`like`", "WHERE `deckid` = '$this->deckid1'", $this->con);
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals('0', $row['like']);
    }
  }

  public function testLikeActivity() {
    // user1 likes deck1
    Deck::like($this->user1->get_id(), $this->deckid1, NULL, $this->con);

    $result = select_from("decks", "`like`", "WHERE `deckid` = '$this->deckid1'", $this->con);
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals('1', $row['like']);
    }

    // Check the activity_user_likes table
    $result = select_from("activity_user_likes", "*", "WHERE `userid` = '{$this->user1->get_id()}'", $this->con);
    $actid = '';
    $time = '';
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals('1', $row['type']);
      $this->assertEquals("$this->deckid1", $row['targetid']);
      $actid = $row['actid'];
      $time = $row['time'];
    }
    
    // check the timeline table
    $result = select_from("timeline", "*", "WHERE `reftable` = 'activity_user_likes' AND `refid` = '$actid'", $this->con);
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals($time, $row['time']); // check if the time mataches
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
