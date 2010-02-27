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
            $data = socket_read($this->_socket, 10240);
            if (!empty($data)) {
                echo $data . "\n";
            }

            usleep(1000);
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
