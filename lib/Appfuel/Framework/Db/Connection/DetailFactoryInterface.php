<?php
/**
 * Appfuel
 * PHP 5.3+ object oriented MVC framework supporting domain driven design. 
 *
 * @package     Appfuel
 * @author      Robert Scott-Buccleuch <rsb.code@gmail.com.com>
 * @copyright   2009-2010 Robert Scott-Buccleuch <rsb.code@gmail.com>
 * @license		http://www.apache.org/licenses/LICENSE-2.0
 */
namespace Appfuel\Framework\Db\Connection;


/**
 * Creates connection detail objects using a parser interface
 */
interface DetailFactoryInterface
{
	/**
	 * @param	string	$connectionString
	 * @return	mixed	ConnectionDetailInterface | false on failure
	 */
	public function createConnectionDetail($connectionString);
}
