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
 * An event
 *
 * @category   Kokx
 * @package    Kokx_Irc_Bot
 * @subpackage Plugin
 * @copyright  Copyright (c) 2009-2010 Pieter Kokx (http://blog.kokx.nl/)
 * @license    http://www.opensource.org/licenses/bsd-license.php   New BSD License
 */
class Kokx_Irc_Bot_Plugin_GitHub implements Kokx_Irc_Bot_Plugin_PluginInterface
{

    /**
     * Input file, the GitHub commit hook writes into this file
     *
     * @var string
     */
    protected $_inputFile;

    /**
     * Last run
     *
     * @var int
     */
    protected $_lastRun;

    /**
     * Target to send to
     *
     * @var string
     */
    protected $_target;


    /**
     * Constructor
     *
     * @param string $inputFile input file where the github commit hook stores its data
     * @param string $target target to send the github data to
     *
     * @return void
     */
    public function __construct($inputFile, $target)
    {
        $this->_inputFile = $inputFile;

        $this->_lastRun = time();

        $this->_target = $target;
    }

    /**
     * Get the plugin's name
     *
     * @return string
     */
    public function getName()
    {
        return 'github';
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
        $dispatcher->subscribe('timer', array($this, 'timer'));
    }

    /**
     * Timer
     *
     * @param Kokx_Event $event
     *
     * @return void
     */
    public function timer(Kokx_Event $event)
    {
        // only run this every 2 secons so we don't overload the server too much
        if ($this->_lastRun + 2 < time()) {
            // check if there are new messagesk
            $this->_lastRun = time();

            // check the file
            $data = trim(file_get_contents($this->_inputFile));

            if (empty($data)) {
                // nothing to see, move along ma'am
                return;
            }

            $data = Zend_Json::decode($data);

            $client = $event->getSubject();

            if (count($data['commits']) == 1) {
                // Firal: kokx pushed one commit on master ∆ Updated Zend Framework submodule. ∆ http://github.com/kokx/Firal/commit/5b32449e567dbf4d4bb9f8e81954bab0d9640a92
                $message = $data['repository']['name'] . ': ' . $data['repository']['owner']['name']
                         . ' pushed one commit on ' . $this->_getBranch($data)
                         . ' ∆ ' . str_replace(array("\r", "\n"), ' | ', $data['commits'][0]['message'])
                         . ' ∆ ' . $data['commits'][0]['url'];
            } else {
                // Firal: kokx pushed 2 commits on master ∆ http://github.com/kokx/Firal/compare/816fee7544eda668...452768c13c94af75e67
                $message = $data['repository']['name'] . ': ' . $data['repository']['owner']['name']
                         . ' pushed ' . count($data['commits']) . ' commits on ' . $this->_getBranch($data)
                         . ' ∆ ' . $data['repository']['url'] . '/compare/'
                         . substr($data['before'], 0, 12) . '...' . substr($data['after'], 0, 12);
            }

            $client->send($message, $this->_target);

            file_put_contents($this->_inputFile, '');
        }
    }

    /**
     * Get the commit branch
     *
     * @param array $data
     *
     * @return void
     */
    protected function _getBranch(array $data)
    {
        $matches = array();
        preg_match('#^refs/heads/(?<branch>.+)$#i', $data['ref'], $matches);

        return $matches['branch'];
    }
}
