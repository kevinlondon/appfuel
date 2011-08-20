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
namespace Appfuel\App;

use Appfuel\Framework\Registry,
	Appfuel\Framework\Exception,
	Appfuel\Framework\File\FileManager,
	Appfuel\Framework\App\ContextInterface,
	Appfuel\Framework\App\AppFactoryInterface,
	Appfuel\Framework\App\FrontControllerInterface;

/**
 * The AppManager is used to encapsulate the logic need to build an App object,
 * the application object used to run a user request. Because This is the fist
 * file the calling code is likely to use it will not be governed by an 
 * interface. It will also hold the responsibility of initializing the system.
 */
class AppManager
{
	/**
	 * Flag used to determine if dependencies have been looded
	 * @var bool
	 */
	static protected$isLoaded = false;

	/**
	 * Front controller used dispatching and rendering of the app message
	 * @var FrontController
	 */
	protected $front = null;

	/**
	 * Relative path to the class file that loads appfuel dependencies
	 * @var string
	 */
	protected $dependFile = 'Appfuel/App/Dependency.php';

	/**
	 * Absolute path to the base of the application
	 * @vat string
	 */
	protected $base = null;

	/**
	 * Relative path to the config file
	 * @var string
	 */
	protected $configFile = 'config/app.ini';


	/**
	 * @param	string	$basePath
	 * @param	string	$configFile
	 * @return	AppManager
	 */	
	public function __construct($base, 
								$configFile = null, 
								AppFactoryInterface $factory = null)
	{
		$this->setBasePath($base);
		if (null !== $configFile) {
			$this->setConfigFile($configFile);
		}

		if (! self::isDependencyLoaded()) {
			$this->loadDependency();
		}

		if (null === $factory) {
			$factory = $this->createAppFactory();
		}
		$this->setAppFactory($factory);
	}

	/**
	 * @return	string
	 */
	public function getBasePath()
	{
		return $this->base;
	}

	/**
	 * @return	string
	 */
	public function getDependencyFile()
	{
		return $this->dependFile;
	}

	/**
	 * @return	string
	 */
	public function getConfigFile()
	{
		return $this->configFile;
	}

	/**
	 * @return	bool
	 */
	static public function isDependencyLoaded()
	{
		return self::$isLoaded;
	}

	/**
	 * Resolves the path to the Dependecy class and loads app fuel dependent
	 * files. Note that these files are located at the lib directory off the
	 * base directory
	 *
	 * @param	string	$basePath
	 * @return	NULL
	 */
	public function loadDependency()
	{
		$lib  = "{$this->getBasePath()}/lib";
		$file = "{$lib}/{$this->getDependencyFile()}";
		if (! file_exists($file)) {
			throw new \Exception("Dependency file could not be found ($file)");
		}
		require_once $file;

		$depend = new Dependency($lib);
		$depend->load();

		self::$isLoaded = TRUE;
	}

	/**
	 * Initialize the framework by creating the intializer which runs 
	 * init tasks defined in the app.ini. Assign the front controller.
	 * 
	 * @return	null
	 */
	public function initialize()
	{
		/* initialize the registry with the app base path */
		$base = $this->getBasePath();
		Registry::initialize(array('base-path' => $base));
		
		$file = "{$base}/{$this->getConfigFile()}";
		$data = FileManager::parseIni($file);
		if (is_array($data) && ! empty($data)) {
			Registry::load($data);	
		}

		$factory = $this->getAppFactory();
		$init = $factory->createInitializer();
		$init->initialize();

		$this->front = $factory->createFrontController();
	}

	/**
	 * @param	string	$base
	 * @return	null
	 */	
	protected function setBasePath($base)
	{
		if (empty($base) || ! is_string($base)) {
			throw new \Exception("Invalid base path: must be non empty string");
		}

		if (! defined('AF_BASE_PATH')) {
			define('AF_BASE_PATH', $base);
		}

		$this->base = AF_BASE_PATH;
	}

	/**
	 * @param	string	$file
	 * @return	null
	 */
	protected function setConfigFile($file)
	{
		if (empty($file) || ! is_string($file)) {
			throw new \Exception("Invalid file path: must be non empty string");
		}
	
		$this->configFile = $file;
	}

	/**
	 * @return	AppFactoryInterface
	 */
	protected function createAppFactory()
	{
		return new AppFactory();
	}

	/**
	 * @param	AppFactoryInterface		$factory
	 * @return	null
	 */
	protected function setAppFactory(AppFactoryInterface $factory)
	{
		$this->appFactory = $factory;
	}

	/**
	 * @return	AppFactoryInterface
	 */
	protected function getAppFactory()
	{
		return $this->appFactory;
	}
}