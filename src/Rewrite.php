<?php
/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the General Public License (GPL 3.0)
 * that is bundled with this package in the file LICENSE
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/GPL-3.0
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category    J!Code: Framework
 * @package     J!Code: Framework
 * @author      Jeroen Bleijenberg <jeroen@jcode.nl>
 *
 * @copyright   Copyright (c) 2017 J!Code (http://www.jcode.nl)
 * @license     http://opensource.org/licenses/GPL-3.0 General Public License (GPL 3.0)
 */
namespace Jcode\Router;

use \Exception;
use Jcode\Application;

class Rewrite
{

    protected $isSharedInstance = true;

    protected $eventId = 'router.rewrite';

    protected $rewrites = [];

    /**
     * Add an url rewrite to the application.
     * If $grace is set to false, and exception will be thrown if the source url already has a rewrite
     *
     * @param $source
     * @param $destination
     * @param bool $grace
     *
     * @return $this
     * @throws \Exception
     */
    public function addRewrite($source, $destination, $grace = true)
    {
        if (($cache = Application::getConfig()->getCacheInstance())) {
            if ($cache->exists('url.rewrites')) {
                $this->rewrites = unserialize($cache->get('url.rewrites'));
            }
        }

        if ($grace === true) {
            $this->rewrites[$source] = $destination;
        } else {
            if (!array_key_exists($source, $this->rewrites)) {
                $this->rewrites[$source] = $destination;
            } else {
                throw new Exception("An url rewrite for '{$source}' already exists'");
            }
        }

        if (($cache = Application::getConfig()->getCacheInstance())) {
            $cache->set('url.rewrites', serialize($this->rewrites));
        }

        return $this;
    }

    /**
     * Find a rewrite for the given source.
     *
     * @param $source
     *
     * @return null
     */
    public function getRewrite($source)
    {
        $urlRewrite = null;

        if (($cache = Application::getConfig()->getCacheInstance())) {
            if ($cache->exists('url.rewrites')) {
                $this->rewrites = unserialize($cache->get('url.rewrites'));
            }
        }

        foreach ($this->rewrites as $rewrite => $destination) {
            if (preg_match("#^{$rewrite}$#", $source, $matches)) {
                $urlRewrite = preg_replace("#^{$rewrite}$#", $destination, $source);
            }
        }

        return $urlRewrite;
    }
}