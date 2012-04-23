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
namespace Appfuel\App;

use RunTimeException,
	Appfuel\Http\HttpOutput,
	Appfuel\Http\HttpResponse,
	Appfuel\Console\ConsoleOutput,
	Appfuel\Kernel\Mvc\AppInput,
	Appfuel\Kernel\Mvc\AppInputInterface,
	Appfuel\Kernel\Mvc\RequestUri,
	Appfuel\Kernel\Mvc\RequestUriInterface,
	Appfuel\Kernel\Mvc\MvcContext,
	Appfuel\Kernel\Mvc\MvcContextInterface,
	Appfuel\Kernel\Mvc\MvcRouteManager,
	Appfuel\Kernel\Mvc\MvcDispatcher,
	Appfuel\Kernel\Mvc\MvcDispatcherInterface,
	Appfuel\Kernel\Mvc\InterceptChain,
	Appfuel\Kernel\Mvc\InterceptChainInterface,
	Appfuel\Kernel\Mvc\MvcFront,
	Appfuel\View\ViewBuilder,
	Appfuel\Kernel\Startup\TaskHandler,
	Appfuel\Kernel\Startup\ConfigRegistry;

/**
 * Create all object required to implement appfuels take on the mvc pattern
 */
class AppFactory implements AppFactoryInterface
{	

    /**
     * We look for query string first because the path info in the request uri
	 * gets lost with rewrite rules.
	 * 
     * @return  RequestUri
     */
	public function createUriFromServerSuperGlobal()
	{
		$isQueryString = isset($_SERVER['QUERY_STRING']) &&
						 is_string($_SERVER['QUERY_STRING']) &&
						 ! empty($_SERVER['QUERY_STRING']);

        if ($isQueryString) {
			$str = '?' . $_SERVER['QUERY_STRING'];
        }
        else if (isset($_SERVER['REQUEST_URI'])) {
            $str = $_SERVER['REQUEST_URI'];
        }
        else {
            $err  = 'ConextBuilder failed: php super global ';
            $err .= '$_SERVER[\'REQUEST_URI\']';
            throw new RunTimeException("$err is not set");
        }

		return $this->createUri($str);
	}

	/**
	 * @return	RequestUri
	 */
	public function createUri($str)
	{
		return new RequestUri($str);
	}

	/**
	 * @param	string	$method
	 * @param	array	$params
	 * @return	AppInput
	 */
	public function createInput($method, array $params = array())
	{
		return new AppInput($method, $params);
	}

	/**
	 * @param	RequestUriInterface $uri 
	 * @return	AppInput
	 */
	public function createInputFromSuperGlobals(RequestUriInterface $uri = null)
	{
		$argvData = array();
		$method   = 'get';
		if (PHP_SAPI === 'cli') {
			$method   = 'cli';
			$argvData = $_SERVER['argv'];
		}
		else if (isset($_SERVER['REQUEST_METHOD']) &&
				'post' === strtolower($_SERVER['REQUEST_METHOD'])) {
				$method = 'post';
		}

		if (null !== $uri) {
			$getData = $uri->getParams();
		}
		else {
			$getData = $_GET;
		}

		$params = array(
			'get'     => $getData,
			'post'    => $_POST,
			'files'   => $_FILES,
			'cookie' => $_COOKIE,
			'session' => (isset($_SESSION)) ? $_SESSION : array(),
			'argv'    => $argvData,
		);

		return $this->createInput($method, $params);
	}

	/**
	 * @return	AppInput
	 */
	public function createEmptyInput()
	{
		$method = 'get';
		if (PHP_SAPI === 'cli') {
			$method   = 'cli';
		}
		
		return $this->createInput($method, array());
	}

	/**
	 * @param	string	$key
	 * @return	MvcRouteDetailInterface
	 */
	public function createRouteDetail($key)
	{
		return MvcRouteManager::getRouteDetail($key);
	}

	/**
	 * @param	string	$key
	 * @param	AppInputInterface $input
	 * @param	mixed	$view
	 * @return	MvcContext
	 */
	public function createContext($key, AppInputInterface $input, $view = null)
	{
		return new MvcContext($key, $input, $view);
	}

	/**
	 * @param	string	$key
	 * @return	MvcContext
	 */
	public function createEmptyContext($key)
	{
		return $this->createContext($key, $this->createEmptyInput());
	}

	/**
	 * @return	MvcViewBuilderInterface
	 */
	public function createViewBuilder()
	{
		return new ViewBuilder();
	}

	/**
	 * @param	MvcDispatcherInterface $dispatcher
	 * @param	InterceptChainInterface $preChain
	 * @param	InterceptChainInterface $postChain
	 * @return	MvcFront
	 */
	public function createFront(MvcDispatcherInterface $dispatcher = null,
								InterceptChainInterface $preChain  = null,
								InterceptChainInterface $postChain = null)
	{
		$preList = ConfigRegistry::get('pre-filters', array());
		if (null === $preChain) {
			$preChain = new InterceptChain();
		}

		if (is_array($preList) && ! empty($preList)) {
			$preChain->loadFilters($preList);
		}

		$postList = ConfigRegistry::get('post-filters', array());
		if (null === $postChain) {
			$postChain = new InterceptChain();
		}

		if (is_array($postList) && ! empty($postList)) {
			$postChain->loadFilters($postList);
		}

		if (null === $dispatcher) {
			$dispatcher = new MvcDispatcher();
		}

		return new MvcFront($dispatcher, $preChain, $postChain);
	}

	/**
	 * @return	MvcDispatcher
	 */
	public function createDispatcher()
	{
		return new MvcDispatcher();
	}

	/**	
	 * @return	TaskHandler
	 */
	public function createTaskHandler()
	{
		return new TaskHandler();
	}

	public function createHttpResponse($data, 
									   $status,
									   $version = null,
									   array $headers = null)
	{
		return new HttpResponse($data, $status, $version, $headers);
	}

	/**
	 * @return	HttpOutputInterface
	 */
	public function createHttpOutput()
	{
		return new HttpOutput();
	}

	public function createConsoleOutput()
	{
		return new ConsoleOutput();
	}
}
