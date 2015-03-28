<?php 
require_once "../models.php";
require_once "../database.php";
require_once "../tables.php";

/*
   Unit test for Comment class
   Note: keep the temp_flashcard database clean before you test
 */

class BoardTest extends PHPUnit_Framework_Testcase
{
  private $con;
  private $user1;
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

    // Create a deck for user1
    $title = "Seattle Beauty";
    $tags = array("advanced", "popular", "cool");
    $this->deckid1 = $this->user1->add_deck($title, $tags, true, $this->con);

    // Add a card to the deck
    $title = "UW Cherry Blossom";
    $sub = "One card";
    $content = "<h1>Hi there</h1>";
    $this->cardid1 = $this->user1->add_card($title, $sub, $content, $this->deckid1, 0, NULL, $this->con);
  }

  public function testCreateBoard() {
    $boardid = Board::create_board($this->user1->get_id(), NULL, $this->con);

    $result = select_from("boards", "`boardid`", "WHERE `boardid` = '$boardid'", $this->con);
    $this->assertEquals(1, $result->num_rows);
  }

  public function testAddToBoard() {
    // create a board first
    $boardid = Board::create_board($this->user1->get_id(), NULL, $this->con);

    // Add a deck to the board
    $brdwthid1 = Board::add($boardid, 1, $this->deckid1, NULL, $this->con);
    $this->assertEquals($brdwthid1,
			Board::exists_on_board($boardid, 1, $this->deckid1, $this->con));

    // Add a card to the board
    $brdwthid2 = Board::add($boardid, 0, $this->deckid1, NULL, $this->con);
    $this->assertEquals($brdwthid2,
			Board::exists_on_board($boardid, 0, $this->deckid1, $this->con));

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

