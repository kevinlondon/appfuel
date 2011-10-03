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
namespace Example\App\Filter;

use AppFuel\App\Filter\AbstractFilter,
	Appfuel\Framework\App\Context\ContextInterface,
    Appfuel\Framework\App\Filter\InterceptingFilterInterface;

/**
 * Designed for unit tests this filter simply adds a string to a variable
 * and calls the next filter. Its intended to be the first filter in a chain
 */
class PreFilterA extends AbstractFilter implements InterceptingFilterInterface
{
    public function __construct(ContextInterface $context = null)
    {
        parent::__construct('pre', $context);
    }

    public function filter(ContextInterface $context)
    {
        $context->add('test-var', 'first-pre-filterA');
        return $this->next($context);
    }
}