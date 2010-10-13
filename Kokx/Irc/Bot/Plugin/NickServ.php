<?php
/**
 * Kokx library
 *
 * @category   Kokx
 * @package    Kokx_Irc_Bot
 * @subpackage Plugin
 * @copyright  Copyright (c) 2009-2010 Pieter Kokx (http://blog.kokx.nl/)
 * @license    http://www.opensource.org/licenses/bsd-license.php   New BSD License
 */


/**
 * NickServ
 *
 * @category   Kokx
 * @package    Kokx_Irc_Bot
 * @subpackage Plugin
 * @copyright  Copyright (c) 2009-2010 Pieter Kokx (http://blog.kokx.nl/)
 * @license    http://www.opensource.org/licenses/bsd-license.php   New BSD License
 */
class Kokx_Irc_Bot_Plugin_NickServ implements Kokx_Irc_Bot_Plugin_PluginInterface
{

    /**
     * Config
     *
     * @var array
     */
    protected $_config;

    /**
     * Confirmed users
     *
     * @var array
     */
    protected $_confirmed = array();


    /**
     * Constructor
     *
     * @param string $config
     *
     * @return void
     */
    public function __construct(array $config = array())
    {
        $this->_config = $config;

        if (!isset($this->_config['nick'])) {
            $this->_config['nick'] = 'NickServ';
        }
        if (!isset($this->_config['password'])) {
            $this->_config['password'] = '';
        }
    }

    /**
     * Get the plugin's name
     *
     * @return string
     */
    public function getName()
    {
        return 'nickserv';
    }

    /**
     * Register the plugin with the dispatcher
     *
     * @param Kokx_Event_Dispatcher $dispatcher
     *
     * @return Kokx_Irc_Bot_Plugin_PluginInterface
     */
    public function register(Kokx_Event_Dispatcher $dispatcher)
    {
        $dispatcher->subscribe('376', array($this, 'motd'));
    }

    /**
     * End of the MOTD event
     *
     * @param Kokx_Event $event
     *
     * @return void
     */
    public function motd(Kokx_Event $event)
    {
        $event->getSubject()->send('IDENTIFY ' . $this->_config['password'], $this->_config['nick']);
    }
}
