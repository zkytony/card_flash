<?php 
require_once "../models.php";
require_once "../database.php";
require_once "../tables.php";

/* Test class for user favorites functionality */
class FavoriteTest extends PHPUnit_Framework_Testcase 
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

    // let user1 and user3 favorite to deck1, and user3 favorite to deck2
    $this->favid1 = Favorite::favorite($this->deckids[0], $this->user1->get_id(), NULL, $this->con);
    $this->favid2 = Favorite::favorite($this->deckids[0], $this->user3->get_id(), NULL, $this->con);
    $this->favid3 = Favorite::favorite($this->deckids[1], $this->user3->get_id(), NULL, $this->con);
  }

  public function testFavoriteTable() {
    $result = select_from("favorites", "*", "", $this->con);
    $has1 = false; // has favid1
    $has2 = false; // ...
    $has3 = false;
    while ($row = mysqli_fetch_assoc($result)) {
      if ($row['favid'] == $this->favid1) {
        $has1 = true;
        $this->assertEquals($this->deckids[0], $row['deckid']);
        $this->assertEquals($this->user1->get_id(), $row['fav_userid']);
      } else if ($row['favid'] == $this->favid2) {
        $has2 = true;
        $this->assertEquals($this->deckids[0], $row['deckid']);
        $this->assertEquals($this->user3->get_id(), $row['fav_userid']);
      } else if ($row['favid'] == $this->favid3) {
        $has3 = true;
        $this->assertEquals($this->deckids[1], $row['deckid']);
        $this->assertEquals($this->user3->get_id(), $row['fav_userid']);
      }
    }
    $this->assertEquals(true, $has1);
    $this->assertEquals(true, $has2);
    $this->assertEquals(true, $has3);
  }

  public function testFavoriteUnopenDeck() {
    // deck3 is unopen, try to let user1 to favorite it
    $favid = Favorite::favorite($this->deckids[2], $this->user1->get_id(), NULL, $this->con);
    // should return null
    $this->assertEquals(NULL, $favid);
  }

  public function testDeckAndUserFavoritesCount() {
    $fav_num1 = User::num_favorites($this->user1->get_id(), $this->con);
    $fav_num2 = User::num_favorites($this->user2->get_id(), $this->con);
    $fav_num3 = User::num_favorites($this->user3->get_id(), $this->con);

    $this->assertEquals(1, $fav_num1); // fav == favorites
    $this->assertEquals(0, $fav_num2);
    $this->assertEquals(2, $fav_num3);

    $fav_num1 = Deck::num_favorites($this->deckids[0], $this->con);
    $fav_num2 = Deck::num_favorites($this->deckids[1], $this->con);
    $fav_num3 = Deck::num_favorites($this->deckids[2], $this->con);

    $this->assertEquals(2, $fav_num1);
    $this->assertEquals(1, $fav_num2);
    $this->assertEquals(0, $fav_num3);
  }

  public function testUnfavorite() {
    Favorite::unfavorite($this->deckids[0], $this->user1->get_id(), $this->con);

    $fav_num1 = User::num_favorites($this->user1->get_id(), $this->con);
    $this->assertEquals(0, $fav_num1); // fav == favorites

    $fav_num1 = Deck::num_favorites($this->deckids[0], $this->con);
    $this->assertEquals(1, $fav_num1);
  }

  public function tearDown() {
    delete_from("favorites", "", "", $this->con);
    delete_from("decks", "", "", $this->con);
    delete_from("users", "", "", $this->con);
  }
}

?>
