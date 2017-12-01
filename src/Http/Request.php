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

use \Jcode\Application;
use \Exception;
use Jcode\Router\Front\Controller;

class Request
{

    protected $isSharedInstance = true;

    protected $eventId = 'router.request';

    protected $frontName = 'core';

    protected $controller = 'index';

    protected $action = 'index';

    /**
     * @inject \Jcode\Router\Rewrite
     * @var \Jcode\Router\Rewrite
     */
    protected $rewrite;

    protected $route;

    /**
     * @var \Jcode\DataObject
     */
    protected $module;

    protected $get = [];

    /**
     * Initialize request object
     * Request URI is exploded into <module_key>/<controller_key>/<action_key>
     * If a key is not filled, it defaults to index
     *
     * @param \Jcode\Router\Http\Response $response
     */
    public function buildHttpRequest(Response $response)
    {
        $route = trim($this->getServer('REQUEST_URI'), '/');

        /**
         * If there is no route (user is at / ), check if there is a default path configured
         */
        if (empty($route)) {
            $route = Application::getConfig('default_route');
        }

        /**
         * Check if a rewrite is set for the current request. If there is, use that rewrite as route.
         */
        if ($rewrite = $this->rewrite->getRewrite($route)) {
            $route = $rewrite;
        }

        $params = null;

        /**
         * Explode any GET params from route
         */
        if (strpos($route, '?')) {
            $route = current(explode('?', $route));
        }

        $routeArray = array_values(
            array_pad(array_filter(explode('/', $route)), 3, 'index')
        );

        /**
         * Chop up the route into frontname, controller and action. Replace blanks by 'index'
         * E.G: /my/page/ would create a route of /my/page/index
         */
        list($this->frontName, $this->controller, $this->action) = $routeArray;

        if (count($routeArray) > 3) {
            unset($routeArray[0]);
            unset($routeArray[1]);
            unset($routeArray[2]);

            if (!empty($routeArray)) {
                $args = array_values($routeArray);
                $name = null;

                if (count($args) === 1) {
                    $this->get = $args;
                } else {
                    foreach ($args as $key => $value) {
                        if ($key % 2 == 0) {
                            $name = $value;
                        } else {
                            $this->get[$name] = $value;
                        }
                    }
                }
            }
        }

        $this->dispatch($response);

        return;
    }

    /**
     * Dispatch request, finding corresponting modules, controllers and actions
     *
     * @param Response $response
     */
    public function dispatch(Response $response)
    {
        if (Application::getConfig('force_ssl') && $this->getServer('REQUEST_SCHEME') == 'http') {
            $location = sprintf('https://%s%s', $this->getServer('HTTP_HOST'), $this->getServer('REQUEST_URI'));

            $response->setLocation($location);
            $response->setHttpCode(301);
            $response->dispatch();

            return;
        }

        /**
         * Load a module by frontname.
         * @var Application\Module $module
         */
        if ($module = Application::getConfig()->getModuleByFrontname($this->frontName)) {
            $this->module = $module;

            /**
             * Check if the module has any controllers defined
             */
            if ($router = $module->getRouter()) {
                if ($class = $router->getClass()) {
                    $class = rtrim($class, '\\') . '\\' . ucfirst($this->controller);

                    try {
                        $controller = Application::getClass($class, [$this, $response]);

                        if ($controller instanceof Controller) {
                            $action = $this->action . "Action";

                            if (method_exists($controller, $action)) {
                                $this->route = sprintf('%s/%s/%s', $this->frontName, $this->controller, $this->action);

                                /* @var \Jcode\DataObject $get */
                                $get = Application::getClass('Jcode\DataObject');

                                if (!empty($_GET)) {
                                    $get->importArray($_GET);
                                } else {
                                    $get->importArray($this->get);
                                }

                                /* @var \Jcode\DataObject $post */
                                $post = Application::getClass('Jcode\DataObject');

                                foreach ($_POST as $key => $value) {
                                    $method = Application\Config::convertStringToMethod($key);
                                    $post->$method($value);
                                }
                                $post->importArray($_POST);

                                /* @var \Jcode\DataObject $files */
                                $files = Application::getClass('Jcode\DataObject');
                                $files->importArray($_FILES);

                                $controller->preDispatch($get, $post, $files);

                                Application::register('current_controller', $controller);

                                $controller->$action();
                                $controller->postDispatch();
                            } else {
                                Application::log("Class is loaded, but action is not found");

                                $controller->noRoute();
                                $controller->postDispatch();
                            }
                        } else {
                            Application::log("{$class} not an instance of \\Jcode\\Router\\Front\\Controler");

                            $this->noRoute($response);
                        }
                    } catch (Exception $e) {
                        Application::logException($e);

                        $this->noRoute($response);
                    }
                } else {
                    Application::log('Module router is defined, but no controller class is set');

                    $this->noRoute($response);
                }
            } else {
                Application::log('Module is set, but no router is defined');

                $this->noRoute($response);
            }
        } else {
            $this->noRoute($response);
        }
    }

    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Get a server variable
     *
     * @param null $key
     *
     * @return mixed
     */
    public function getServer($key = null)
    {
        return ($key !== null) ? $_SERVER[$key] : $_SERVER;
    }

    /**
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @param $controller
     *
     * @return $this
     */
    public function setController($controller)
    {
        $this->controller = $controller;

        return $this;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param $action
     *
     * @return $this
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    public function getFrontName()
    {
        return $this->frontName;
    }

    public function noRoute($response)
    {
        /** @var Controller $controller */
        $controller = Application::getClass('\Jcode\Router\Front\Controller', [$this, $response]);

        $controller->noRoute();
    }

    /**
     * @param $frontName
     *
     * @return $this
     */
    public function setFrontName($frontName)
    {
        $this->frontName = $frontName;

        return $this;
    }

    /**
     * Return currently used module
     *
     * @return \Jcode\DataObject
     */
    public function getModule()
    {
        return $this->module;
    }
}