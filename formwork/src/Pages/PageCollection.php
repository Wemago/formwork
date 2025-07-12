<?php

namespace Formwork\Pages;

use Formwork\Cms\Site;
use Formwork\Data\AbstractCollection;
use Formwork\Data\Contracts\Paginable;
use Formwork\Utils\Str;
use RuntimeException;

class PageCollection extends AbstractCollection implements Paginable
{
    protected ?string $dataType = Page::class . '|' . Site::class;

    protected bool $associative = true;

    /**
     * Pagination related to the collection
     */
    protected Pagination $pagination;

    /**
     * @param array<int|string, mixed> $data
     */
    public function __construct(
        array $data,
        protected PaginationFactory $paginationFactory,
    ) {
        parent::__construct($data);
    }

    /**
     * Return the Pagination object related to the collection
     */
    public function pagination(): Pagination
    {
        return $this->pagination;
    }

    /**
     * Paginate the collection
     *
     * @param int $length Number of items in the pagination
     */
    public function paginate(int $length, int $currentPage): self
    {
        $pagination = $this->paginationFactory->make($this, $length);
        $pagination->setCurrentPage($currentPage);

        $pageCollection = $this->slice($pagination->offset(), $pagination->length());
        $pageCollection->pagination = $pagination;
        return $pageCollection;
    }

    public function extract(string $key, mixed $default = null): array
    {
        return $this->everyItem()->get($key, $default)->toArray();
    }

    /**
     * Get all the listed pages in the collection
     */
    public function listed(): static
    {
        return $this->filterBy('listed');
    }

    /**
     * Get all the published pages in the collection
     */
    public function published(): static
    {
        return $this->filterBy('status', 'published');
    }

    /**
     * Get all the pages in the collection which allow children
     */
    public function allowingChildren(): static
    {
        return $this->filterBy('allowChildren');
    }

    /**
     * Search pages in the collection
     *
     * @param string $query Query to search for
     * @param int    $min   Minimum query length
     */
    public function search(string $query, int $min = 4): static
    {
        $query = preg_replace(['/\s+/u', '/^\s+|\s+$/u'], [' ', ''], $query)
            ?? throw new RuntimeException(sprintf('Whitespace normalization failed with error: %s', preg_last_error_msg()));

        if (strlen($query) < $min) {
            $pageCollection = clone $this;
            $pageCollection->data = [];
        }

        $keywords = explode(' ', $query);

        $keywords = array_filter($keywords, fn(string $item): bool => strlen($item) > $min);

        $queryRegex = '/\b' . preg_quote($query, '/') . '\b/iu';
        $keywordsRegex = '/(?:\b' . implode('\b|\b', $keywords) . '\b)/iu';

        $scores = [
            'title'   => 8,
            'summary' => 4,
            'content' => 3,
            'author'  => 2,
            'uri'     => 1,
        ];

        $pageCollection = clone $this;

        foreach ($pageCollection->data as $page) {
            $score = 0;
            foreach (array_keys($scores) as $key) {
                $value = Str::removeHTML((string) $page->get($key));

                $queryMatches = preg_match_all($queryRegex, $value);
                $keywordsMatches = $keywords === [] ? 0 : preg_match_all($keywordsRegex, $value);

                $score += ($queryMatches * 2 + min($keywordsMatches, 3)) * $scores[$key];
            }

            if ($score > 0) {
                $page->set('score', $score);
            }
        }

        return $pageCollection->filterBy('score')->sortBy('score', direction: SORT_DESC);
    }

    /**
     * Get all the pages in the collection without the children of the specified one
     */
    public function withoutChildren(Page $page): static
    {
        return $this->difference($page->children());
    }

    /**
     * Get all the pages in the collection without the specified one and its children
     */
    public function withoutPageAndChildren(Page $page): static
    {
        return $this->without($page)->difference($page->children());
    }

    /**
     * Get all the pages in the collection without the descendants of the specified one
     */
    public function withoutDescendants(Page $page): static
    {
        return $this->difference($page->descendants());
    }

    /**
     * Get all the pages in the collection without the specified one and its descendants
     */
    public function withoutPageAndDescendants(Page $page): static
    {
        return $this->without($page)->difference($page->descendants());
    }

    /**
     * Get all the pages in the collection without the parent of the specified one
     */
    public function withoutParent(Page $page): static
    {
        return $this->without($page->parent());
    }

    /**
     * Get all the pages in the collection without the specified one and its parent
     */
    public function withoutPageAndParent(Page $page): static
    {
        return $this->without($page)->without($page->parent());
    }

    /**
     * Get all the pages in the collection without the ancestors of the specified one
     */
    public function withoutAncestors(Page $page): static
    {
        return $this->difference($page->ancestors());
    }

    /**
     * Get all the pages in the collection without the specified one and its ancestors
     */
    public function withoutPageAndAncestors(Page $page): static
    {
        return $this->without($page)->difference($page->ancestors());
    }

    /**
     * Get all the pages in the collection without the siblings of the specified one
     */
    public function withoutSiblings(Page $page): static
    {
        return $this->difference($page->siblings());
    }

    /**
     * Get all the pages in the collection without the specified one and its siblings
     */
    public function withoutPageAndSiblings(Page $page): static
    {
        return $this->without($page)->difference($page->siblings());
    }
}
