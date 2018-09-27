<?php

namespace Formwork\Admin;

use Formwork\Admin\Utils\Log;
use Formwork\Admin\Utils\Notification;
use Formwork\Admin\Utils\Registry;
use Formwork\Core\Scheme;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Header;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Uri;

trait AdminTrait
{
    protected function uri($subpath)
    {
        return HTTPRequest::root() . ltrim($subpath, '/');
    }

    protected function siteUri()
    {
        return rtrim(FileSystem::dirname(HTTPRequest::root()), '/') . '/';
    }

    protected function pageUri($page)
    {
        return $this->siteUri() . ltrim($page->slug(), '/');
    }

    protected function redirect($uri, $code = 302, $exit = false)
    {
        Header::redirect($this->uri($uri), $code, $exit);
    }

    protected function redirectToSite($code = 302, $exit = false)
    {
        Header::redirect($this->siteUri(), $code, $exit);
    }

    protected function redirectToPanel($code = 302, $exit = false)
    {
        $this->redirect('/', $code, $exit);
    }

    protected function redirectToReferer($code = 302, $exit = false, $default = '/')
    {
        if (!is_null(HTTPRequest::referer()) && HTTPRequest::referer() !== Uri::current()) {
            Header::redirect(HTTPRequest::referer(), $code, $exit);
        } else {
            Header::redirect($this->uri($default), $code, $exit);
        }
    }

    protected function scheme($template)
    {
        return new Scheme($template);
    }

    protected function registry($name)
    {
        return new Registry(LOGS_PATH . $name . '.json');
    }

    protected function log($name)
    {
        return new Log(LOGS_PATH . $name . '.json');
    }

    protected function notify($text, $type)
    {
        Notification::send($text, $type);
    }

    protected function notification()
    {
        return Notification::exists() ? Notification::get() : null;
    }

    protected function label(...$arguments)
    {
        return Admin::instance()->language()->get(...$arguments);
    }
}
