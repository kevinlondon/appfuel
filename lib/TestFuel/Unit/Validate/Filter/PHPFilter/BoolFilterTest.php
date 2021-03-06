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
namespace TestFuel\Unit\Validate\Filter\PHPFilter;

use StdClass,
	TestFuel\TestCase\BaseTestCase,
	Appfuel\Validate\Filter\PHPFilter\BoolFilter,
	Appfuel\DataStructure\Dictionary;

/**
 * Test bool filter which wraps php filter var
 */
class BoolFilterTest extends BaseTestCase
{
	/**
	 * System under test
	 * @var IntFilter
	 */
	protected $filter = null;

	/**
	 * @return null
	 */
	public function setUp()
	{
		$this->filter = new BoolFilter('php-bool-filter');
	}

	/**
	 * @return null
	 */
	public function tearDown()
	{
		unset($this->filter);
	}

	/**
	 * @return null
	 */
	public function testInterfaces()
	{
		$this->assertInstanceOf(
			'Appfuel\Validate\Filter\FilterInterface',
			$this->filter
		);

		$this->assertInstanceOf(
			'Appfuel\Validate\Filter\ValidateFilter',
			$this->filter
		);
	}

	/**
	 * @return	array
	 */
	public function provideTrueValues()
	{
		return array(
			array(true),
			array(1),
			array('1'),
			array('yes'),
			array('on'),
			array('true')
		);
	}

	/**
	 * @return	array
	 */
	public function provideFalseValues()
	{
		return array(
			array('false'),
			array('0'),
			array('false'),
			array('off'),
		);
	}

	/**
	 * @return	array
	 */
	public function provideNonTrueValues()
	{
		return array(
			array(false),
			array(null),
			array(0),
			array('0'),
			array('false'),
			array('off'),
			array(''),
			array(1234),
			array('abc'),
			array(new StdClass()),
			array(array(1,3,4))
		);
	}

	/**
	 * True is considered any of these values:
	 * true, 1, '1', 'yes', 'on', 'true'
	 *
	 * @dataProvider	provideTrueValues
	 * @return	null
	 */
	public function testTrueCaseNoParameters($raw)
	{
		$params = new Dictionary();
		$this->assertTrue($this->filter->filter($raw, $params));
	}

	/**
	 * With no params anything that is not true is false
	 *
	 * @depends			testTrueCaseNoParameters
	 * @dataProvider	provideNonTrueValues
	 * @return	null
	 */
	public function testFalseCaseNoParameters($raw)
	{
		$params = new Dictionary();
		$this->assertFalse($this->filter->filter($raw, $params));
	}

	/**
	 * Make sure truth values work the same with the strict params
	 *
	 * @depends			testTrueCaseNoParameters
	 * @dataProvider	provideTrueValues
	 * @return null
	 */
	public function testTrueWithStrictParams($raw)
	{
		$params = new Dictionary(array('strict' => true));
		$this->assertTrue($this->filter->filter($raw, $params));
	}

	/**
	 * With strict mode false will be returned only for specific false values
	 *
	 * depends			testTrueCaseNoParameters
	 * @dataProvider	provideFalseValues
	 * @return null
	 */
	public function testFalseWithStrictParams($raw)
	{
		$params = new Dictionary(array('strict' => true));
		$this->assertFalse($this->filter->filter($raw, $params));
	}

	/**
	 * depends			testFalseWithStrictParams
	 * @return null
	 */
	public function testFailedFalseWithStrictParams()
	{
		$params = new Dictionary(array('strict' => true));
		$this->assertNull(
			$this->filter->filter(false, $params),
			'php filter_var does not think this is really false go figure'
		);
		$this->assertTrue($this->filter->isFailure());

		/* the string false on the other hand is acceptable */
		$this->assertFalse($this->filter->filter('false', $params));

		$this->assertNull(
			$this->filter->filter(null, $params),
			'php filter_var does not consider null false'
		);
		$this->assertTrue($this->filter->isFailure());

		$this->assertNull(
			$this->filter->filter('null', $params),
			'php filter_var does not consider null false'
		);
		$this->assertTrue($this->filter->isFailure());


		$this->assertNull(
			$this->filter->filter('abc', $params),
			'php filter_var does not consider null false'
		);
		$this->assertTrue($this->filter->isFailure());

		$this->assertTrue($this->filter->filter('1', $params));
	}



}
