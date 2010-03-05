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
interface Kokx_Irc_Bot_Plugin_PluginInterface
{

    /**
     * Get the plugin's name
     *
     * @return string
     */
    public function getName();

    /**
     * Register the plugin with the dispatcher
     *
     * @param Kokx_Event_Dispatcher $dispatcher
     *
     * @return Kokx_Irc_Bot_Plugin_PluginInterface
     */
    public function register(Kokx_Event_Dispatcher $dispatcher);
}
