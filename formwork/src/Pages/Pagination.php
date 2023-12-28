<?php

namespace Formwork\Pages;

use Formwork\Data\Pagination as BasePagination;
use Formwork\Pages\Traits\PaginationUri;

class Pagination extends BasePagination
{
    use PaginationUri;

    public function __construct(PageCollection $pageCollection, int $length)
    {
        parent::__construct($pageCollection, $length);
    }
}
