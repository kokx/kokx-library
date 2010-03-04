<?php
/**
 * Kokx library
 *
 * @category   Kokx
 * @package    Kokx_Irc_Bot
 * @copyright  Copyright (c) 2009-2010 Pieter Kokx (http://blog.kokx.nl/)
 * @license    http://www.opensource.org/licenses/bsd-license.php   New BSD License
 */


/**
 * An event
 *
 * @category   Kokx
 * @package    Kokx_Irc_Bot
 * @copyright  Copyright (c) 2009-2010 Pieter Kokx (http://blog.kokx.nl/)
 * @license    http://www.opensource.org/licenses/bsd-license.php   New BSD License
 */
class Kokx_Irc_Bot
{

    /**
     * Config
     *
     * @var array
     */
    protected $_config = array();

    /**
     * IRC Client class
     *
     * @var Kokx_Irc_Client
     */
    protected $_client;

    /**
     * Loaded plugins
     *
     * @var array
     */
    protected $_plugins;


    /**
     * Constructor
     *
     * @param Kokx_Irc_Client $client
     * @param array|Zend_Config $config
     *
     * @return void
     */
    public function __construct(Kokx_Irc_Client $client, $config = array())
    {
        $this->_client = $client;

        if ($config instanceof Zend_Config) {
            $config = $config->toArray();
        }

        if (!empty($config['plugins'])) {
            if (!is_array($config['plugins'])) {
                $config['plugins'] = array($config['plugins']);
            }

            foreach ($config['plugins'] as $plugin) {
                $this->addPlugin($config['plugins']);
            }
        }

        $this->_config = $config;

        // initialize plugins
        $this->_init();
    }

    /**
     * Get the client instance
     *
     * @return Kokx_Irc_Client
     */
    public function getClient()
    {
        return $this->_client;
    }

    /**
     * Add a plugin
     *
     * @param string|Kokx_Irc_Bot_Plugin_PluginInterface $plugin
     *
     * @return Kokx_Irc_Bot
     */
    public function addPlugin($plugin)
    {
        if (is_string($plugin)) {
            $plugin = $this->_loadPlugin($plugin);
        }

        $this->_addPlugin($plugin);

        return $this;
    }

    /**
     * Load a plugin
     *
     * @param string $plugin
     *
     * @return Kokx_Irc_Bot_Plugin_PluginInterface
     */
    protected function _loadPlugin($plugin)
    {
        // load it

        // instantiate it
    }

    /**
     * Add a plugin
     *
     * @param Kokx_Irc_Bot_Plugin_PluginInterface $plugin
     *
     * @return void
     */
    protected function _addPlugin(Kokx_Irc_Bot_Plugin_PluginInterface $plugin)
    {
        $this->_plugins[$plugin->getName()] = $plugin;

        $plugin->register($this->getClient()->getDispatcher());
    }
}
