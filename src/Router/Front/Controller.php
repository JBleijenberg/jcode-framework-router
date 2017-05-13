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
use Jcode\Object;
use Jcode\Router\Http\Request;
use Jcode\Router\Http\Response;

class Controller
{

    /**
     * Store $_POST
     *
     * @var Object
     */
    protected $post;

    /**
     * Store $_GET
     *
     * @var Object
     */
    protected $params;

    /**
     * Store $_FILES
     *
     * @var Object
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
     * @param \Jcode\Object $params
     * @param \Jcode\Object $post
     * @param \Jcode\Object $files
     */
    public function preDispatch(Object $params, Object $post, Object $files)
    {
        $this->params = $params;
        $this->post = $post;
        $this->files = $files;
    }

    /**
     * @param null $key
     *
     * @return null|Object
     */
    public function getPost($key = null)
    {
        if ($key !== null) {
            if ($this->post->getData($key)) {
                return $this->post->getData($key);
            } else {
                return null;
            }
        } else {
            return $this->post;
        }
    }

    /**
     * return $_GET object
     *
     * @return Object
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

    /**
     * Redirect user to given location
     *
     * @param $location
     */
    public function redirect($location, array $params = [])
    {
        $this->getResponse()->redirect($location, $params);
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

    public function loadLayout($block = null, $template = null)
    {
        if (!$this->layout) {
            $request = $this->getRequest();
            $module = $request->getModule();

            $element = $module->getName()
                . '::'
                . ucfirst($request->getController())
                . '\\'
                . ucfirst($request->getAction());

            $this->layout = Application::getLayout($element);
        }

        return $this->layout;
    }

    public function renderLayout()
    {
        if ($layout = $this->layout) {
            Application::register('current_layout', $layout);

            if ($root = $layout->getRoot()) {
                foreach ($root->getItemById('child_html') as $childHtml) {
                    $childHtml->setCurrentLayout($layout);

                    echo $childHtml->render();
                }
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
}