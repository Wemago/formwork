<?php

namespace Formwork\Panel\Controllers;

use Formwork\Cms\Site;
use Formwork\Controllers\AbstractController as BaseAbstractController;
use Formwork\Files\Services\FileUploader;
use Formwork\Panel\Modals\Modal;
use Formwork\Panel\Panel;
use Formwork\Router\Router;
use Formwork\Security\CsrfToken;
use Formwork\Services\Container;
use Formwork\Translations\Translations;
use Stringable;

abstract class AbstractController extends BaseAbstractController
{
    public function __construct(
        private Container $container,
        protected readonly Router $router,
        protected readonly CsrfToken $csrfToken,
        protected readonly Translations $translations,
        protected readonly FileUploader $fileUploader,
        protected readonly Site $site,
        protected readonly Panel $panel,
    ) {
        $this->container->call(parent::__construct(...));
    }

    /**
     * Generate a route by name
     *
     * @param array<string, mixed> $params
     */
    protected function generateRoute(string $name, array $params = []): string
    {
        return $this->router->generate($name, $params);
    }

    /**
     * Get translated string by key
     */
    protected function translate(string $key, int|float|string|Stringable ...$arguments): string
    {
        return $this->translations->getCurrent()->translate($key, ...$arguments);
    }

    /**
     * Get if current user has a permission
     */
    protected function hasPermission(string $permission): bool
    {
        return $this->panel->user()->permissions()->has($permission);
    }

    /**
     * Load a modal to be rendered later
     */
    protected function modal(string $name): Modal
    {
        $this->panel->modals()->add($name);
        return $this->panel->modals()->get($name);
    }

    /**
     * Render a view
     *
     * @param array<string, mixed> $data
     */
    protected function view(string $name, array $data = []): string
    {
        $view = $this->viewFactory->make(
            $name,
            [...$this->defaults(), ...$data],
            $this->config->get('system.views.paths.panel'),
        );
        return $view->render();
    }

    /**
     * Return default data passed to views
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'location'   => $this->name,
            'site'       => $this->site,
            'panel'      => $this->panel,
            'csrfToken'  => $this->csrfToken->get($this->panel->getCsrfTokenName()),
            'navigation' => [
                'dashboard' => [
                    'label'       => $this->translate('panel.dashboard.dashboard'),
                    'uri'         => '/dashboard/',
                    'permissions' => 'dashboard',
                    'badge'       => null,
                ],
                'pages' => [
                    'label'       => $this->translate('panel.pages.pages'),
                    'uri'         => '/pages/',
                    'permissions' => 'pages',
                    'badge'       => $this->site->descendants()->count(),
                ],
                'files' => [
                    'label'       => $this->translate('panel.files.files'),
                    'uri'         => '/files/',
                    'permissions' => 'files',
                    'badge'       => null,
                ],
                'statistics' => [
                    'label'       => $this->translate('panel.statistics.statistics'),
                    'uri'         => '/statistics/',
                    'permissions' => 'statistics',
                    'badge'       => null,
                ],
                'users' => [
                    'label'       => $this->translate('panel.users.users'),
                    'uri'         => '/users/',
                    'permissions' => 'users',
                    'badge'       => $this->site->users()->count(),
                ],
                'options' => [
                    'label'       => $this->translate('panel.options.options'),
                    'uri'         => '/options/',
                    'permissions' => 'options',
                    'badge'       => null,
                ],
                'tools' => [
                    'label'       => $this->translate('panel.tools.tools'),
                    'uri'         => '/tools/',
                    'permissions' => 'tools',
                    'badge'       => null,
                ],
                'logout' => [
                    'label'       => $this->translate('panel.login.logout'),
                    'uri'         => '/logout/',
                    'permissions' => '*',
                    'badge'       => null,
                ],
            ],
        ];
    }
}
