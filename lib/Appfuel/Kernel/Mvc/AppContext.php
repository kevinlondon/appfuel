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
namespace Appfuel\Kernel\Mvc;

use Appfuel\Error\ErrorStack,
	Appfuel\Error\ErrorStackInterface,
	Appfuel\DataStructure\Dictionary,
	Appfuel\DomainStructure\DictionaryInterface;

/**
 * The app context holds the input (get, post, argv etc..), handles errors and 
 * is a dictionary that can hold hold key value pairs allowing custom objects
 * specific to the application to be added without having to extends the 
 * context. The context is passed into each intercepting filter and then into
 * the action controllers process method.
 */
class AppContext extends Dictionary implements ContextInterface
{
	/**
	 * Holds most of the user input given to the application. Used by the
	 * Front controller and all action controllers
	 * @var	AppInputInterface
	 */
	protected $input = null;

	/**
	 * Hold all errors for the application controller
	 * @var Appfuel\Error\ErrorStack
	 */
	protected $errorStack = null;

	/**
	 * @param	AppInputInterface		$input
	 * @param	ErrorStackInterface		$error
	 * @return	AppContext
	 */
	public function __construct(AppInputInterface $input,
								ErrorStackInterface $error = null)
	{
		$this->setInput($input);

		if (null === $error) {
			$error = new ErrorStack();
		}
		$this->setErrorStack($error);
	}

	/**
	 * @return	ContextInputInterface
	 */
	public function getInput()
	{
		return $this->input;
	}

	/**
	 * @return	ErrorStackInterface
	 */
	public function getErrorStack()
	{
		return $this->errorStack;
	}

	/**
	 * @param	ErrorStackInterface		$error
	 * @return	AppContext
	 */
	public function setErrorStack(ErrorStackInterface $error)
	{
		$this->errorStack = $error;
		return $this;
	}

	/**
	 * @return	bool
	 */
	public function isError()
	{
		return $this->errorStack->count() > 0;
	}

	/**
	 * @param	string	$msg
	 * @param	int		$code
	 * @return	AppContext
	 */
	public function addError($msg, $code = 400)
	{
		$this->getErrorStack()
			 ->addError($msg, $code);
		return $this;
	}

	/**
	 * @return	string
	 */
	public function getErrorString()
	{
		return (string)$this->getErrorStack();
	}

	/**
	 * @param	AppInputInterface	$input
	 * @return	null
	 */
	protected function setInput(AppInputInterface $input)
	{
		$this->input = $input;
	}

	/**
	 * Allows the deleveloper to define what interface is valid for their
	 * user implementation
	 *
	 * @param	mixed	$user
	 * @return	bool
	 */
	protected function isValidUser($user)
	{
		if ($user instanceof UserInterface) {
			return true;
		}

		return false;
	}
}
