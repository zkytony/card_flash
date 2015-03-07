<?php 
require_once "../models.php";
require_once "../database.php";
require_once "../tables.php";

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
?>
