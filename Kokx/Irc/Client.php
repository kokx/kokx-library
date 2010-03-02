<?php
/**
 * Kokx library
 *
 * @category   Kokx
 * @package    Kokx_Irc_Client
 * @copyright  Copyright (c) 2009-2010 Pieter Kokx (http://blog.kokx.nl/)
 * @license    http://www.opensource.org/licenses/bsd-license.php   New BSD License
 */


/**
 * An event
 *
 * @category   Kokx
 * @package    Kokx_Irc_Client
 * @copyright  Copyright (c) 2009-2010 Pieter Kokx (http://blog.kokx.nl/)
 * @license    http://www.opensource.org/licenses/bsd-license.php   New BSD License
 */
class Kokx_Irc_Client
{

    /**
     * Message types
     */
    const TYPE_PRIVMSG    = 'privmsg';
    const TYPE_NOTICE     = 'notice';
    const TYPE_CTCP       = 'ctcp';
    const TYPE_CTCP_REPLY = 'ctcp_reply';


    /**
     * Config array
     *
     * @var array
     */
    protected $_config = array();

    /**
     * Socket pointer
     *
     * @var resource
     */
    protected $_socket;

    /**
     * Address
     *
     * @var string
     */
    protected $_address;

    /**
     * Port
     *
     * @var int
     */
    protected $_port;

    /**
     * Nickname
     *
     * @var string
     */
    protected $_nickname;

    /**
     * Username
     *
     * @var string
     */
    protected $_username;

    /**
     * Real name
     *
     * @var string
     */
    protected $_realname;

    /**
     * Event dispatcher
     *
     * @var Kokx_Event_Dispatcher
     */
    protected $_dispatcher;


    /**
     * Constructor
     *
     * @param array|Zend_Config $config
     *
     * @return void
     */
    public function __construct($config)
    {
        if ($config instanceof Zend_Config) {
            $config = $config->toArray();
        }
        if (!is_array($config)) {
            throw new Kokx_Irc_Client_InvalidArgumentException("The first argument must be a Zend_Config object or array");
        }

        if (!isset($config['address'])) {
            throw new Kokx_Irc_Client_InvalidArgumentException("You must specify an address where I can connect to.");
        }
        if (!isset($config['port'])) {
            $config['port'] = 6667;
        }
        if (!isset($config['nickname'])) {
            throw new Kokx_Irc_Client_InvalidArgumentException("You must specify a nickname for the client.");
        }
        if (!isset($config['username'])) {
            $config['username'] = $config['nickname'];
        }
        if (!isset($config['realname'])) {
            $config['realname'] = $config['username'];
        }

        $this->_address = $config['address'];
        $this->_port    = $config['port'];

        $this->_config = $config;
    }

    /**
     * Set the event dispatcher
     *
     * @param Kokx_Event_Dispatcher $dispatcher
     *
     * @return Kokx_Irc_Client
     */
    public function setDispatcher(Kokx_Event_Dispatcher $dispatcher)
    {
        $this->_dispatcher = $dispatcher;

        return $this;
    }

    /**
     * Get the event dispatcher
     *
     * @return Kokx_Event_Dispatcher
     */
    public function getDispatcher()
    {
        if (null === $this->_dispatcher) {
            $this->_dispatcher = new Kokx_Event_Dispatcher();
        }

        return $this->_dispatcher;
    }

    /**
     * Connect and start listening
     *
     * @return void
     */
    public function connect()
    {
        $this->_connect();

        $this->_listen();
    }

    /**
     * Send a message to the server
     *
     * @param string $message
     * @param string $target
     * @param string $type
     *
     * @return void
     */
    public function send($message, $target, $type = self::TYPE_PRIVMSG)
    {
        switch ($type) {
            case self::TYPE_CTCP:
                $message = chr(1) . $message . chr(1);
            case self::TYPE_PRIVMSG:
                $message = 'PRIVMSG ' . $target . ' :' . $message;
                break;
            case self::TYPE_CTCP_REPLY:
                $message = chr(1) . $message . chr(1);
            case self::TYPE_NOTICE:
                $message = 'NOTICE ' . $target . ' :' . $message;
                break;
        }

        $this->sendRaw($message);
    }

    /**
     * Send a CTCP action
     *
     * @param string $message
     * @param string $target
     *
     * @return void
     */
    public function action($message, $target)
    {
        $this->send('ACTION ' . $message, $target, self::TYPE_CTCP);
    }

    /**
     * Join one or more channels
     *
     * @param array|string $channel
     *
     * @return void
     */
    public function join($channel)
    {
        if (is_array($channel)) {
            array_map(array($this, 'join'), $channel);
        } else {
            $this->sendRaw('JOIN ' . $channel);
        }
    }

    /**
     * Part one or more channels
     *
     * @param array|string $channel
     *
     * @return void
     */
    public function part($channel)
    {
        if (is_array($channel)) {
            array_map(array($this, 'part'), $channel);
        } else {
            $this->sendRaw('PART ' . $channel);
        }
    }

    /**
     * Send a raw message to the server
     *
     * @param string $message
     *
     * @return void
     */
    public function sendRaw($message)
    {
        $this->_connect();

        socket_write($this->_socket, $message . "\r\n", strlen($message) + 2);
    }

    /**
     * Connect to an IRC server
     *
     * @return bool
     */
    protected function _connect()
    {
        if (null === $this->_socket) {
            $this->_socket = socket_create(AF_INET, SOCK_STREAM, 0);

            socket_connect($this->_socket, $this->_address, $this->_port);

            socket_set_nonblock($this->_socket);

            $this->_sendNick();
            $this->_sendUser();
        }
    }

    /**
     * Listen
     *
     * @return void
     */
    protected function _listen()
    {
        while (true) {
            $lines = array();
            $data  = socket_read($this->_socket, 10240);

            if (!empty($data)) {
                $lines = explode("\n", $data);

                // loop through all the commands
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line)) {
                        $event = $this->_createEvent($line);
                        if (false !== $event) {
                            $this->_processEvent($event);
                        }
                    }
                }
            }

            // unset some variables to make sure we don't have a memory leak
            unset($lines, $line, $data, $event);

            usleep(2500);
        }
    }

    /**
     * Process an event
     *
     * @param Kokx_Event $event
     *
     * @return void
     */
    protected function _processEvent(Kokx_Event $event)
    {
        switch ($event->getName()) {
            case 'ping':
                // send a pong back
                $this->sendRaw('PONG :' . $event['message']);
                break;
            case 'ctcp_version':
                if (isset($this->_config['version'])) {
                    $version = $this->_config['version'];
                } else {
                    $version = 'Kokx_Irc_Client';
                }
                $this->send('VERSION ' . $version, $event['nick'], self::TYPE_CTCP_REPLY);
                break;
            default:
                // for all other events, we use the dispatcher
                $this->getDispatcher()->trigger($event);
                break;
        }
    }

    /**
     * Create an event from a data line
     *
     * @param string $data
     */
    protected function _createEvent($line)
    {
        $regex   = "^(?::(?<hostspec>((?<nick>[^!]+)(?:!))?(?<host>[^ ]+)) )"
                 . "?(?<command>[A-Z0-9]*)[ ]*"
                 . "(?:(?<target>[^: ][^ ]*))?"
                 . "(?<params>( [^: ][^ ]*){0,12})"
                 . "(?: :(?<message>.*))?$";
        $matches = array();

        // check if it is a correct command, otherwise we just ignore it silently
        if (!preg_match('/' . $regex . '/i', $line, $matches)) {
            return false;
        }
        if (!isset($matches['message'])) {
            $matches['message'] = '';
        }

        // check if it is a CTCP message
        if (!empty($matches['message'])
            && ($matches['message'][0] == chr(1))
            && ($matches['message'][strlen($matches['message']) - 1] == chr(1))
        ) {
            // CTCP message
            $ctcpMatches = array();
            preg_match('/^\001(?<command>[a-zA-Z]*)(?: ?(?<message>.*))?\001$/i', $matches['message'], $ctcpMatches);
            $params = array(
                'line'         => $line,
                'hostspec'     => $matches['hostspec'],
                'nick'         => $matches['nick'],
                'host'         => $matches['host'],
                'command'      => 'CTCP',
                'ctcp_command' => $ctcpMatches['command'],
                'target'       => $matches['target'],
                'params'       => explode(' ', trim($matches['params'])),
                'message'      => $ctcpMatches['message']
            );
            return new Kokx_Event($this, 'ctcp_' . strtolower($ctcpMatches['command']), $params);
        } else {
            $params = array(
                'line'     => $line,
                'hostspec' => $matches['hostspec'],
                'nick'     => $matches['nick'],
                'host'     => $matches['host'],
                'command'  => $matches['command'],
                'target'   => $matches['target'],
                'params'   => explode(' ', trim($matches['params'])),
                'message'  => $matches['message']
            );
            return new Kokx_Event($this, strtolower($params['command']), $params);
        }
    }

    /**
     * Send the NICK command
     *
     * @return void
     */
    protected function _sendNick()
    {
        $this->sendRaw('NICK ' . $this->_config['nickname']);
    }

    /**
     * Send the USER command
     *
     * @return void
     */
    protected function _sendUser()
    {
        $this->sendRaw('USER ' . $this->_config['username'] . ' 8 * :' . $this->_config['realname']);
    }
}
