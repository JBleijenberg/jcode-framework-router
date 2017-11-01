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

use Jcode\Application;

class Front
{

    protected $isSharedInstance = true;

    protected $eventId = 'router.front';

    /**
     * @var \Jcode\Router\Http\Request
     */
    protected $request;

    /**
     * @var \Jcode\Router\Http\Response
     */
    protected $response;

    public function dispatch()
    {
        $this->response = Application::getClass('Jcode\Router\Http\Response');
        $this->request = Application::getClass('Jcode\Router\Http\Request');

        $this->request->buildHttpRequest($this->response);

        return $this;
    }

    /**
     * @return \Jcode\Router\Http\Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return \Jcode\Router\Http\Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}