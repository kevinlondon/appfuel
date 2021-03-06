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
namespace TestFuel\Unit\Expr;

use StdClass,
	SplFileInfo,
	Appfuel\Expr\UnaryExpr,
	Appfuel\Expr\BasicExpr,
	Appfuel\Expr\BinaryExpr,
	TestFuel\TestCase\BaseTestCase;

/**
 * Test capabilities of the binary expression class
 */
class BinaryExprTest extends BaseTestCase
{
	/**
	 * System under test
	 * @var BinaryExpr
	 */
	protected $expr = null;
	
	/**
	 * Used as first paramter in constructor
	 * @var	string
	 */	
	protected $operator = null;
	
	/**
	 * Used as second paramter in constructor
	 * @var string
	 */
	protected $leftop = null;

	/**
	 * Used as second paramter in constructor
	 * @var string
	 */
	protected $rightop = null;


	/**
	 * @return null
	 */
	public function setUp()
	{
		$this->operator = '=';
		$this->leftop  = 'leftop';
		$this->rightop = 'rightop';
		$this->expr     = new BinaryExpr(
			$this->leftop, 
			$this->operator,
			$this->rightop
		);
	}

	/**
	 * @return	null
	 */
	public function tearDown()
	{
		unset($this->expr);
	}

	/**
	 * @return null
	 */
	public function testHasCorrectInterface()
	{	
		$this->assertInstanceOf(
			'Appfuel\Expr\ExprInterface',
			$this->expr
		);

		$this->assertInstanceOf(
			'Appfuel\Expr\BinaryExprInterface',
			$this->expr
		);
	}

	/**
	 * @return null
	 */
	public function testOperator()
	{
		/* this was a string */		
		$this->assertEquals($this->operator, $this->expr->getOperator());
	}

	/**
	 * @expectedException	InvalidArgumentException
	 * @return	null
	 */
	public function testOperatorInvalidEmptyString()
	{
		$expr = new BinaryExpr('field', '', 'field');		
	}

	/**
	 * @expectedException	InvalidArgumentException
	 * @return	null
	 */
	public function testOperatorInvalidNumber()
	{
		$expr = new BinaryExpr('operand', 99, 'operand');		
	}

	/**
	 * @expectedException	InvalidArgumentException
	 * @return	null
	 */
	public function testOperatorInvalidObject()
	{
		$expr = new BinaryExpr('operand', new StdClass(), 'operand');		
	}

	/**
	 * Even objects supporting toString
	 * @expectedException	InvalidArgumentException
	 * @return	null
	 */
	public function testOperatorInvalidObjectWithToString()
	{
		$expr = new BinaryExpr(
			'operand', 
			new SplFileInfo('failed'), 
			'operand'
		);
	}

	/**
	 * @return null
	 */
	public function testLeftRightOpNumber()
	{
		$expr = new BinaryExpr(99, '=', 88);
			
		/* because we extend the basic expression the operand in the
		 * basic expression is our left operand
		 */
		$this->assertEquals(99, $expr->getOperand());
		$this->assertEquals(99, $expr->getLeftOperand());

		$this->assertEquals(88, $expr->getRightOperand());

		$expr = new BinaryExpr(99.8, '=', 88.9);
			
		/* because we extend the basic expression the operand in the
		 * basic expression is our left operand
		 */
		$this->assertEquals(99.8, $expr->getOperand());
		$this->assertEquals(99.8, $expr->getLeftOperand());

		$this->assertEquals(88.9, $expr->getRightOperand());
	}

	public function testLeftRightOpObjects()
	{
		$leftOp  = new UnaryExpr('IS NOT NULLL', 'my_var');
		$rightOp = new BasicExpr('my_other_var');
		$expr = new BinaryExpr($leftOp, 'AND', $rightOp);
			
		/* because we extend the basic expression the operand in the
		 * basic expression is our left operand
		 */
		$this->assertEquals($leftOp, $expr->getOperand());
		$this->assertEquals($leftOp, $expr->getLeftOperand());

		$this->assertEquals($rightOp, $expr->getRightOperand());
	}

	/**
	 * @expectedException	InvalidArgumentException
	 * @return	null
	 */
	public function testLeftOperandInvalidEmptyString()
	{
		$expr = new BinaryExpr('', '=', 'field');		
	}

	/**
	 * @expectedException	InvalidArgumentException
	 * @return	null
	 */
	public function testRightOperandInvalidEmptyString()
	{
		$expr = new BinaryExpr('operand', '=', '');		
	}

	/**
	 * @expectedException	InvalidArgumentException
	 * @return	null
	 */
	public function testLeftOperatorInvalidObject()
	{
		$expr = new BinaryExpr(new StdClass(), '=', 'operand');		
	}

	/**
	 * @expectedException	InvalidArgumentException
	 * @return	null
	 */
	public function testRightOperatorInvalidObject()
	{
		$expr = new BinaryExpr('operand', '=', new StdClass());		
	}

	/**
	 * @return null
	 */
	public function testBuild()
	{
		$expected = 'leftop = rightop';
		$this->assertEquals($expected, $this->expr->build());
	}

	/**
	 * @return null
	 */
	public function testBuildObjects()
	{
		$other     = new BasicExpr('me');
		$leftExpr  = new UnaryExpr('!', $other);
		$rightExpr = new BasicExpr('must be you');

		$expr = new BinaryExpr($leftExpr, 'AND', $rightExpr);
		$expected = '! me AND must be you';
		$this->assertEquals($expected, $expr->build());
	}

	/**
	 * @return null
	 */
	public function testBuildNumbers()
	{
		$expr = new BinaryExpr(99, '!=', 99.9);
		
		$expected = '99 != 99.9';
		$this->assertEquals($expected, $expr->build());
	}

    /**
     * @return null
     */
    public function testToString()
    {  
		$expr = new BinaryExpr(99, '!=', 99.9);
		$expected = '99 != 99.9';
        $this->expectOutputString($expected);

        echo $expr;
    }
    /**
     * @return null
     */
    public function testDefaultValueIsParentheses()
    {
        $this->assertFalse($this->expr->isParentheses());
    }

    /**
     * @return null
     */
    public function testIsEnableDisableParentheses()
    {
        $expected = "(leftop = rightop)";
        $this->expr->enableParentheses();
        $this->assertEquals($expected, $this->expr->build());

        $expected = "leftop = rightop";
        $this->expr->disableParentheses();
        $this->assertEquals($expected, $this->expr->build());
    }
}
