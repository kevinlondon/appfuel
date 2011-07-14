<?php
/**
 * Appfuel
 * PHP 5.3+ object oriented MVC framework supporting domain driven design. 
 *
 * @package     Appfuel
 * @author      Robert Scott-Buccleuch <rsb.code@gmail.com.com>
 * @copyright   2009-2010 Robert Scott-Buccleuch <rsb.code@gmail.com>
 * @license     http://www.apache.org/licenses/LICENSE-2.0
 */
namespace Test\Appfuel\Db\Mysql\AfMysqli;

use mysqli,
	Test\DbCase as ParentTestCase,
	Appfuel\Db\Connection\ConnectionDetail,
	Appfuel\Db\Mysql\AfMysqli\Connection;

/**
 * Test the adapters ability to wrap mysqli
 */
class ConnectionTest extends ParentTestCase
{
	/**
	 * System under test
	 * @var Connection
	 */
	protected $conn = null;

	/**
	 * @return null
	 */
	public function setUp()
	{
		$this->conn = new Connection($this->getConnDetail());
	}

	/**
	 * @return null
	 */
	public function tearDown()
	{
		unset($this->conn);
	}

	/**	
	 * @return null
	 */
	public function testInitialStatus()
	{
		$this->assertFalse($this->conn->isDriver());
		$this->assertEquals('uninitialized', $this->conn->getStatus());
		$this->assertFalse($this->conn->isError());
		$this->assertFalse($this->conn->isConnected());
		$this->assertEquals(0, $this->conn->getErrorCode());
		$this->assertNull($this->conn->getErrorText());
	}

	/**
	 * Connection must be initialized before it can be used. The initialize 
	 * method wraps the mysqli_init and I am not sure how to get that to 
	 * fail, if it ever does this will also fail.
	 * 
	 * @return null
	 */
	public function testInitialize()
	{
		$this->assertFalse($this->conn->isDriver());
		$this->assertNull($this->conn->getDriver());
		$this->assertEquals('uninitialized', $this->conn->getStatus());
		$this->assertTrue($this->conn->initialize());
		$this->assertTrue($this->conn->isDriver());
		$this->assertEquals('initialized', $this->conn->getStatus());
		$this->assertInstanceOf('mysqli', $this->conn->getDriver());
	}

	/**
	 * Connection can be manually initialized by setting the driver
	 * 
	 * @return null
	 */
	public function testGetSetIsDriver()
	{
		$this->assertFalse($this->conn->isDriver());
		$this->assertNull($this->conn->getDriver());
		$this->assertEquals('uninitialized', $this->conn->getStatus());

		$driver = mysqli_init();
		$this->assertNull($this->conn->setDriver($driver));
		$this->assertSame($driver, $this->conn->getDriver());
		$this->assertTrue($this->conn->isDriver());
		$this->assertEquals('initialized', $this->conn->getStatus());
	}

	/**
	 * @return null
	 */
	public function testConnectClose()
	{
		$this->assertTrue($this->conn->initialize());
		$this->assertTrue($this->conn->connect());
		$this->assertEquals('connected', $this->conn->getStatus());
		$this->assertTrue($this->conn->isConnected());
		$this->assertFalse($this->conn->isError());

		$this->assertTrue($this->conn->close());
		$this->assertEquals('closed', $this->conn->getStatus());
		
		$this->assertFalse($this->conn->isDriver());
		$this->assertFalse($this->conn->isConnected());
		$this->assertFalse($this->conn->isError());

		/* lets see if we can connect again */
		$this->assertTrue($this->conn->initialize());	
		$this->assertTrue($this->conn->connect());
		$this->assertEquals('connected', $this->conn->getStatus());
		$this->assertTrue($this->conn->isConnected());
		$this->assertFalse($this->conn->isError());

		$this->assertTrue($this->conn->close());
		$this->assertEquals('closed', $this->conn->getStatus());
		
		$this->assertFalse($this->conn->isDriver());
		$this->assertFalse($this->conn->isConnected());
		$this->assertFalse($this->conn->isError());	
	}

	/**
	 * @return	null
	 */
	public function testConnectBadConnectionPermissionDenied()
	{
		$connDetail = $this->getConnDetail();
		$connDetail->setUserName('_not_likely_to_exist_appfuel__');

		$conn = new Connection($connDetail);
		
		$this->assertTrue($conn->initialize());
		$this->assertFalse($conn->connect());
		$this->assertEquals('connection failed', $conn->getStatus());
		$this->assertFalse($conn->isConnected());
		$this->assertTrue($conn->isError());
		
		/* should fire a permission denied error */
		$this->assertEquals(1045, $conn->getErrorCode());
		
		$expected = "Access denied for user " .
					"'_not_likely_to_exist_appfuel__'@'localhost'" .
					" (using password: NO)";

		$this->assertEquals($expected, $conn->getErrorText());
		
	}

	/**
	 * @return	null
	 */
	public function testConnectBadConnectionIncorrectDb()
	{
		$connDetail = $this->getConnDetail();
		$connDetail->setDbName('_not_likely_to_exist_appfuel__');

		$conn = new Connection($connDetail);

		$this->assertTrue($conn->initialize());
		$this->assertFalse($conn->connect());
		$this->assertEquals('connection failed', $conn->getStatus());
		$this->assertFalse($conn->isConnected());
		$this->assertTrue($conn->isError());
		
		$this->assertEquals(1044, $conn->getErrorCode());
		
		$expected = "Access denied for user " .
					"'appfuel_user'@'%' to database " .
					"'_not_likely_to_exist_appfuel__'";

		$this->assertEquals($expected, $conn->getErrorText());
	}

	/**
	 * @return	null
	 */
	public function testConnectBadConnectionIncorrectPassword()
	{
		$connDetail = $this->getConnDetail();
		$connDetail->setPassword('xxxx');

		$conn = new Connection($connDetail);
		$this->assertTrue($conn->initialize());
		$this->assertFalse($conn->connect());
		$this->assertEquals('connection failed', $conn->getStatus());
		$this->assertFalse($conn->isConnected());
		$this->assertTrue($conn->isError());
		
		$this->assertEquals(1045, $conn->getErrorCode());
		
		$expected = "Access denied for user " .
					"'appfuel_user'@'localhost' " .
					"(using password: YES)";

		$this->assertEquals($expected, $conn->getErrorText());
	}

	/**
	 * @return null
	 */
	public function xtestCreateStmtHandle()
	{
		$this->assertTrue($this->conn->connect());
		$this->assertInstanceOf(
			'mysqli_stmt', 
			$this->conn->createStmtDriver()
		);
		
		$this->assertTrue($this->conn->close());
	}

	/**
	 * @return null
	 */
	public function xtestCreateStmtHandleNoConnection()
	{
		$this->assertFalse($this->conn->createStmtDriver());
		$this->assertTrue($this->conn->isError());
		$this->assertEquals('AF_CONN_ERR', $this->conn->getErrorCode());
	
		$expected = 'connect failure: must be connected to create stmt handle';
		$this->assertEquals($expected, $this->conn->getErrorText());
		
	}


}