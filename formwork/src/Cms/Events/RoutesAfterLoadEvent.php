<?php

namespace Formwork\Cms\Events;

use Formwork\Events\Event;
use Formwork\Router\Router;

class RoutesAfterLoadEvent extends Event
{
    public function __construct(Router $router)
    {
        parent::__construct('routesAfterLoad', ['router' => $router]);
    }

    /**
     * Get the router instance
     */
    public function router(): Router
    {
        return $this->data['router'];
    }
}
