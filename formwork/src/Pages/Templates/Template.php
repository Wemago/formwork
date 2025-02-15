<?php

namespace Formwork\Pages\Templates;

use Formwork\App;
use Formwork\Assets;
use Formwork\Pages\Page;
use Formwork\Pages\Site;
use Formwork\Utils\FileSystem;
use Formwork\View\Renderer;
use Formwork\View\View;

class Template extends View
{
    /**
     * @inheritdoc
     */
    protected const TYPE = 'template';

    /**
     * Page passed to the template
     */
    protected Page $page;

    /**
     * Template assets instance
     */
    protected Assets $assets;

    /**
     * Create a new Template instance
     */
    public function __construct(string $name, protected App $app, protected Site $site)
    {
        parent::__construct($name, $this->defaults(), $this->app->config()->get('system.templates.path'), $this->defaultMethods());
    }

    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * Get Assets instance
     */
    public function assets(): Assets
    {
        if (isset($this->assets)) {
            return $this->assets;
        }
        return $this->assets = new Assets(
            $this->path() . 'assets/',
            $this->site->uri('/site/templates/assets/', includeLanguage: false)
        );
    }

    /**
     * Render template
     */
    public function render(): string
    {
        $isCurrentPage = $this->page->isCurrent();

        $this->loadController();

        // Render correct page if the controller has changed the current one
        if ($isCurrentPage && !$this->page->isCurrent()) {
            return $this->site->currentPage()->render();
        }

        return parent::render();
    }

    public function setPage(Page $page)
    {
        $this->page = $page;
        $this->vars['page'] = $page;
        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function defaults(): array
    {
        return [
            'router' => $this->app->router(),
            'site'   => $this->site,
        ];
    }

    /**
     * Default template methods
     */
    protected function defaultMethods(): array
    {
        return [
            'assets' => fn () => $this->assets(),
        ];
    }

    /**
     * Load template controller if exists
     */
    protected function loadController(): void
    {
        $controllerFile = FileSystem::joinPaths($this->path, 'controllers', $this->name . '.php');

        if (FileSystem::exists($controllerFile)) {
            $this->allowMethods = true;
            $this->vars = [...$this->vars, ...(array) Renderer::load($controllerFile, $this->vars, $this)];
            $this->allowMethods = false;
        }
    }
}
