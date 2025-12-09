<?php

namespace Formwork\Panel\Controllers;

use Formwork\Config\Config;
use Formwork\Fields\FieldCollection;
use Formwork\Http\RequestMethod;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
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
        if (!$this->hasPermission('panel.options.site')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        return $this->redirect($this->generateRoute('panel.options.site'));
    }

    /**
     * Options@systemOptions action
     */
    public function systemOptions(Schemes $schemes): Response
    {
        if (!$this->hasPermission('panel.options.system')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $scheme = $schemes->get('config.system');
        $fields = $scheme->fields();

        $valid = false;

        switch ($this->request->method()) {
            case RequestMethod::GET:
                $fields->setValues($this->config->get('system'));

                $valid = $fields->isValid();

                break;

            case RequestMethod::POST:
                $fields->setValuesFromRequest($this->request, null);

                if (!($valid = $fields->isValid())) {
                    $this->panel->notify($this->translate('panel.options.cannotUpdate.invalidFields'), 'error');
                    break;
                }

                $this->handleUploads($fields);

                $options = $this->getConfigOverrides()->get('system', []);
                $defaults = $this->getConfigDefaults()->get('system');

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

        $responseStatus = ($valid || $this->request->method() === RequestMethod::GET) ? ResponseStatus::OK : ResponseStatus::UnprocessableEntity;

        return new Response($this->view('@panel.options.system', [
            'title' => $this->translate('panel.options.options'),
            'tabs'  => $this->view('@panel.options.tabs', [
                'tabs'    => $this->tabs,
                'current' => 'system',
            ]),
            'fields' => $fields,
        ]), $responseStatus);
    }

    /**
     * Options@siteOptions action
     */
    public function siteOptions(Schemes $schemes): Response
    {
        if (!$this->hasPermission('panel.options.site')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $scheme = $schemes->get('config.site');
        $fields = $scheme->fields();

        $valid = false;

        switch ($this->request->method()) {
            case RequestMethod::GET:
                $fields->setValues($this->site->data());

                $valid = $fields->isValid();
                break;

            case RequestMethod::POST:
                $fields->setValuesFromRequest($this->request, null);

                if (!($valid = $fields->isValid())) {
                    $this->panel->notify($this->translate('panel.options.cannotUpdate.invalidFields'), 'error');
                    break;
                }

                $this->handleUploads($fields);

                $options = $this->getConfigOverrides()->get('site', []);
                $defaults = $this->getConfigDefaults()->get('site');
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

        $responseStatus = ($valid || $this->request->method() === RequestMethod::GET) ? ResponseStatus::OK : ResponseStatus::UnprocessableEntity;

        return new Response($this->view('@panel.options.site', [
            'title' => $this->translate('panel.options.options'),
            'tabs'  => $this->view('@panel.options.tabs', [
                'tabs'    => $this->tabs,
                'current' => 'site',
            ]),
            'fields' => $fields,
        ]), $responseStatus);
    }

    /**
     * Get config defaults
     */
    private function getConfigDefaults(): Config
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
     * Get config overrides
     */
    private function getConfigOverrides(): Config
    {
        $config = new Config(resolved: true);
        $config->loadFromPath(ROOT_PATH . '/site/config/');
        return $config;
    }

    /**
     * Handle upload fields
     */
    private function handleUploads(FieldCollection $fieldCollection): void
    {
        foreach ($fieldCollection->filterBy('type', 'upload') as $field) {
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
        }
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

        // Update options with new values
        $data = $fieldCollection
            ->filter(fn($field) => $field->type() !== 'upload')
            ->extract('value');

        $options = Arr::exclude(
            Arr::override($options, Arr::undot($data)),
            $defaults
        );

        // Update config file if options differ
        if ($options !== $old) {
            Yaml::encodeToFile($options, ROOT_PATH . '/site/config/' . $type . '.yaml');
            return true;
        }

        // Return false if options do not differ
        return false;
    }
}
