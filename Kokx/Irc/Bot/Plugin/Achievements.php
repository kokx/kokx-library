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

    const NICK_REGEX = '[a-zA-Z][a-zA-Z0-9{}\[\]\\`^-]*';

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
     * Database adapter
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;


    /**
     * Constructor
     *
     * @param Zend_Db_Adapter_Abstract $db
     * @param string $config
     *
     * @return void
     */
    public function __construct(Zend_Db_Adapter_Abstract $db, array $config = array())
    {
        $this->_db = $db;

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
        $dispatcher->subscribe('notice', array($this, 'notice'));

        $dispatcher->subscribe('part', array($this, 'part'));
        $dispatcher->subscribe('quit', array($this, 'part'));

        $dispatcher->subscribe('join', array($this, 'join'));
        // this event gives the names of the people currently in this channel
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

        $matches = array();

        if (preg_match('/^!check (?<nick>' . self::NICK_REGEX . ')/i', $event['message'], $matches)) {
            // check if a certain nick is confirmed
            if ($this->_isAuthed($matches['nick'])) {
                $this->_client->send($matches['nick'] . ' is authed!', $event['target']);
            } else {
                $this->_client->send($matches['nick'] . ' fails!', $event['target']);
            }
        }
    }

    /**
     * NOTICE event
     *
     * @param Kokx_Event $event
     *
     * @return void
     */
    public function notice(Kokx_Event $event)
    {
        if ($event['nick'] == $this->_config['NickServ']) {
            // yay, nickserv is contacting us
            $matches = array();

            // check if we were busy confirming a nick
            if (preg_match('/Nickname: (?<nick>' . self::NICK_REGEX . ') << ONLINE >>/i', $event['message'], $matches)) {
                if (isset($this->_confirmed[$matches['nick']])) {
                    $this->_confirmed[$matches['nick']] = true;
                }
            }
        }
    }

    /**
     * Check if a user is authed
     *
     * @param string $nick
     *
     * @return bool
     */
    protected function _isAuthed($nick)
    {
        return isset($this->_confirmed[$nick]) && $this->_confirmed[$nick];
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
