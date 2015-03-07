<?php 
require_once "../models.php";
require_once "../database.php";
require_once "../tables.php";

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
?>
