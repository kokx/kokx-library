<?php
/**
 * Kokx library
 *
 * @category   Kokx
 * @package    Kokx_Event
 * @copyright  Copyright (c) 2009-2010 Pieter Kokx (http://blog.kokx.nl/)
 * @license    http://www.opensource.org/licenses/bsd-license.php   New BSD License
 */


/**
 * An event
 *
 * @category   Kokx
 * @package    Kokx_Event
 * @copyright  Copyright (c) 2009-2010 Pieter Kokx (http://blog.kokx.nl/)
 * @license    http://www.opensource.org/licenses/bsd-license.php   New BSD License
 */
class Kokx_Event_Dispatcher
{

    /**
     * Event listeners
     *
     * @var array
     */
    protected $_listeners = array();


    /**
     * Subscribe to an event
     *
     * @param string $name Name of the event
     * @param callback $callback Callback for the event
     *
     * @return void
     */
    public function subscribe($name, $callback)
    {
        if (!is_callable($callback)) {
            throw new Kokx_Event_InvalidArgumentException("You need to provide a valid callback.");
        }
        $this->_listeners[$name][] = $callback;
    }

    /**
     * Unsubscribe from an event
     *
     * @param string $name
     * @param callback $callback
     *
     * @return void
     */
    public function unsubscribe($name, $callback)
    {
        if (!empty($this->_listeners[$name])) {
            foreach ($this->_listeners[$name] as $key => $listener) {
                if ($callback == $listener) {
                    unset($this->_listeners[$name][$key]);
                    return;
                }
            }
        }
    }

    /**
     * Trigger an event
     *
     * @param Kokx_Event $event
     *
     * @return void
     */
    public function trigger(Kokx_Event $event)
    {
        if (!empty($this->_listeners[$event->getName()])) {
            foreach ($this->_listeners[$event->getName()] as $listener) {
                call_user_func($listener, $event);
            }
        }
    }
}
