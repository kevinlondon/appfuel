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
namespace Appfuel\Framework;


use Appfuel\Framework\Exception,
    Appfuel\Framework\MessageInterface,
    Appfuel\Framework\Render\RenderInterface,
    Appfuel\Framework\View\TemplateInterface;

/**
 * Handle dispatching the request and outputting the response
 */
interface FrontControllerInterface
{
    /**
     * @return  Dispatcher
     */
    public function getDispatcher();

    /**
     * @return  Output
     */
    public function getRenderEngine();

    /**
     * Dispatch
     * Use the route destination to create the controller and execute the
     * its method. Check the return of method, if its a message with a 
     * distination different from the previous then dispath that one
     *
     * @param   MessageInterface $msg
     * @return  MessageInterface
     */
    public function dispatch(MessageInterface $msg);

    /**
     * Render output. Can either echo the built content or
     * return it as a string
     *
     * @param   MessageInterface    $msg   
     * @return  mixed  void|string
     */
    public function render(MessageInterface $msg);
}