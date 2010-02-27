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
class Kokx_Event implements ArrayAccess
{

    /**
     * The event's name
     *
     * @var string
     */
    protected $_name;

    /**
     * The event's subject
     *
     * @var mixed
     */
    protected $_subject;

    /**
     * Event parameters
     *
     * @var array
     */
    protected $_parameters = array();


    /**
     * Constructor
     *
     * @param mixed $subject
     * @param string $name
     * @param array $parameters
     *
     * @return void
     */
    public function __construct($subject, $name, array $parameters = array())
    {
        $this->_subject    = $subject;
        $this->_name       = $name;
        $this->_parameters = $parameters;
    }

    /**
     * Get the event's subject
     *
     * @return mixed
     */
    public function getSubject()
    {
        return $this->_subject;
    }

    /**
     * Get the event's name
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Check if the offset exists
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->_parameters[$offset]);
    }

    /**
     * Get a parameter
     *
     * @param mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->_parameters[$offset];
    }

    /**
     * Set a parameter
     *
     * @param mixed $offset
     * @param mixed $value
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->_parameters[$offset] = $value;
    }

    /**
     * Unset a parameter
     *
     * @param mixed $offset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->_parameters[$offset]);
    }
}
