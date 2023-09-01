<?php

namespace Formwork\Pages\Traits;

use Formwork\Pages\Page;
use Formwork\Pages\PageCollection;
use Formwork\Pages\Site;
use Formwork\Utils\FileSystem;

trait PageTraversal
{
    /**
     * Parent page
     */
    protected Page|Site|null $parent;

    /**
     * Collection of page children
     */
    protected PageCollection $children;

    /**
     * Collection of page descendants
     */
    protected PageCollection $descendants;

    /**
     * Collection of page ancestors
     */
    protected PageCollection $ancestors;

    /**
     * Collection of page siblings
     */
    protected PageCollection $siblings;

    /**
     * Collection of page siblings containing the page itself
     */
    protected PageCollection $inclusiveSiblings;

    /**
     * Get page or site path
     */
    abstract public function path(): ?string;

    /**
     * Get page or site route
     */
    abstract public function route(): ?string;

    /**
     * Return whether the page is site
     */
    abstract public function isSite(): bool;

    abstract public function site(): Site;

    /**
     * Get parent page or site
     */
    public function parent(): Page|Site|null
    {
        if (isset($this->parent)) {
            return $this->parent;
        }

        $parentPath = FileSystem::joinPaths(dirname($this->path()), '/');

        if ($parentPath === $this->site()->path()) {
            return $this->parent = $this->site();
        }

        return $this->parent = $this->site()->retrievePage($parentPath);
    }

    /**
     * Return whether the page has a parent
     */
    public function hasParent(): bool
    {
        return $this->parent() !== null;
    }

    /**
     * Return whether the page is parent of the specified one
     */
    public function isParentOf(Page|Site $page): bool
    {
        return $page->parent() === $this;
    }

    /**
     * Return children pages
     */
    public function children(): PageCollection
    {
        if (isset($this->children)) {
            return $this->children;
        }

        if ($this->path() === null) {
            return $this->children = new PageCollection();
        }

        return $this->children = $this->site()->retrievePages($this->path());
    }

    /**
     * Return whether the page has children
     */
    public function hasChildren(): bool
    {
        return !$this->children()->isEmpty();
    }

    /**
     * Return whether the page is child of the specified one
     */
    public function isChildOf(Page|Site $page): bool
    {
        return $page->children()->contains($this);
    }

    /**
     * Return descendant pages
     */
    public function descendants(): PageCollection
    {
        if (isset($this->descendants)) {
            return $this->descendants;
        }

        if ($this->path() === null) {
            return $this->descendants = new PageCollection();
        }

        return $this->descendants = $this->site()->retrievePages($this->path(), recursive: true);
    }

    /**
     * Return whether the page has descendants
     */
    public function hasDescendants(): bool
    {
        return !$this->descendants()->isEmpty();
    }

    /**
     * Return whether the page is descendant of the specified one
     */
    public function isDescendantOf(Page|Site $page): bool
    {
        return $page->descendants()->contains($this);
    }

    /**
     * Return ancestor pages
     */
    public function ancestors(): PageCollection
    {
        if (isset($this->ancestors)) {
            return $this->ancestors;
        }

        $ancestors = [];

        $page = $this;

        while (($parent = $page->parent()) !== null) {
            $ancestors[$parent->route()] = $parent;
            $page = $parent;
        }

        return $this->ancestors = new PageCollection($ancestors);
    }

    /**
     * Return whether the page has ancestors
     */
    public function hasAncestors(): bool
    {
        return !$this->ancestors()->isEmpty();
    }

    /**
     * Return whether the page is ancestor of the specified one
     */
    public function isAncestorOf(Page|Site $page): bool
    {
        return $page->ancestors()->contains($this);
    }

    /**
     * Return sibling pages
     */
    public function siblings(): PageCollection
    {
        if (isset($this->siblings)) {
            return $this->siblings;
        }

        return $this->siblings = $this->inclusiveSiblings()->without($this);
    }

    /**
     * Return a collection containing the page and its siblings
     */
    public function inclusiveSiblings(): PageCollection
    {
        if (isset($this->inclusiveSiblings)) {
            return $this->inclusiveSiblings;
        }

        if ($this->path() === null) {
            return $this->inclusiveSiblings = new PageCollection([$this->route() => $this]);
        }

        return $this->inclusiveSiblings = $this->parent()->children();
    }

    /**
     * Return whether the page has siblings
     */
    public function hasSiblings(): bool
    {
        return !$this->siblings()->isEmpty();
    }

    /**
     * Return whether the page is sibling of the specified one
     */
    public function isSiblingOf(Page|Site $page): bool
    {
        return !$page->siblings()->contains($this);
    }

    /**
     * Return the previous sibling of the page
     */
    public function previousSibling(): ?Page
    {
        return $this->inclusiveSiblings()->nth($this->index() - 1);
    }

    /**
     * Return the next sibling of the page
     */
    public function nextSibling(): ?Page
    {
        return $this->inclusiveSiblings()->nth($this->index() + 1);
    }

    /**
     * Return the page index between its siblings
     */
    public function index(): int
    {
        return $this->inclusiveSiblings()->indexOf($this);
    }

    /**
     * Return the page level in the hierarchy
     */
    public function level(): int
    {
        return $this->ancestors()->count();
    }
}
