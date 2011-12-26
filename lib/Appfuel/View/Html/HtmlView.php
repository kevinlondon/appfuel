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
namespace Appfuel\View\Html;

use Appfuel\View\ViewTemplate,
	Appfuel\View\Formatter\FileFormatter;

/**
 * Template used to generate generic html documents
 */
class HtmlViewTemplate extends FileViewTemplate
{
	/**
	 * Location of the view template file
	 * @var	string
	 */
	protected $viewTpl = null;

	/**
	 * Location of the html document template
	 * @var string
	 */
	protected $HtmlDocClass = null;

	protected $layoutCode = null;

	protected $manifest = null;

	/**
	 * @param	string				$path	relative path to template file
	 * @param	array				$data	data to be assigned
	 * @return	HtmlTemplate
	 */
	public function __construct()
	{
	}

}
