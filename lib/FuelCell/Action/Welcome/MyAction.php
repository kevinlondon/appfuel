<?php
/**
 * Appfuel
 * PHP 5.3+ object oriented MVC framework supporting domain driven design. 
 *
 * @package     Appfuel
 * @author      Robert Scott-Buccleuch <rsb.code@gmail.com>
 * @copyright   2009-2010 Robert Scott-Buccleuch <rsb.code@gmail.com>
 * @license     http://www.apache.org/licenses/LICENSE-2.0
 */
namespace FuelCell\Action\Welcome;

use Appfuel\Kernel\Mvc\MvcAction,
	Appfuel\Kernel\Mvc\MvcContextInterface;

class MyAction extends MvcAction
{
	/**
	 * @param	MvcContextInterface $context
	 * @return	null
	 */
	public function process(MvcContextInterface $context)
	{
		$input = $context->getInput();
		$view = $context->getView();
		$view->assign('foo', 'bar');
	}
}
