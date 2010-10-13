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
     * Confirmed users
     *
     * @var array
     */
    protected $_confirmed = array();

    /**
     * Client
     *
     * @var Kokx_Irc_Client
     */
    protected $_client;


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

        if (!isset($this->_config['NickServ'])) {
            $this->_config['NickServ'] = 'NickServ';
        }
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

        $dispatcher->subscribe('part', array($this, 'part'));
        $dispatcher->subscribe('quit', array($this, 'part'));

        $dispatcher->subscribe('join', array($this, 'join'));
        $dispatcher->subscribe('353', array($this, 'names'));
    }

    /**
     * PART/QUIT event
     *
     * @param Kokx_Event $event
     *
     * @return void
     */
    public function part(Kokx_Event $event)
    {
        unset($this->_confirmed[$event['nick']]);
    }

    /**
     * JOIN event
     *
     * @param Kokx_Event $event
     *
     * @return void
     */
    public function join(Kokx_Event $event)
    {
        $this->_client = $event->getSubject();

        $this->_confirm($event['nick']);
    }

    /**
     * NAMES event
     *
     * @param Kokx_Event $event
     *
     * @return void
     */
    public function names(Kokx_Event $event)
    {
        $this->_client = $event->getSubject();

        $names = explode(' ', $event['message']);

        foreach ($names as $nick) {
            switch ($nick[0]) {
                case '@':
                case '&':
                case '%':
                case '+':
                    $nick = substr($nick, 1);
                default:
                    $this->_confirm($nick);
            }
        }
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
        $this->_client = $event->getSubject();

        if ($event['nick'] == $this->_config['NickServ']) {
            $matches = array();
            // we were busy confirming a nick
            if (preg_match('/Nickname: (?<nick>[a-zA-Z]([a-zA-Z0-9{}\[\]\\`^-])) << ONLINE >>/i', $event['message'], $matches)) {
                if (isset($this->_confirmed[$matches['nick']])) {
                    $this->_confirmed[$matches['nick']] = true;
                }
            }
        }
    }

    /**
     * Confirm a user
     *
     * @param string $nick
     *
     * @return void
     */
    protected function _confirm($nick)
    {
        $this->_confirmed[$nick] = false;

        // ask confirmation to NickServ
        $this->_client->send('info ' . $nick, $this->_config['NickServ']);
    }
}
