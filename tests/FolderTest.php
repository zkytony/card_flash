<?php 
require_once "../models.php";
require_once "../database.php";
require_once "../tables.php";

class FolderTest extends PHPUnit_Framework_Testcase {

  private $con;
  private $user1;

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

    // Create 3 decks for user1
    $this->deckids = array();
    // add the deck 1
    $title = "C plus plus";
    $tags = array("advanced", "popular", "cool");
    $this->deckids[] = $this->user1->add_deck($title, $tags, true, $this->con);

    // add the deck 2
    $title = "Java";
    $tags = array("popular", "easy");
    $this->deckids[] = $this->user1->add_deck($title, $tags, true, $this->con);

    // add the deck 3
    $title = "Python";
    $tags = array("popular", "easy", "cool");
    $this->deckids[] = $this->user1->add_deck($title, $tags, true, $this->con);

    // Add a folder called Skills
    $folderid = Deck::add_folder('Skills', $this->user1->get_id(), $this->con);
    $this->assertFalse(is_null($folderid));
  }

  public function testAddtoFolderThatIsNotExistYet() {
    // Add the C plus plus deck to the folder called 'Programming'
    $created_new = Deck::add_to_folder($this->deckids[0], 'Programming', $this->user1->get_id(), $this->con);
    $this->assertTrue($created_new);
    $folderid = Deck::get_folderid('Programming', $this->user1->get_id(), $this->con);
    $result = select_from("folders", "*", "WHERE `folderid` = '$folderid'", $this->con);
    while ($row = mysqli_fetch_assoc($result)) {
      $this->assertEquals('Programming', $row['name']);
      $this->assertEquals($this->user1->get_id(), $row['userid']);
    }

    // Check if the 'folderid' column in decks table is updated
    $folderid_get = Deck::folderid_for_deck($this->deckids[0], $this->con);
    $this->assertEquals($folderid, $folderid_get);
  }

  /*
   * @depends testAddtoFolderThatIsNotExistYet
   */
  public function testAddtoFolderThatHasBeenThere() {
    $aa = Deck::folder_exists_for_user('Skills', $this->user1->get_id(), $this->con);
    $this->assertTrue($aa);

    // Add C plus plus, Java and Python decks to 'Skills' folder that has been created previously
    $created_new = Deck::add_to_folder($this->deckids[0], 'Skills', $this->user1->get_id(), $this->con);
    $created_new = Deck::add_to_folder($this->deckids[1], 'Skills', $this->user1->get_id(), $this->con);
    $created_new = Deck::add_to_folder($this->deckids[2], 'Skills', $this->user1->get_id(), $this->con);
    $this->assertFalse($created_new);
    $folderid = Deck::get_folderid('Skills', $this->user1->get_id(), $this->con);
    
    // Check if the 'folderid' column in decks table is updated
    // For C plus plus
    $folderid_get = Deck::folderid_for_deck($this->deckids[0], $this->con);
    $this->assertEquals($folderid, $folderid_get);
    // For Java
    $folderid_get = Deck::folderid_for_deck($this->deckids[1], $this->con);
    $this->assertEquals($folderid, $folderid_get);
    // For Python
    $folderid_get = Deck::folderid_for_deck($this->deckids[2], $this->con);
    $this->assertEquals($folderid, $folderid_get);
  }

  public function testDeleteDeckFromFolderThatDoesNotExistForUser() {
    // Attempt to delete C plus plus deck from the folder Food
    $success = Deck::delete_from_folder($this->deckids[0], 'Food', $this->user1->get_id(), $this->con);
    $this->assertFalse($success);
  }

  public function testDeleteDeckFromFolderThatExistForUser() {
    // Delete C plus plus deck from the folder Skills
    $success = Deck::delete_from_folder($this->deckids[0], 'Skills', $this->user1->get_id(), $this->con);
    $this->assertTrue($success);
    // Check if the 'folderid' column in decks table is updated
    $folderid_get = Deck::folderid_for_deck($this->deckids[0], $this->con);
    $this->assertEquals(NULL, $folderid_get);
  }

  public function testDeleteFolder() {
    $success = Deck::delete_folder('Skills', $this->user1->get_id(), $this->con);    
    $this->assertTrue($success);
    // Check if the 'folderid' column in decks table is updated
    $folderid_get_0 = Deck::folderid_for_deck($this->deckids[0], $this->con);
    $folderid_get_1 = Deck::folderid_for_deck($this->deckids[1], $this->con);
    $folderid_get_2 = Deck::folderid_for_deck($this->deckids[2], $this->con);
    $this->assertEquals(NULL, $folderid_get_0);
    $this->assertEquals(NULL, $folderid_get_1);
    $this->assertEquals(NULL, $folderid_get_2);
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
