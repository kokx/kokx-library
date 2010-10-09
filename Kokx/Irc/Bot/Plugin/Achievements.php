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
 * Achievements
 *
 * @category   Kokx
 * @package    Kokx_Irc_Bot
 * @subpackage Plugin
 * @copyright  Copyright (c) 2009-2010 Pieter Kokx (http://blog.kokx.nl/)
 * @license    http://www.opensource.org/licenses/bsd-license.php   New BSD License
 */
class Kokx_Irc_Bot_Plugin_Achievements implements Kokx_Irc_Bot_Plugin_PluginInterface
{

    /**
     * Config
     *
     * @var array
     */
    protected $_config;


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
    }

    /**
     * Get the plugin's name
     *
     * @return string
     */
    public function getName()
    {
        return 'achievements';
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
        $dispatcher->subscribe('privmsg', array($this, 'privmsg'));
    }

    /**
     * PRIVMSG event
     *
     * @param Kokx_Event $event
     *
     * @return void
     */
    public function privmsg(Kokx_Event $event)
    {
        $matches = array();

        if (($event['nick'] == $this->_config['NickServ']['name'])
        && ($event['host'] == $this->_config['NickServ']['host'])) {
        }
    }
}
