<?php
/**
 * Kokx library
 *
 * @category   Kokx
 * @package    Kokx_Db
 * @copyright  Copyright (c) 2009-2010 Pieter Kokx (http://blog.kokx.nl/)
 * @license    http://www.opensource.org/licenses/bsd-license.php   New BSD License
 */


/**
 * Broker class for Zend_Db objects, this makes sure that we will always have
 * a connection with a server.
 *
 * @category   Kokx
 * @package    Kokx_Db
 * @copyright  Copyright (c) 2009-2010 Pieter Kokx (http://blog.kokx.nl/)
 * @license    http://www.opensource.org/licenses/bsd-license.php   New BSD License
 */
class Kokx_Db_Broker
{

    /**
     * Database adapter
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_adapter;

    /**
     * Database adapter name
     *
     * @var string
     */
    protected $_adapterName;

    /**
     * Database options
     *
     * @var array
     */
    protected $_options;


    /**
     * Constructor
     *
     * @param string $adapter
     * @param array $options
     *
     * @return void
     */
    public function __construct($adapter, array $options)
    {
        $this->_adapterName = $adapter;
        $this->_options     = $options;

        $this->_connect();
    }

    /**
     * Connect
     *
     * @return void
     */
    protected function _connect()
    {
        $this->_adapter = Zend_Db::factory($this->_adapterName, $this->_options);
    }

    /**
     * Test the connection, and if needed, reconnect.
     *
     * @return void
     */
    protected function _test()
    {
        try {
            $this->_adapter->query('SELECT 1');
        } catch (Exception $e) {
            // there is an error, so we reconnect
            $this->_adapter = null;

            $this->_connect();
        }
    }

    /**
     * Get the adapter. This ensures that the connection is there.
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function getAdapter()
    {
        $this->_test();

        return $this->_adapter;
    }
}
