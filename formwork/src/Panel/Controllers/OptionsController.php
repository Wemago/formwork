<?php

namespace Formwork\Panel\Controllers;

use Formwork\Config\Config;
use Formwork\Fields\FieldCollection;
use Formwork\Http\RequestMethod;
use Formwork\Http\Response;
use Formwork\Parsers\Yaml;
use Formwork\Schemes\Schemes;
use Formwork\Utils\Arr;
use Formwork\Utils\FileSystem;
use UnexpectedValueException;

final class OptionsController extends AbstractController
{
    /**
     * All options tabs
     *
     * @var list<string>
     */
    private array $tabs = ['site', 'system'];

    /**
     * Options@index action
     */
    public function index(): Response
    {
        if (!$this->hasPermission('options.site')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        return $this->redirect($this->generateRoute('panel.options.site'));
    }

    /**
     * Options@systemOptions action
     */
    public function systemOptions(Schemes $schemes): Response
    {
        if (!$this->hasPermission('options.system')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $scheme = $schemes->get('config.system');
        $fields = $scheme->fields();

        if ($this->request->method() === RequestMethod::POST) {
            $options = $this->config->get('system');
            $defaults = $this->defaultConfig()->get('system');
            $fields->setValuesFromRequest($this->request, null)->validate();

            $differ = $this->updateOptions('system', $fields, $options, $defaults);

            // Touch content folder to invalidate cache
            if ($differ) {
                if ($this->site->contentPath() === null) {
                    throw new UnexpectedValueException('Unexpected missing site path');
                }
                FileSystem::touch($this->site->contentPath());
            }

            $this->panel->notify($this->translate('panel.options.updated'), 'success');
            return $this->redirect($this->generateRoute('panel.options.system'));
        }

        $fields->setValues($this->config->get('system'));

        return new Response($this->view('options.system', [
            'title' => $this->translate('panel.options.options'),
            'tabs'  => $this->view('options.tabs', [
                'tabs'    => $this->tabs,
                'current' => 'system',
            ]),
            'fields' => $fields,
        ]));
    }

    /**
     * Options@siteOptions action
     */
    public function siteOptions(Schemes $schemes): Response
    {
        if (!$this->hasPermission('options.site')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $scheme = $schemes->get('config.site');
        $fields = $scheme->fields();

        if ($this->request->method() === RequestMethod::POST) {
            $options = $this->site->data();
            $defaults = $this->defaultConfig()->get('site');
            $fields->setValuesFromRequest($this->request, null)->validate();
            $differ = $this->updateOptions('site', $fields, $options, $defaults);

            // Touch content folder to invalidate cache
            if ($differ) {
                if ($this->site->contentPath() === null) {
                    throw new UnexpectedValueException('Unexpected missing site path');
                }
                FileSystem::touch($this->site->contentPath());
            }

            $this->panel->notify($this->translate('panel.options.updated'), 'success');
            return $this->redirect($this->generateRoute('panel.options.site'));
        }

        $fields->setValues($this->site->data());

        return new Response($this->view('options.site', [
            'title' => $this->translate('panel.options.options'),
            'tabs'  => $this->view('options.tabs', [
                'tabs'    => $this->tabs,
                'current' => 'site',
            ]),
            'fields' => $fields,
        ]));
    }

    /**
     * Get default config
     */
    private function defaultConfig(): Config
    {
        $config = new Config();
        $config->loadFromPath(SYSTEM_PATH . '/config/');
        $config->resolve([
            '%ROOT_PATH%'   => ROOT_PATH,
            '%SYSTEM_PATH%' => SYSTEM_PATH,
        ]);
        return $config;
    }

    /**
     * Update options of a given type with given data
     *
     * @param 'site'|'system'      $type
     * @param array<string, mixed> $options
     * @param array<string, mixed> $defaults
     */
    private function updateOptions(string $type, FieldCollection $fieldCollection, array $options, array $defaults): bool
    {
        $old = $options;

        $options = [];

        $removedKeys = [];

        // Update options with new values
        foreach ($fieldCollection as $field) {
            // Ignore empty and default values
            if ($field->isEmpty()) {
                continue;
            }

            if ($field->type() === 'upload') {
                $files = $field->isMultiple() ? $field->value() : [$field->value()];
                foreach ($files as $file) {
                    $this->fileUploader->upload(
                        $file,
                        $field->destination(),
                        $field->filename(),
                        $field->acceptMimeTypes(),
                        $field->overwrite(),
                    );
                }
                continue;
            }

            if (Arr::has($defaults, $field->name()) && Arr::get($defaults, $field->name()) === $field->value()) {
                $removedKeys[] = $field->name();
                continue;
            }

            Arr::set($options, $field->name(), $field->value());
        }

        // Add options that are not in the defaults nor fields
        foreach (Arr::dot($old) as $key => $value) {
            if (in_array($key, $removedKeys, true)) {
                continue;
            }
            if (!Arr::has($defaults, $key) || Arr::get($defaults, $key) !== $value) {
                Arr::set($options, $key, $value);
            }
        }

        // Update config file if options differ
        if ($options !== $old) {
            Yaml::encodeToFile($options, ROOT_PATH . '/site/config/' . $type . '.yaml');
            return true;
        }

        // Return false if options do not differ
        return false;
    }
}
