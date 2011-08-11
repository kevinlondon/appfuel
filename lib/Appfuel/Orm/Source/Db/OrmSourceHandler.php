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
namespace Appfuel\Orm\Source\Db;

use Appfuel\Framework\Exception,
	Appfuel\View\Template as ViewTemplate,
	Appfuel\Db\Request\QueryRequest,
	Appfuel\Db\Request\MultiQueryRequest,
	Appfuel\Db\Request\PreparedRequest,
	Appfuel\Framework\File\FileManager,
	Appfuel\Framework\Db\Request\RequestInterface,
	Appfuel\Framework\Db\Handler\HandlerInterface,
	Appfuel\Framework\Orm\Source\SourceHandlerInterface;

/**
 * The database source handles preparing the sql and executing the database
 * handler and passing back the result
 */
class OrmSourceHandler implements SourceHandlerInterface
{
	/**
	 * Database handler used to issue database operations
	 * @var DbHandler
	 */
	protected $dbHandler = null;

	/**
	 * @param	AssemblerInterface $asm
	 * @return	OrmRepository
	 */
	public function __construct(HandlerInterface $dbHandler)
	{
		$this->dbHandler = $dbHandler;
	}

	/**
	 * @return	HandlerInterface
	 */
	public function getDataHandler()
	{
		return $this->dbHandler;
	}

	/**
	 * @param	string	$cat	this is the category of request to use
	 * @param	string	$type	type of operation the request will ask for
	 *							valid values: read|write|both
	 * @return	QueryRequest | false on failure
	 */
	public function createRequest($cat, $type = 'read')
	{
		$valid = array('query', 'multiquery', 'prepared');
		if (empty($cat) || ! is_string($cat) || ! in_array($cat, $valid)) {
			return false;
		}

		switch($cat) {
			case 'query'	 : $request = new QueryRequest($type);		break;
			case 'multiquery': $request = new MultiQueryRequest($type); break;
			case 'prepared'	 : $request = new PreparedRequest($type);   break;
			default: 
				return false;
		}
				
		return $request;
	}

	/**
	 * Create a simple view template for the sql file given
	 *
	 * @param	string	$path	relative path to sql file
	 * @return	ViewTemplate
	 */
	public function createTemplate($relativePath)
	{
		$dirPath  = FileManager::classNameToDir(get_class($this));
		$fullPath = "{$dirPath}/{$relativePath}";
		$file     = FileManager::createAppfuelFile($fullPath);
		return new ViewTemplate($file);
	}

	/**
	 * Used the database handler to send a request to the database
	 * 
	 * @param	RequestInterface
	 * @return	ResponseInterface
	 */
	public function sendRequest(RequestInterface $request)
	{
		return $this->getDataHandler()
					->execute($request);
	}
}