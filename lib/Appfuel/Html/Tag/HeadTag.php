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
namespace Appfuel\Html\Tag;

use InvalidArgumentException;

/**
 * Handles all tags associated with the html head.
 */
class HeadTag extends GenericTag implements HeadTagInterface
{
	/**
	 * Html head title tag
	 * @var GenericTagInterface
	 */
	protected $title = null;

	/**
	 * Html base tag 
	 * @var GenericTagInterface
	 */
	protected $base = null;

	/**
	 * List of meta tags
	 * @var	array
	 */
	protected $meta = array();

	/**
	 * List of link tags
	 * @var array
	 */
	protected $cssTags = array();

	/**
	 * List of ScriptTags
	 * @var array
	 */
	protected $scripts = array();

	/**
	 * @var array
	 */
	protected $contentOrder = array(
		'Title', 
		'Base', 
		'Meta', 
		'CssTags', 
		'Scripts', 
	);

	/**
	 * @param	string	$data	content for the title
	 * @return	Title
	 */
	public function __construct($sep = null)
	{
		if (null === $sep) {
			$sep = PHP_EOL;
		}

		parent::__construct('head');
		$this->setContentSeparator($sep)
			 ->setTitle(new TitleTag());
	}

	/**
	 * @return	GenericTagInterface
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param	GenericTagInterface $tag
	 * @return	HeadTag
	 */
	public function setTitle(GenericTagInterface $tag)
	{
		if ('title' !== $tag->getTagName()) {
			$err = 'must have a tag name of -(title)';
			throw new InvalidArgumentException($err);
		}
		
		$this->title = $tag;
		return $this;
	}

	/**
	 * @param	string	$text
	 * @param	string	$action	
	 * @return	HeadTag
	 */
	public function setTitleText($text, $action = 'append')
	{
		$this->getTitle()
			 ->addContent($text, $action);

		return $this;
	}

	/**
	 * @param	string	$char
	 * @return	HeadTag
	 */
	public function setTitleSeparator($char)
	{
		$this->getTitle()
			 ->setContentSeparator($char);

		return $this;	
	}

	/**
	 * @return	bool	
	 */
	public function isTitle()
	{
		return $this->title instanceof GenericTagInterface;
	}

	/**
	 * @return	GenericTagInterface
	 */
	public function getBase()
	{
		return $this->base;
	}

	/**
	 * @param	GenericTagInterface $tag
	 * @return	HeadTag
	 */
	public function setBase(GenericTagInterface $tag)
	{
		if ('base' !== $tag->getTagName()) {
			$err = 'must have a tag name of -(base)';
			throw new InvalidArgumentException($err);
		}
		
		$this->base = $tag;
		return $this;
	}

	/**
	 * @return	bool	
	 */
	public function isBase()
	{
		return $this->base instanceof GenericTagInterface;
	}

	/**
	 * @throws	InvalidArgumentException
	 * @param	GenericTagInterface $tag
	 * @return	HeadTag
	 */
	public function addMeta(GenericTagInterface $tag)
	{
		if ('meta' !== $tag->getTagName()) {
			$err = 'must have a tag name of -(meta)';
			throw new InvalidArgumentException($err);
		}

		$this->meta[] = $tag;
		return $this;
	}

	/**
	 * @return	array
	 */
	public function getMeta()
	{
		return $this->meta;
	}

	/**
	 * @return	bool
	 */
	public function isMeta()
	{
		return count($this->meta) > 0;
	}

	/**
	 * @throws	InvalidArgumentException
	 * @param	GenericTagInterface $tag
	 * @return	HeadTag
	 */
	public function addCssTag(GenericTagInterface $tag)
	{
		$tagName = $tag->getTagName();
		if ('link' !== $tagName && 'style' !== $tagName) {
			$err = 'must have a tag name of -(link|style)';
			throw new InvalidArgumentException($err);
		}

		$this->cssTags[] = $tag;
		return $this;
	}

	/**
	 * @return	array
	 */
	public function getCssTags()
	{
		return $this->cssTags;
	}

	/**
	 * @return	bool
	 */
	public function isCssTags()
	{
		return count($this->cssTags) > 0;
	}

	/**
	 * @throws	InvalidArgumentException
	 * @param	GenericTagInterface $tag
	 * @return	HeadTag
	 */
	public function addScript(GenericTagInterface $tag)
	{
		if ('script' !== $tag->getTagName()) {
			$err = 'must have a tag name of -(script)';
			throw new InvalidArgumentException($err);
		}

		$this->scripts[] = $tag;
		return $this;
	}

	/**
	 * @return	array
	 */
	public function getScripts()
	{
		return $this->scripts;
	}

	/**
	 * @return	bool
	 */
	public function isScripts()
	{
		return count($this->scripts) > 0;
	}

	/**
	 * @return	array
	 */
	public function getContentOrder()
	{
		return $this->contentOrder;
	}

	public function setContentOrder(array $items)
	{
		$this->contentOrder = $items;
		return $this;
	}

	/**
	 * @return	string
	 */
	public function build()
	{
		$content = $this->getTagContent();
		$attrs   = $this->getTagAttributes();

		$order = $this->getContentOrder();
		foreach ($order as $item) {
			$getter = 'get' . ucfirst($item);
			if (! method_exists($this, $getter)) {
				continue;
			}
			$tag = $this->$getter();
			if (is_array($tag) && ! empty($tag)) {
				foreach ($tag as $listTag) {
					$content->add($listTag);
				}
			}
			else if ($tag instanceof GenericTagInterface) {
				$content->add($tag);
			}
		}

		return $this->buildTag($content, $attrs);
	}
}
