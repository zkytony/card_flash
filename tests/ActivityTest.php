<?php
require_once "../models.php";
require_once "../database.php";
require_once "../tables.php";

class ActivityTest extends PHPUnit_Framework_Testcase {

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

  public function testActivityUserRegister_0() {
    // since we have registered 3 users, we should be able to get their activity

    $refid = '';
    $reftable = "activity_user_register";    
    $time = '';
    // activity table
    $result = select_from($reftable, "*", "WHERE `userid` = '{$this->user1->get_id()}'", $this->con);
    $got_it = false;
    while ($row = mysqli_fetch_assoc($result)) {
      $refid = $row['actid'];
      $time = $row['time'];
      $got_it = true;
    }
    $this->assertTrue($got_it);

    // timeline table; Use userid and time to select the proper row
    $result = select_from("timeline", "*", "WHERE `refid` = '$refid'", $this->con);
    $got_it = false;
    $time_get = '';
    $reftable_get = '';
    while ($row = mysqli_fetch_assoc($result)) {
      $got_it = true;
      $time_get = $row['time'];
      $reftable_get = $row['reftable'];
    }
    $this->assertTrue($got_it);
    $this->assertEquals($time, $time_get); // time should match. This is important
    $this->assertEquals($reftable, $reftable_get);
  }

  public function testActivityCreateCircle_6() {
    // since we have created a circle, we should have the activity about that

    $refid = '';
    $reftable = "activity_group_join";    
    $time = '';
    // activity table
    $result = select_from($reftable, "*", 
			  "WHERE `userid` = '{$this->user1->get_id()}'"
			  ." AND `circleid` = '{$this->circleid1}'", $this->con);
    $got_it = false;
    while ($row = mysqli_fetch_assoc($result)) {
      $refid = $row['actid'];
      $time = $row['time'];
      $got_it = true;
      
      // Since user1 is the group creator, init should be '1' (Representing 'true')
      $this->assertEquals('1', $row['init']);
    }
    $this->assertTrue($got_it);

    // timeline table; Use userid and time to select the proper row
    $result = select_from("timeline", "*", "WHERE `refid` = '$refid'", $this->con);
    $got_it = false;
    $time_get = '';
    $reftable_get = '';
    while ($row = mysqli_fetch_assoc($result)) {
      $got_it = true;
      $time_get = $row['time'];
      $reftable_get = $row['reftable'];
    }
    $this->assertTrue($got_it);
    $this->assertEquals($time, $time_get); // time should match. This is important
    $this->assertEquals($reftable, $reftable_get);
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
