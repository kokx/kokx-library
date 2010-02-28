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
    const TYPE_PRIVMSG = 'privmsg';
    const TYPE_NOTICE  = 'notice';
    const TYPE_CTCP    = 'ctcp';


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
                $message = chr(0) . $message . chr(0);
            case self::TYPE_PRIVMSG:
                $message = 'PRIVMSG ' . $target . ' :' . $message;
                break;
            case self::TYPE_NOTICE:
                $message = 'NOTICE ' . $target . ' :' . $message;
                break;
        }

        $this->sendRaw($message);
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

        socket_write($this->_socket, $message . "\n", strlen($message) + 1);
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

                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line)) {
                        $this->_processEvent($this->_createEvent($line));
                    }
                }
            }

            usleep(100);
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
            // TODO: implement other events
        }
    }

    /**
     * Create an event from a data line
     *
     * @param string $data
     */
    protected function _createEvent($line)
    {
        $regex   = "^(?::(?<hostspec>((?<nick>[^!]+)(?:!))?(?<host>[^ ]+)) )?(?<command>[A-Z]*) (?<target>[^ ]* )?:(?<message>.*)$";
        $matches = array();

        preg_match('/' . $regex . '/i', $line, $matches);

        $params = array(
            'line'     => $line,
            'hostspec' => $matches['hostspec'],
            'nick'     => $matches['nick'],
            'host'     => $matches['host'],
            'command'  => $matches['command'],
            'target'   => $matches['target'],
            'message'  => $matches['message']
        );

        return new Kokx_Event($this, strtolower($matches['command']), $params);
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
