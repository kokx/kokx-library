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
     * From RFC-2812 2.3.1
     * BNF form:
     *
     * nickname = ( letter / special ) *8( letter / digit / special / "-" )
     *
     * Please note that we disobey to the maximum length of the nickname, since
     * most IRC servers allow longer nicknames in practise.
     */
    const NICK_REGEX       = '[a-zA-Z][a-zA-Z0-9{}\[\]\\`^-]*';
    const MAX_REFRESH_TIME = 60;

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
     * Last time we refreshed the confirmed users list
     *
     * @var int
     */
    protected $_lastRefresh;

    /**
     * Client
     *
     * @var Kokx_Irc_Client
     */
    protected $_client;

    /**
     * Database broker
     *
     * @var Kokx_Db_Broker
     */
    protected $_db;


    /**
     * Constructor
     *
     * @param Kokx_Db_Broker $db
     * @param string $config
     *
     * @return void
     */
    public function __construct(Kokx_Db_Broker $db, array $config = array())
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

        $this->_lastRefresh = time();
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

                // and now check if this user is in the DB too
                if (null !== $this->_getUserId($matches['nick'])) {
                    $this->_client->send('And of course I know who ' . $matches['nick'] . ' is.', $event['target']);
                } else {
                    $this->_client->send('But I don\'t actually know who the fucking fuck ' . $matches['nick'] . ' is.', $event['target']);
                }
            } else {
                $this->_client->send($matches['nick'] . ' fails!', $event['target']);
            }
        } else if (preg_match('/^!add (?<nick>' . self::NICK_REGEX . ') (?<desc>.{10,})/i', $event['message'], $matches)) {
            // validate, then use the method
            if ($this->_checkUser($event['nick'], $event['target'], true)
            && $this->_checkUser($matches['nick'], $event['target'], true)) {

                // the user is checked, now add its new achievement
                $user = $this->_getUserId($matches['nick']);

                $this->_db->getAdapter()->insert('achievements', array(
                    'user_id'     => $user,
                    'achievement' => $matches['desc']
                ));

                $this->_client->send('New achievement added!', $event['target']);
            }
        } else if (preg_match('/!list( (?<nick>' . self:: NICK_REGEX . '))?/i', $event['message'], $matches)) {
            if (empty($matches['nick'])) {
                $matches['nick'] = $event['nick'];
            }

            // show all the achievements of a user
            if (null !== ($user = $this->_getUserId($matches['nick']))) {
                $this->_client->send('The achievements of ' . $matches['nick'] . ':', $event['target']);

                foreach ($this->_getAchievements($user) as $achievement) {
                    $this->_client->send($achievement['id'] . ': '
                                       . $achievement['achievement']
                                       . ' ['
                                       . ($achievement['achieved'] == 'true' ? 'achieved' : 'in progress')
                                       . ']', $event['target']);
                }
            } else {
                $this->_client->send('I dunno who the fuck ' . $matches['nick'] . ' is.', $event['target']);
            }
        } else if (preg_match('/^!achieved (?<nick>' . self::NICK_REGEX . ') (?<id>[0-9]+)/i', $event['message'], $matches)) {
            // first check if this nick is a user
            if (($matches['nick'] != $event['nick']) && $this->_checkUser($event['nick'], $event['target'], true)
            && (null !== ($user = $this->_getUserId($matches['nick'])))) {
                // the user is checked, now check if he can unlock the achievement

                $user = $this->_getUserId($matches['nick']);

                $stmt = $this->_db->getAdapter()->prepare($this->_db->getAdapter()->select()
                                                                                  ->from('achievements', 'user_id')
                                                                                  ->where('id=:id'));

                $stmt->bindParam('id', $matches['id'], Zend_Db::PARAM_INT);

                $stmt->execute();

                // check the achievement
                if (($achievement = $stmt->fetch(Zend_Db::FETCH_ASSOC)) && ($achievement['user_id'] == $user)) {
                    $this->_db->getAdapter()->update('achievements', array(
                        'achieved' => 'true'
                    ), array('id=?' => $matches['id']));

                    $this->_client->send('ACHIEVEMENT UNLOCKED!!!!!!!!!!!!!!!', $event['target']);
                } else {
                    $this->_client->send('Someone should unlock the achievement \'EPIC FAIL\' for ' . $event['nick'] . '!', $event['target']);
                }
            }
        } else if (preg_match('/^!refresh/i', $event['message'])) {
            $this->_refresh();
            $this->_client->send('Finished refreshing the confirmed users list.', $event['target']);
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

            // check if we have confirmed a nick
            if (preg_match('/Nickname: (?<nick>' . self::NICK_REGEX . ') << ONLINE >>/i', $event['message'], $matches)) {
                $nick = strtolower($matches['nick']);
                if (isset($this->_confirmed[$nick])) {
                    $this->_confirmed[$nick] = true;
                }
            }
        }
    }

    /**
     * Check a user
     *
     * The third parameter, which is optional, defines if this command should
     * send messages to the target if $nick is not authed.
     *
     * @param string $nick
     * @param string $target
     * @param bool $sendMessage
     *
     * @return bool
     */
    protected function _checkUser($nick, $target = '', $sendMessage = false)
    {
        if ($this->_isAuthed($nick)) {
            if (null !== $this->_getUserId($nick)) {
                return true;
            }

            if ($sendMessage) {
                $this->_client->send('I dunno who the fuck ' . $nick . ' is.', $target);
            }

            return false;
        }

        if ($sendMessage) {
            $this->_client->send('I dunno who the fuck ' . $nick . ' is.', $target);
        }

        return false;
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
        return isset($this->_confirmed[strtolower($nick)]) && $this->_confirmed[strtolower($nick)];
    }

    /**
     * Get the achievements of a user
     *
     * @param int $user
     *
     * @return int
     */
    protected function _getAchievements($user)
    {
        $stmt = $this->_db->getAdapter()->prepare($this->_db->getAdapter()->select()
                                                                          ->from('achievements')
                                                                          ->where('user_id=:user')
                                                                          ->order(new Zend_Db_Expr(
                                                                              "FIELD(achieved, 'true', 'false')"
                                                                          )));

        $stmt->bindParam('user', $user, Zend_Db::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
    }

    /**
     * Get a user's ID
     *
     * @param string $nick
     *
     * @return int
     */
    protected function _getUserId($nick)
    {
        $stmt = $this->_db->getAdapter()->prepare($this->_db->getAdapter()->select()->from('users', 'id')->where('name=:nick'));

        $nick = strtolower($nick);

        $stmt->bindParam('nick', $nick, Zend_Db::PARAM_STR);

        $stmt->execute();

        if ($user = $stmt->fetch(Zend_Db::FETCH_ASSOC)) {
            return $user['id'];
        }

        return null;
    }

    /**
     * Refresh user confirmation
     *
     * @return void
     */
    protected function _refresh()
    {
        // first check if we should try to confirm the user
        if ($this->_lastRefresh + self::MAX_REFRESH_TIME < time()) {
            return;
        }

        foreach ($this->_confirmed as $nick => $confirmed) {
            if (!$confirmed) {
                // retry to confirm this user
                $this->_confirm($nick);
            }
        }

        $this->_lastRefresh = time();
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
        $this->_confirmed[strtolower($nick)] = false;

        // ask confirmation to NickServ
        $this->_client->send('info ' . $nick, $this->_config['NickServ']);
    }
}
