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
namespace Appfuel\Error;

use Countable,
	Iterator;

/**
 * The error stack handles a collection of errors or treats that collection
 * as if it were one error.
 */
class ErrorStack implements ErrorStackInterface, Countable, Iterator
{
	/**
	 * Collection of error objects
	 * @var scalar
	 */
	protected $errors = array();

	/**
	 * @return	bool
	 */
	public function isError()
	{
		return $this->count() > 0;
	}

	/**
	 * @param	ErrorStackInterface $stack
	 * @return	ErrorStack
	 */
	public function mergeStack(ErrorStackInterface $stack)
	{
		foreach ($stack as $item) {
			$this->addErrorItem($item);
		}

		return $this;
	}

	/**
	 * @param	ErrorInterface	$error
	 * @return	ErrorStack	
	 */
	public function addErrorItem(ErrorInterface $error)
	{
		$this->errors[] = $error;
		return $this;
	}

	/**
	 * @throws	InvalidArgumentException	
	 *			when msg or code is not a scalar or an object that implements
	 *			__toString.
	 *										
	 * @paran	scalar	$msg
	 * @param	scalar	$code
	 * @return	ErrorStack
	 */
	public function addError($msg, $code = null)
	{
		return $this->addErrorItem($this->createErrorItem($msg, $code));
	}

	/**
	 * Alias for current
	 *
	 * @return	ErrorInterface | false when no error exists
	 */
	public function getError()
	{
		return $this->current();
	}

	/**
	 * @return	string | null when no error exists
	 */
	public function getCode()
	{
		$error = $this->current();
		if ($error instanceof ErrorInterface) {
			return $error->getCode();
		}

		return null;
	}


	/**
	 * @return	string | null when no error exists
	 */
	public function getMessage()
	{
		$error = $this->current();
		if ($error instanceof ErrorInterface) {
			return $error->getMessage();
		}

		return null;
	}

	/**
	 * @return	ErrorInterface | false when no error exists
	 */
	public function getLastError()
	{
		$count = $this->count();
		if (0 === $count) {
			return false;
		}

		return $this->errors[$count - 1];
	}

	/**
	 * @return	int
	 */
	public function count()
	{
		return count($this->errors);
	}

    /**
     * @return  null
     */
    public function rewind()
    {
        reset($this->errors);
    }

    /**
     * @return  ErrorInterface | false no error exists
     */
    public function current()
    {
        return current($this->errors);
    }

    /**
     * @return  int
     */
    public function key()
    {
        return key($this->errors);
    }

    /**
     * @return  bool
     */
    public function valid()
    {
		if (null === ($key = $this->key())) {
			return false;
		}
		
        return $this->errors[$key] instanceof ErrorInterface;
    }

    /**
     * @return  null
     */
    public function next()
    {
		next($this->errors);
    }

	/**
	 * @param	string	
	 * @param	scalar	$code
	 * @return	AppfuelError
	 */
	public function createErrorItem($msg, $code = null)
	{
		return new ErrorItem($msg, $code);
	}
}
