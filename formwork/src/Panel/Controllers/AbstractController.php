<?php

namespace Formwork\Panel\Controllers;

use Formwork\Cms\Site;
use Formwork\Controllers\AbstractController as BaseAbstractController;
use Formwork\Panel\Modals\Modal;
use Formwork\Panel\Panel;
use Formwork\Parsers\Json;
use Formwork\Router\Router;
use Formwork\Security\CsrfToken;
use Formwork\Services\Container;
use Formwork\Translations\Translations;
use Formwork\Utils\Date;
use Stringable;

abstract class AbstractController extends BaseAbstractController
{
    public function __construct(
        private Container $container,
        protected readonly Router $router,
        protected readonly CsrfToken $csrfToken,
        protected readonly Translations $translations,
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
            'appConfig' => Json::encode([
                'baseUri'   => $this->panel->panelUri(),
                'DateInput' => [
                    'weekStarts'     => $this->config->get('system.date.weekStarts'),
                    'dateFormat'     => Date::formatToPattern($this->config->get('system.date.dateFormat')),
                    'dateTimeFormat' => Date::formatToPattern($this->config->get('system.date.datetimeFormat')),
                    'time'           => true,
                    'labels'         => [
                        'today'      => $this->translate('date.today'),
                        'weekdays'   => ['long' => $this->translations->getCurrent()->getStrings('date.weekdays.long'), 'short' => $this->translations->getCurrent()->getStrings('date.weekdays.short')],
                        'months'     => ['long' => $this->translations->getCurrent()->getStrings('date.months.long'), 'short' => $this->translations->getCurrent()->getStrings('date.months.short')],
                        'prevMonth'  => $this->translate('fields.date.previousMonth'),
                        'nextMonth'  => $this->translate('fields.date.nextMonth'),
                        'prevHour'   => $this->translate('fields.date.previousHour'),
                        'nextHour'   => $this->translate('fields.date.nextHour'),
                        'prevMinute' => $this->translate('fields.date.previousMinute'),
                        'nextMinute' => $this->translate('fields.date.nextMinute'),
                    ],
                ],
                'DurationInput' => [
                    'labels' => [
                        'years'   => $this->translations->getCurrent()->getStrings('date.duration.years'),
                        'months'  => $this->translations->getCurrent()->getStrings('date.duration.months'),
                        'weeks'   => $this->translations->getCurrent()->getStrings('date.duration.weeks'),
                        'days'    => $this->translations->getCurrent()->getStrings('date.duration.days'),
                        'hours'   => $this->translations->getCurrent()->getStrings('date.duration.hours'),
                        'minutes' => $this->translations->getCurrent()->getStrings('date.duration.minutes'),
                        'seconds' => $this->translations->getCurrent()->getStrings('date.duration.seconds'),
                    ],
                ],
                'EditorInput' => [
                    'labels' => [
                        'bold'           => $this->translate('panel.editor.bold'),
                        'italic'         => $this->translate('panel.editor.italic'),
                        'link'           => $this->translate('panel.editor.link'),
                        'image'          => $this->translate('panel.editor.image'),
                        'quote'          => $this->translate('panel.editor.quote'),
                        'undo'           => $this->translate('panel.editor.undo'),
                        'redo'           => $this->translate('panel.editor.redo'),
                        'bulletList'     => $this->translate('panel.editor.bulletList'),
                        'numberedList'   => $this->translate('panel.editor.numberedList'),
                        'code'           => $this->translate('panel.editor.code'),
                        'heading1'       => $this->translate('panel.editor.heading1'),
                        'heading2'       => $this->translate('panel.editor.heading2'),
                        'heading3'       => $this->translate('panel.editor.heading3'),
                        'heading4'       => $this->translate('panel.editor.heading4'),
                        'heading5'       => $this->translate('panel.editor.heading5'),
                        'heading6'       => $this->translate('panel.editor.heading6'),
                        'paragraph'      => $this->translate('panel.editor.paragraph'),
                        'increaseIndent' => $this->translate('panel.editor.increaseIndent'),
                        'decreaseIndent' => $this->translate('panel.editor.decreaseIndent'),
                    ],
                ],
                'SelectInput' => [
                    'labels' => [
                        'empty' => $this->translate(('fields.select.empty')),
                    ],
                ],
                'TagsInput' => [
                    'labels' => [
                        'remove' => $this->translate('fields.tags.remove'),
                    ],
                ],
                'Backups' => [
                    'labels' => [
                        'now' => $this->translate('date.now'),
                    ],
                ],
            ]),
        ];
    }
}
