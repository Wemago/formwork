<?php

use Formwork\Http\ResponseStatus;
use Formwork\Http\Utils\Header;

// Posts are the published children of the blog page
$posts = $page->children()->published();

// If the route has the param `{taxonomy}`
if ($router->params()->has('taxonomy')) {
    // Filter posts by the taxonomy term provided in the `{taxonomyTerm}` param
    $posts = $posts->havingTaxonomy(
        [$router->params()->get('taxonomy') => [$router->params()->get('taxonomyTerm')]],
        slug: true // Use slugs for matching terms
    );
}

// Get the param `{paginationPage}` from the route and cast its value to integer
$paginationPage = (int) $router->params()->get('paginationPage', 1);

// Reverse the order and paginate the posts
$posts = $posts->reverse()->paginate($page->postsPerPage(), $paginationPage);

// Permanently redirect to the URI of the first page (without the `/page/{paginationPage}/`)
// if the `paginationPage` param is given and equals `1`
if ($router->params()->has('paginationPage') && $paginationPage === 1) {
    Header::redirect($posts->pagination()->firstPageUri(), ResponseStatus::MovedPermanently);
}

// If we have no posts or the `paginationPage` params refer to an nonexistent page
// go to the error page
if ($posts->isEmpty() || !$posts->pagination()->has($paginationPage)) {
    $site->setCurrentPage($site->errorPage());
}

return [
    'posts'      => $posts,
    'pagination' => $posts->pagination()
];
