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
namespace Jcode\Router\Front;

use Jcode\Application;
use Jcode\DataObject;
use Jcode\Layout\Model\Reference;
use Jcode\Helper;
use Jcode\Router\Http\Request;
use Jcode\Router\Http\Response;

class Controller
{

    /**
     * Store $_POST
     *
     * @var DataObject
     */
    protected $post;

    /**
     * Store $_GET
     *
     * @var DataObject
     */
    protected $params;

    /**
     * Store $_FILES
     *
     * @var DataObject
     */
    protected $files;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @inject \Jcode\Application\Config
     * @var \Jcode\Application\Config
     */
    protected $config;

    protected $layout;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @param \Jcode\DataObject $params
     * @param \Jcode\DataObject $post
     * @param \Jcode\DataObject $files
     */
    public function preDispatch(DataObject $params, DataObject $post, DataObject $files)
    {
        $this->params = $params;
        $this->post = $post;
        $this->files = $files;
    }

    /**
     * @param null $key
     *
     * @return null|DataObject|array
     */
    public function getPost($key = null)
    {
        if ($key !== null) {
            return $this->post->getData($key);
        }

        return $this->post->getAllData();
    }

    /**
     * return $_GET object
     *
     * @return DataObject
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Return single value from $_GET
     *
     * @param $key
     *
     * @return mixed
     */
    public function getParam($key)
    {
        return $this->params->getData($key);
    }

    public function getFirstParam()
    {
        return $this->params->getData(0);
    }

    /**
     * Redirect user to given location
     *
     * @param $location
     */
    public function redirect($location, array $params = [])
    {
        $this->getResponse()->redirect($location, $params);
    }

    public function noRoute()
    {
        $this->loadLayout('noRoute');
        $this->renderLayout();

        $this->getResponse()->setHttpCode(404);
        $this->getResponse()->dispatch();
    }

    public function translate() :string
    {
        return $this->getHelper()->translate(func_get_args());
    }

    /**
     * Get helper
     *
     * @return object|\Jcode\Helper
     * @throws \Exception
     */
    public function getHelper() :Helper
    {
        return Application::getClass('Jcode\Helper');
    }

    /**
     * Forward a user to the given location
     *
     * @param $action
     * @param null $controller
     * @param null $frontName
     *
     * @return $this
     */
    public function forward($action, $controller = null, $frontName = null)
    {
        $request = $this->getRequest();

        $request->setAction($action);

        if ($controller !== null) {
            $request->setController($controller);
        }

        if ($frontName !== null) {
            $request->setFrontName($frontName);
        }

        $request->dispatch($this->getResponse());

        return $this;
    }

    public function loadLayout($path = null)
    {
        if (!$this->layout) {
            $request = $this->getRequest();
            $module = $request->getModule();

            $element = ($path !== null)
                ? $path
                : sprintf('%s::%s/%s', $module->getName(), $request->getController(), $request->getAction());

            $this->layout = Application::getClass('\Jcode\Layout\Layout')->getLayout($element);
        }

        return $this->layout;
    }

    public function renderLayout()
    {
        /** @var \Jcode\Layout\Model\Request $layout */
        if ($layout = $this->layout) {
            Application::register('current_layout', $layout);

            $root = $layout->getReference('root');

            if ($root instanceof Reference) {
                $root->render();
            }
        }
    }

    /**
     * Return response object
     *
     * @return \Jcode\Router\Http\Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Return request object
     *
     * @return \Jcode\Router\Http\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    public function postDispatch()
    {
        $this->getResponse()->dispatch();
    }

    /**
     * Check if current request is an AJAX call
     *
     * @return bool
     */
    public function isXmlHttpRequest() :bool
    {
        if (($httpx = $this->getRequest()->getServer('HTTP_X_REQUESTED_WITH'))) {
            return (strtolower($httpx) == 'xmlhttprequest');
        }

        return false;
    }
}