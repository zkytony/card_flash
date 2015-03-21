<?php 
require_once "../models.php";
require_once "../database.php";
require_once "../tables.php";

/*
   Unit test for Comment class
   Note: keep the temp_flashcard database clean before you test
 */

class CommentTest extends PHPUnit_Framework_Testcase
{
  private $con;
  private $user1;
  private $user2;
  private $deckid1;
  private $cardid1;

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
      'email' => 'yuu@ddd.com',
      'first' => 'Bruno',
      'last' => 'Moons',
      'password' => 'bnmmoon',
      'birth' => '07-03-1934'
    );
    $success_2 = User::register($info2, $this->con);
    $this->assertEquals(true, $success_2);
    $this->user2 = User::sign_in($info2['email'], $info2['password'], $this->con);

    // Create a deck fro user1
    $title = "Seattle Beauty";
    $tags = array("advanced", "popular", "cool");
    $this->deckid1 = $this->user1->add_deck($title, $tags, true, $this->con);

    // Add a card to the deck
    $title = "UW Cherry Blossom";
    $sub = "One card";
    $content = "<h1>Hi there</h1>";
    $this->cardid1 = $this->user1->add_card($title, $sub, $content, $this->deckid1, 0, NULL, $this->con);
  }

  public function testMakeCommentOnCard() {
    // User2 now makes a comment on the card:
    $content = 'Wonderful!';
    $commentid1 = Comment::comment($this->user2->get_id(), $content, 
				   NULL, 0, $this->cardid1, NULL, $this->con);

    // check if this comment is inserted into the table
    $result = select_from("comments", "*", "WHERE `commentid` = '$commentid1'", $this->con);

    $this->assertEquals(1, $result->num_rows);
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals($content, $row['content']);
      $this->assertEquals('', $row['reply_commentid']); // NULL is '' in mysql?
      $this->assertEquals('0', $row['type']);
      $this->assertEquals($this->cardid1, $row['targetid']);
    }
  }

  public function testMakeCommentOnDeck() {
    // User2 now makes a comment on the deck:
    $content = 'Brilliant work!';
    $commentid1 = Comment::comment($this->user2->get_id(), $content, 
				   NULL, 1, $this->deckid1, NULL, $this->con);

    // check if this comment is inserted into the table
    $result = select_from("comments", "*", "WHERE `commentid` = '$commentid1'", $this->con);

    $this->assertEquals(1, $result->num_rows);
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals($content, $row['content']);
      $this->assertEquals('', $row['reply_commentid']);
      $this->assertEquals('1', $row['type']);
      $this->assertEquals($this->deckid1, $row['targetid']);
    }
  }


  public function testReplyBackAndForth() {
    // User2 now makes a comment on the card:
    $content = 'Wonderful!';
    $commentid1 = Comment::comment($this->user2->get_id(), $content, 
				   NULL, 0, $this->cardid1, NULL, $this->con);

    // User1 replies the above comment
    $content = 'Thank you!';
    $commentid2 = Comment::comment($this->user1->get_id(), $content, 
				   $commentid1, 0, $this->cardid1, NULL, $this->con);

    // Because User 1 is dumb, he replies his own comment
    $content = 'I feel happy';
    $commentid3 = Comment::comment($this->user1->get_id(), $content, 
				   $commentid2, 0, $this->cardid1, NULL, $this->con);
    
    // User2 is noway smarter. He commented on the card again
    $content = 'Do you have balls?';
    $commentid4 = Comment::comment($this->user2->get_id(), $content, 
				   NULL, 0, $this->cardid1, NULL, $this->con);

    // User2 demonstrates some chill
    $content = 'You are welcome';
    $commentid5 = Comment::comment($this->user2->get_id(), $content, 
				   $commentid2, 0, $this->cardid1, NULL, $this->con);

    // The game is over.
    // It should look like this:
    // CARD -- 1 -- 2 -- 3
    //      \- 4      \- 5
    
    // checks all comments that the card has. Should be 5 in total
    $num_comments = Comment::num_comments($this->cardid1, $this->con);
    $this->assertEquals(5, $num_comments);

    // checkout num of replies that comment 2 has. Should be 2 (comment 3 and 5)
    $num_replies = Comment::num_replies($commentid2, $this->con);
    $this->assertEquals(2, $num_replies);

    // NEED MORE TESTING HERE
  }

  public function testCommentingActivity() {
    // User2 now makes a comment on the card:
    $content = 'Wonderful!';
    $commentid1 = Comment::comment($this->user2->get_id(), $content, 
				   NULL, 0, $this->cardid1, NULL, $this->con);

    // Check if timeline table and activity_user_comments table are updated
    $result = select_from("activity_user_comments", "*", "WHERE `commentid` = '$commentid1'", $this->con);
    $actid = "";
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals($this->cardid1, $row['targetid']);
      $this->assertEquals(0, $row['type']); // 0 is for commenting on a card
      $actid = $row['actid'];
    }

    $result = select_from("timeline", "*", "WHERE `refid` = '$actid'", $this->con);
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals("activity_user_comments", $row['reftable']);
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
