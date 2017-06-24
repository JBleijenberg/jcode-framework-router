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
namespace Jcode\Router\Http;

class Response
{

    protected $isSharedInstance = true;

    protected $eventId = 'router.response';

    protected $parameterPrefix = '?';

    /**
     * Http response code
     *
     * @var int
     */
    protected $httpCode = 200;

    /**
     * Location for redirects
     *
     * @var
     */
    protected $location;

    /**
     * Default content type
     *
     * @var string
     */
    protected $contentType = 'Content-Type: text/html';

    /**
     * Set new http response code;
     *
     * @param $code
     *
     * @return $this
     */
    public function setHttpCode($code)
    {
        if (intval($code)) {
            $this->httpCode = $code;
        }

        return $this;
    }

    /**
     * Set new location
     *
     * @param $location
     *
     * @return $this
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Redirect to given location
     *
     * @param $location
     * @param array $params
     * @param int $httpCode
     */
    public function redirect($location, $httpCode = 302)
    {
        if (!empty($params)) {
            $location .= $this->parameterPrefix . http_build_query($params);
        }

        $this->setLocation($location);
        $this->setHttpCode($httpCode);
        $this->dispatch();
    }

    /**
     * Return response to the browser
     *
     * @return $this
     */
    public function dispatch()
    {
        header($this->contentType, true, $this->httpCode);

        if (!empty($this->location)) {
            header("Location: {$this->location}");
        }

        return $this;
    }
}