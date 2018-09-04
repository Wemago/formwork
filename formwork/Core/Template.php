<?php

namespace Formwork\Core;

use Formwork\Utils\FileSystem;
use RuntimeException;

class Template
{
    protected $path;

    protected $extension;

    protected $name;

    protected $vars;

    protected $scheme;

    protected static $rendering = false;

    public function __construct($template)
    {
        $this->path = Formwork::instance()->site()->templatesPath();
        $this->extension = Formwork::instance()->option('templates.extension');
        $this->name = $template;
        $this->vars = $this->defaults();
    }

    public function name()
    {
        return $this->name;
    }

    public function scheme()
    {
        if (!is_null($this->scheme)) {
            return $this->scheme;
        }
        return $this->scheme = new Scheme($this->name);
    }

    public function renderPage(Page $page, $vars, $return = false)
    {
        if (static::$rendering) {
            throw new RuntimeException(__METHOD__ . ' not allowed while rendering');
        }

        $this->vars['page'] = $page;
        $this->vars = array_merge($this->vars, $vars);

        if (!is_null($this->controller())) {
            extract($this->vars);
            $this->vars = array_merge($this->vars, (array) include($this->controller()));
        }

        return $this->render($return);
    }

    protected function defaults()
    {
        return array(
            'params' => Formwork::instance()->router()->params(),
            'site'   => Formwork::instance()->site()
        );
    }

    protected function controller()
    {
        if (static::$rendering) {
            throw new RuntimeException(__METHOD__ . ' not allowed while rendering');
        }
        $controllerFile = $this->path . 'controllers' . DS . $this->name . '.php';
        if (FileSystem::exists($controllerFile)) {
            return $controllerFile;
        }
        return null;
    }

    protected function insert($filename, $vars = array())
    {
        extract(array_merge($this->vars, $vars));
        include $this->path . str_replace('_', 'partials' . DS, $filename) . $this->extension;
    }

    protected function render($return = false)
    {
        if (static::$rendering) {
            throw new RuntimeException(__METHOD__ . ' not allowed while rendering');
        }

        ob_start();

        static::$rendering = true;
        $this->insert($this->name);
        static::$rendering = false;

        if ($return) {
            return ob_get_clean();
        }

        ob_end_flush();
    }

    public function __toString()
    {
        return $this->name;
    }
}
