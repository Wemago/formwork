<?php

namespace Formwork\Panel\Controllers;

use Formwork\Cms\Site;
use Formwork\Exceptions\TranslatedException;
use Formwork\Fields\FieldCollection;
use Formwork\Files\File;
use Formwork\Files\FileCollection;
use Formwork\Files\FileFactory;
use Formwork\Http\JsonResponse;
use Formwork\Http\RequestMethod;
use Formwork\Http\Response;
use Formwork\Http\ResponseStatus;
use Formwork\Images\Image;
use Formwork\Pages\Page;
use Formwork\Parsers\Yaml;
use Formwork\Router\RouteParams;
use Formwork\Utils\Arr;
use Formwork\Utils\Date;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Path;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;
use InvalidArgumentException;

final class FilesController extends AbstractController
{
    /**
     * FilesController@index action
     */
    public function index(): Response
    {
        if (!$this->hasPermission('files.index')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        if ($this->request->method() === RequestMethod::POST) {
            if (!$this->hasPermission('files.upload')) {
                return $this->forward(ErrorsController::class, 'forbidden');
            }

            $fields = $this->modal('uploadFile')->fields();

            $fields->setValuesFromRequest($this->request, null)->validate();

            $filesField = $fields->get('files');

            if (!$filesField->isEmpty()) {
                $files = $filesField->isMultiple() ? $filesField->value() : [$filesField->value()];

                /** @var Page|Site */
                $parent = $fields->get('parent')->return();

                $destination = $parent instanceof Site
                    ? $this->config->get('system.files.paths.site')
                    : $parent->contentPath();

                foreach ($files as $file) {
                    $this->fileUploader->upload(
                        $file,
                        $destination,
                        $filesField->filename(),
                        $filesField->acceptMimeTypes(),
                        $filesField->overwrite(),
                    );
                }

                $this->panel->notify($this->translate('panel.files.uploaded'), 'success');
            }

            return $this->redirect($this->generateRoute('panel.files.index'));
        }

        return new Response($this->view('files.index', [
            'title' => $this->translate('panel.files.files'),
            'files' => $this->getFiles(),
        ]));
    }

    /**
     * FilesController@edit action
     */
    public function edit(RouteParams $routeParams): Response
    {
        if (!$this->hasPermission('files.edit')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $model = $this->getModel($routeParams);

        $filename = $routeParams->get('filename');

        $files = $model?->files();

        $file = $files?->get($filename);

        if ($model === null || $files === null || $file === null) {
            $this->panel->notify($this->translate('panel.files.fileNotFound'), 'error');
            return $this->redirectToReferer(base: $this->panel->panelRoot());
        }

        switch ($this->request->method()) {
            case RequestMethod::GET:
                $data = $file->data();

                $file->fields()->setValues($data);

                break;

            case RequestMethod::POST:
                $data = $this->request->input();

                $file->fields()->setValues($data)->validate();

                $this->updateFileMetadata($file, $file->fields());

                $this->updateLastModifiedTime($model);

                $this->panel->notify($this->translate('panel.files.metadata.updated'), 'success');

                return $this->redirect($this->generateRoute('panel.files.edit', $routeParams->toArray()));
        }

        return new Response($this->view('files.edit', [
            'title' => $file->name(),
            'model' => $model,
            'file'  => $file,
            ...$this->getPreviousAndNextFile($files, $file),
        ]));
    }

    /**
     * FilesController@delete action
     */
    public function delete(RouteParams $routeParams): JsonResponse|Response
    {
        if (!$this->hasPermission('files.delete')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $model = $this->getModel($routeParams);

        $file = $model?->files()?->get($routeParams->get('filename'));

        if ($model === null || $file === null) {
            if ($this->request->isXmlHttpRequest()) {
                return JsonResponse::error($this->translate('panel.files.cannotDelete.fileNotFound'), ResponseStatus::InternalServerError);
            }
            $this->panel->notify($this->translate('panel.files.cannotDelete.fileNotFound'), 'error');
            return $this->redirectToReferer(base: $this->panel->panelRoot());
        }

        FileSystem::delete($file->path());

        $this->updateLastModifiedTime($model);

        if ($this->request->isXmlHttpRequest()) {
            return JsonResponse::success($this->translate('panel.files.deleted'));
        }
        $this->panel->notify($this->translate('panel.files.deleted'), 'success');
        return $this->redirect($this->generateRoute('panel.files.dashboard'));
    }

    /**
     * FilesController@rename action
     */
    public function rename(RouteParams $routeParams, FileFactory $fileFactory): JsonResponse|Response
    {
        if (!$this->hasPermission('files.rename')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $model = $this->getModel($routeParams);

        $fields = $this->modal($this->request->isXmlHttpRequest() ? 'renameFileItem' : 'renameFile')->fields();

        $fields->setValues($this->request->input())->validate();

        $data = $fields->everyItem()->value();

        $filename = $routeParams->get('filename');

        $file = $model?->files()->get($filename);

        if ($model === null || $file === null) {
            if ($this->request->isXmlHttpRequest()) {
                return JsonResponse::error($this->translate('panel.files.cannotRename.fileNotFound'), ResponseStatus::InternalServerError);
            }
            $this->panel->notify($this->translate('panel.files.cannotRename.fileNotFound'), 'error');
            return $this->redirect($this->generateRoute('panel.files.edit', $routeParams->toArray()));
        }

        $name = Str::slug(FileSystem::name($data->get('filename')));
        $extension = FileSystem::extension($filename);

        $newName = $name . '.' . $extension;

        if ($newName !== $filename) {
            if ($model->files()->has($newName)) {
                $this->panel->notify($this->translate('panel.files.cannotRename.fileAlreadyExists'), 'error');
            } else {
                $dirname = dirname($file->path());
                $destination = FileSystem::joinPaths($dirname, $newName);

                FileSystem::move($file->path(), $destination);

                $file = $fileFactory->make($destination);

                $this->updateLastModifiedTime($model);

                if ($this->request->isXmlHttpRequest()) {
                    return JsonResponse::success($this->translate('panel.files.renamed'), data: [
                        'filename'         => $newName,
                        'uri'              => $file->uri(),
                        'size'             => $file->size(),
                        'lastModifiedTime' => Date::formatTimestamp(
                            $file->lastModifiedTime(),
                            $this->config->get('system.date.datetimeFormat'),
                            $this->translations->getCurrent()
                        ),
                        'type'      => $file->type(),
                        'thumbnail' => $this->getThumbnailUri($file),
                        'actions'   => $this->getActionsUri($file, $model),
                    ]);
                }
                $this->panel->notify($this->translate('panel.files.renamed'), 'success');
            }
        }

        return $this->redirect($this->generateRoute('panel.files.edit', [...$routeParams->toArray(), 'filename' => $newName]));
    }

    /**
     * FilesController@replace action
     */
    public function replace(RouteParams $routeParams): JsonResponse|Response
    {
        if (!$this->hasPermission('files.replace')) {
            return $this->forward(ErrorsController::class, 'forbidden');
        }

        $model = $this->getModel($routeParams);

        $filename = $routeParams->get('filename');

        $file = $model?->files()->get($filename);

        if ($model === null || $file === null) {
            if ($this->request->isXmlHttpRequest()) {
                return JsonResponse::error($this->translate('panel.files.cannotReplace.fileNotFound'), ResponseStatus::InternalServerError);
            }
            $this->panel->notify($this->translate('panel.files.cannotReplace.fileNotFound'), 'error');
            return $this->redirectToReferer(base: $this->panel->panelRoot());
        }

        if (!$this->request->files()->isEmpty()) {
            $files = $this->request->files()->getAll();

            if (count($files) > 1) {
                $this->panel->notify($this->translate('panel.files.cannotReplace.multipleFiles'), 'error');
                return $this->redirect($this->generateRoute('panel.files.edit', $routeParams->toArray()));
            }

            try {
                $file = $this->fileUploader->upload(
                    $files[0],
                    dirname($file->path()),
                    FileSystem::name($filename),
                    [$model->files()->get($filename)->mimeType()],
                    overwrite: true,
                );
            } catch (TranslatedException $e) {
                if ($this->request->isXmlHttpRequest()) {
                    return JsonResponse::error($this->translate('upload.error', $this->translate($e->getLanguageString())), ResponseStatus::InternalServerError);
                }
                $this->panel->notify($this->translate('upload.error', $this->translate($e->getLanguageString())), 'error');
                return $this->redirect($this->generateRoute('panel.files.edit', $routeParams->toArray()));
            }
        }

        $this->updateLastModifiedTime($model);

        if ($this->request->isXmlHttpRequest()) {
            return JsonResponse::success($this->translate('panel.uploader.uploaded'), data: [
                'filename'         => $file->name(),
                'uri'              => $file->uri(),
                'size'             => $file->size(),
                'lastModifiedTime' => Date::formatTimestamp(
                    $file->lastModifiedTime(),
                    $this->config->get('system.date.datetimeFormat'),
                    $this->translations->getCurrent()
                ),
                'type'      => $file->type(),
                'thumbnail' => $this->getThumbnailUri($file),
                'actions'   => $this->getActionsUri($file, $model),
            ]);
        }
        $this->panel->notify($this->translate('panel.uploader.uploaded'), 'success');
        return $this->redirect($this->generateRoute('panel.files.edit', $routeParams->toArray()));
    }

    /**
     * @return array<array{FileCollection, Page|Site}>
     */
    private function getFiles(): array
    {
        $files = [];

        foreach ($this->site->files() as $fileCollectionItem) {
            $files[] = [$fileCollectionItem, $this->site];
        }

        foreach ($this->site->descendants() as $pageCollection) {
            foreach ($pageCollection->files() as $fileCollectionItem) {
                $files[] = [$fileCollectionItem, $pageCollection];
            }
        }

        return Arr::sort($files, sortBy: fn(array $a, array $b) => strnatcasecmp($a[0]->name(), $b[0]->name()));
    }

    private function getModel(RouteParams $routeParams): Page|Site|null
    {
        return match ($routeParams->get('model')) {
            'page'  => $this->site->findPage($routeParams->get('id')),
            'site'  => $this->site,
            default => throw new InvalidArgumentException('Invalid model'),
        };
    }

    /**
     * Update file metadata
     */
    private function updateFileMetadata(File $file, FieldCollection $fieldCollection): void
    {
        $data = $file->data();

        $scheme = $file->scheme();

        $defaults = $scheme->fields()->pluck('default');

        foreach ($fieldCollection as $field) {
            if ($field->isEmpty() || (Arr::has($defaults, $field->name()) && Arr::get($defaults, $field->name()) === $field->value())) {
                unset($data[$field->name()]);
                continue;
            }

            $data[$field->name()] = $field->value();
        }

        $metaFile = $file->path() . $this->config->get('system.files.metadataExtension');

        if ($data === [] && FileSystem::exists($metaFile)) {
            FileSystem::delete($metaFile);
            return;
        }

        FileSystem::write($metaFile, Yaml::encode($data));
    }

    /**
     * Update last modified time of the given model
     */
    private function updateLastModifiedTime(Page|Site $model): void
    {
        if ($model->contentFile()?->path() !== null) {
            FileSystem::touch($model->contentFile()->path());
        }
    }

    /**
     * Get previous and next file helper
     *
     * @return array{previousFile: ?File, nextFile: ?File}
     */
    private function getPreviousAndNextFile(FileCollection $fileCollection, File $file): array
    {
        $fileIndex = $fileCollection->indexOf($file);

        return [
            'previousFile' => $fileCollection->nth($fileIndex - 1),
            'nextFile'     => $fileCollection->nth($fileIndex + 1),
        ];
    }

    /**
     * Generate actions URIs for a file
     *
     * @return array<string, string>
     */
    private function getActionsUri(File $file, Page|Site $model): array
    {
        $params = ['model' => $model->getModelIdentifier(), 'id' => $model->route(), 'filename' => $file->name()];
        $actions = [
            'info'    => $this->router->generate('panel.files.edit', $params),
            'rename'  => $this->router->generate('panel.files.rename', $params),
            'replace' => $this->router->generate('panel.files.replace', $params),
            'delete'  => $this->router->generate('panel.files.delete', $params),
        ];
        return Arr::map($actions, fn(string $route): string => Uri::make([], Path::join([$this->request->root(), $route])));
    }

    private function getThumbnailUri(File $file): ?string
    {
        switch ($file->type()) {
            case 'image':
                /** @var Image $file */
                return $file->square(300, 'contain')->uri();
            case 'video':
                return $file->uri();
            default:
                return null;
        }
    }
}
