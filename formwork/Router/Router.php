<?php

namespace Formwork\Router;

use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Uri;
use BadMethodCallException;
use LogicException;

class Router
{
    /**
     * Valid router request types
     *
     * @var array
     */
    protected $types = array('HTTP', 'XHR');

    /**
     * Valid router request methods
     *
     * @var array
     */
    protected $methods = array('GET', 'POST', 'PUT', 'PATCH', 'DELETE');

    /**
     * Array containing route regex shortcuts
     *
     * @var array
     */
    protected $shortcuts = array(
        'num' => '[0-9]+',
        'aln' => '[A-Za-z0-9-]+',
        'all' => '.+'
    );

    /**
     * Array containing loaded routes
     *
     * @var array
     */
    protected $routes;

    /**
     * The request to match routes against
     *
     * @var string
     */
    protected $request;

    /**
     * Route params
     *
     * @var RouteParams
     */
    protected $params;

    /**
     * Whether router has dispatched
     *
     * @var bool
     */
    protected $dispatched = false;

    /**
     * Create a new Router instance
     *
     * @param string $request
     */
    public function __construct($request)
    {
        $this->request = Uri::normalize($request);
        $this->params = new RouteParams(array());
    }

    /**
     * Match route against request
     *
     * @param string $route
     *
     * @return bool
     */
    public function match($route)
    {
        $compiledRoute = $this->compileRoute($route);
        if ($compiledRoute !== false && preg_match($compiledRoute['regex'], $this->request, $matches)) {
            // Remove entire matches from $matches array
            array_shift($matches);
            // Build an associative array using params as keys and matches as values
            $params = array_combine($compiledRoute['params'], $matches);
            $this->params = new RouteParams($params);
            return true;
        }
        return false;
    }

    /**
     * Add a route
     *
     * @param mixed ...$arguments
     */
    public function add(...$arguments)
    {
        $type = 'HTTP';
        $method = 'GET';
        $callback = null;
        switch (count($arguments)) {
            case 1:
                list($route) = $arguments;
                break;
            case 2:
                list($route, $callback) = $arguments;
                break;
            case 3:
                list($method, $route, $callback) = $arguments;
                break;
            case 4:
                list($type, $method, $route, $callback) = $arguments;
                break;
            default:
                throw new BadMethodCallException('Invalid arguments for ' . __METHOD__);
        }
        if (is_array($type)) {
            foreach ($type as $t) {
                $this->add($t, $method, $route, $callback);
            }
            return;
        }
        if (is_array($method)) {
            foreach ($method as $m) {
                $this->add($type, $m, $route, $callback);
            }
            return;
        }
        if (!in_array($type, $this->types, true)) {
            throw new LogicException('Invalid request type "' . $type . '"');
        }
        if (!in_array($method, $this->methods, true)) {
            throw new LogicException('Invalid HTTP method "' . $method . '"');
        }
        if (is_array($route)) {
            foreach ($route as $r) {
                $this->add($type, $method, $r, $callback);
            }
            return;
        }
        $this->routes[] = array(
            'type'     => $type,
            'method'   => $method,
            'route'    => $route,
            'callback' => $callback
        );
    }

    /**
     * Dispatch matching route
     */
    public function dispatch()
    {
        foreach ($this->routes as $route) {
            if (HTTPRequest::type() === $route['type'] && HTTPRequest::method() === $route['method'] && $this->match($route['route'])) {
                $this->dispatched = true;
                // Parse Class@method callback syntax
                if (is_string($route['callback']) && strpos($route['callback'], '@') !== false) {
                    list($class, $method) = explode('@', $route['callback']);
                    $route['callback'] = array(new $class(), $method);
                }
                if (!is_callable($route['callback'])) {
                    throw new LogicException('Invalid callback for ' . $route['route'] . ' route');
                }
                return $route['callback']($this->params);
            }
        }
    }

    /**
     * Return whether router has dispatched
     *
     * @return bool
     */
    public function hasDispatched()
    {
        return $this->dispatched;
    }

    /**
     * Get route params
     *
     * @return RouteParams
     */
    public function params()
    {
        return $this->params;
    }

    /**
     * Get the request handled by the router
     *
     * @return string
     */
    public function request()
    {
        return $this->request;
    }

    /**
     * Compile a route to a valid regex and params list
     *
     * @param string $route
     *
     * @return array
     */
    protected function compileRoute($route)
    {
        preg_match_all('/{([A-Za-z0-9_]+)(?::([^{]+))?}/', $route, $matches);
        list($tokens, $params, $patterns) = $matches;
        $regex = $route;
        foreach ($tokens as $i => $token) {
            // Make sure current pattern is not wrapped in a capture group
            $pattern = trim($patterns[$i], '()');
            if (empty($pattern)) {
                $pattern = 'all';
            }
            if (array_key_exists($pattern, $this->shortcuts)) {
                $pattern = $this->shortcuts[$pattern];
            }
            $regex = str_replace($token, '(' . $pattern . ')', $regex);
        }
        // Wrap the regex in tilde delimiters, so we don't need to escape slashes
        $regex = '~^' . trim($regex, '^$') . '$~';
        return array(
            'regex'  => $regex,
            'params' => $params
        );
    }
}
