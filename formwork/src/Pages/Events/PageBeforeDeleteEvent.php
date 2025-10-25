<?php

namespace Formwork\Pages\Events;

use Formwork\Events\Event;
use Formwork\Pages\Page;

class PageBeforeDeleteEvent extends Event
{
    public function __construct(Page $page)
    {
        parent::__construct('pageBeforeDelete', ['page' => $page]);
    }

    /**
     * Get the page being deleted
     */
    public function page(): Page
    {
        return $this->data['page'];
    }
}
