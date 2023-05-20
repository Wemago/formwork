<?php

namespace Formwork\Panel\Controllers;

use Formwork\Controllers\AbstractController as BaseAbstractController;
use Formwork\Formwork;
use Formwork\Pages\Site;
use Formwork\Panel\Panel;
use Formwork\Panel\Security\CSRFToken;
use Formwork\Panel\Users\User;
use Formwork\Parsers\JSON;
use Formwork\Parsers\PHP;
use Formwork\Response\RedirectResponse;
use Formwork\Utils\Date;
use Formwork\Utils\HTTPRequest;
use Formwork\Utils\Uri;
use Formwork\View\View;

abstract class AbstractController extends BaseAbstractController
{
    /**
     * All loaded modals
     */
    protected array $modals = [];

    /**
     * Return panel instance
     */
    protected function panel(): Panel
    {
        return Formwork::instance()->panel();
    }

    /**
     * Return site instance
     */
    protected function site(): Site
    {
        return Formwork::instance()->site();
    }

    /**
     * Redirect to a given route
     *
     * @param int $code HTTP redirect status code
     */
    protected function redirect(string $route, int $code = 302): RedirectResponse
    {
        return new RedirectResponse($this->panel()->uri($route), $code);
    }

    /**
     * Redirect to the site index page
     *
     * @param int $code HTTP redirect status code
     */
    protected function redirectToSite(int $code = 302): RedirectResponse
    {
        return new RedirectResponse($this->site()->uri(), $code);
    }

    /**
     * Redirect to the administration panel
     *
     * @param int $code HTTP redirect status code
     */
    protected function redirectToPanel(int $code = 302): RedirectResponse
    {
        return $this->redirect('/', $code);
    }

    /**
     * Redirect to the referer page
     *
     * @param int    $code    HTTP redirect status code
     * @param string $default Default route if HTTP referer is not available
     */
    protected function redirectToReferer(int $code = 302, string $default = '/'): RedirectResponse
    {
        if (HTTPRequest::validateReferer($this->panel()->uri('/')) && HTTPRequest::referer() !== Uri::current()) {
            return new RedirectResponse(HTTPRequest::referer(), $code);
        }
        return new RedirectResponse($this->panel()->uri($default), $code);
    }

    protected function translate(...$arguments)
    {
        return Formwork::instance()->translations()->getCurrent()->translate(...$arguments);
    }

    /*
     * Return default data passed to views
     *
     */
    protected function defaults(): array
    {
        return [
            'location'    => $this->name,
            'site'        => $this->site(),
            'panel'       => $this->panel(),
            'csrfToken'   => CSRFToken::get(),
            'modals'      => implode('', $this->modals),
            'colorScheme' => $this->getColorScheme(),
            'appConfig'   => JSON::encode([
                'baseUri'   => $this->panel()->panelUri(),
                'DateInput' => [
                    'weekStarts' => Formwork::instance()->config()->get('date.weekStarts'),
                    'format'     => Date::formatToPattern(Formwork::instance()->config()->get('date.format') . ' ' . Formwork::instance()->config()->get('date.timeFormat')),
                    'time'       => true,
                    'labels'     => [
                        'today'    => $this->translate('date.today'),
                        'weekdays' => ['long' => $this->translate('date.weekdays.long'), 'short' => $this->translate('date.weekdays.short')],
                        'months'   => ['long' => $this->translate('date.months.long'), 'short' => $this->translate('date.months.short')],
                    ],
                ],
                'DurationInput' => [
                    'labels' => [
                        'years'   => $this->translate('date.duration.years'),
                        'months'  => $this->translate('date.duration.months'),
                        'weeks'   => $this->translate('date.duration.weeks'),
                        'days'    => $this->translate('date.duration.days'),
                        'hours'   => $this->translate('date.duration.hours'),
                        'minutes' => $this->translate('date.duration.minutes'),
                        'seconds' => $this->translate('date.duration.seconds'),
                    ],
                ],
            ]),
        ];
    }

    /**
     * Get logged user
     */
    protected function user(): User
    {
        return $this->panel()->user();
    }

    /**
     * Ensure current user has a permission
     */
    protected function ensurePermission(string $permission): void
    {
        if (!$this->user()->permissions()->has($permission)) {
            $errors = new ErrorsController();
            $errors->forbidden()->send();
            exit;
        }
    }

    /**
     * Load a modal to be rendered later
     *
     * @param string $name Name of the modal
     * @param array  $data Data to pass to the modal
     */
    protected function modal(string $name, array $data = []): void
    {
        $this->modals[] = $this->view('modals.' . $name, $data, true);
    }

    /**
     * Render a view
     *
     * @param string $name   Name of the view
     * @param array  $data   Data to pass to the view
     * @param bool   $return Whether to return or render the view
     *
     * @return string|void
     */
    protected function view(string $name, array $data = [], bool $return = false)
    {
        $view = new View(
            $name,
            array_merge($this->defaults(), $data),
            Formwork::instance()->config()->get('views.paths.panel'),
            PHP::parseFile(PANEL_PATH . 'helpers.php')
        );
        return $view->render($return);
    }

    /**
     * Get color scheme
     */
    private function getColorScheme(): string
    {
        $default = Formwork::instance()->config()->get('panel.colorScheme');
        if ($this->panel()->isLoggedIn()) {
            if ($this->user()->colorScheme() === 'auto') {
                return HTTPRequest::cookies()->get('formwork_preferred_color_scheme', $default);
            }
            return $this->user()->colorScheme();
        }
        return $default;
    }
}
