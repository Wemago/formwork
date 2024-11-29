<?php

namespace Formwork\Pages;

use Formwork\Cms\App;
use Formwork\Cms\Site;
use Formwork\Data\Exceptions\InvalidValueException;
use Formwork\Files\File;
use Formwork\Files\FileCollection;
use Formwork\Files\FileFactory;
use Formwork\Http\ResponseStatus;
use Formwork\Languages\Language;
use Formwork\Languages\Languages;
use Formwork\Metadata\MetadataCollection;
use Formwork\Model\Attributes\ReadonlyModelProperty;
use Formwork\Model\Model;
use Formwork\Pages\Traits\PageStatus;
use Formwork\Pages\Traits\PageTraversal;
use Formwork\Pages\Traits\PageUid;
use Formwork\Pages\Traits\PageUri;
use Formwork\Parsers\Yaml;
use Formwork\Templates\Template;
use Formwork\Utils\Arr;
use Formwork\Utils\Date;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Path;
use Formwork\Utils\Str;
use Formwork\Utils\Uri;
use ReflectionClass;
use RuntimeException;
use Stringable;
use UnexpectedValueException;

class Page extends Model implements Stringable
{
    use PageStatus;
    use PageTraversal;
    use PageUid;
    use PageUri;

    /**
     * Page num regex
     */
    public const string NUM_REGEX = '/^(\d+)-/';

    /**
     * Page `published` status
     */
    public const string PAGE_STATUS_PUBLISHED = 'published';

    /**
     * Page `not published` status
     */
    public const string PAGE_STATUS_NOT_PUBLISHED = 'notPublished';

    protected const string MODEL_IDENTIFIER = 'page';

    /**
     * Ignored field names on frontmatter generation
     *
     * @var list<string>
     */
    protected const array IGNORED_FIELD_NAMES = ['content', 'slug', 'template', 'parent'];

    /**
     * Ignored field types on frontmatter generation
     *
     * @var list<string>
     */
    protected const array IGNORED_FIELD_TYPES = ['upload'];

    /**
     * Slug regex
     */
    protected const string SLUG_REGEX = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/i';

    /**
     * Datetime format used for page numbering with `date` mode
     */
    protected const string DATE_NUM_FORMAT = 'Ymd';

    /**
     * Page path
     */
    protected ?string $path = null;

    /**
     * Page path relative to the content path
     */
    #[ReadonlyModelProperty]
    protected ?string $relativePath = null;

    /**
     * Page content file
     */
    #[ReadonlyModelProperty]
    protected ?ContentFile $contentFile = null;

    /**
     * Page last modified time
     */
    #[ReadonlyModelProperty]
    protected int $lastModifiedTime;

    /**
     * Page route
     */
    #[ReadonlyModelProperty]
    protected ?string $route = null;

    /**
     * Page canonical route
     */
    protected ?string $canonicalRoute = null;

    /**
     * Page slug
     */
    protected ?string $slug = null;

    /**
     * Page num used to order pages
     */
    protected ?int $num = null;

    /**
     * Available page languages
     */
    #[ReadonlyModelProperty]
    protected Languages $languages;

    /**
     * Current page language
     */
    protected ?Language $language = null;

    /**
     * Page template
     */
    protected Template $template;

    /**
     * Page metadata
     */
    #[ReadonlyModelProperty]
    protected MetadataCollection $metadata;

    /**
     * Page files
     */
    #[ReadonlyModelProperty]
    protected FileCollection $files;

    /**
     * Page HTTP response status
     */
    protected ResponseStatus $responseStatus;

    /**
     * Page loading state
     */
    #[ReadonlyModelProperty]
    protected bool $loaded = false;

    /**
     * Reference to the site
     */
    protected Site $site;

    /**
     * Page icon
     */
    protected string $icon;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        array $data,
        protected App $app,
        protected PageCollectionFactory $pageCollectionFactory,
    ) {
        $this->setMultiple($data);

        $this->loadFiles();

        if ($this->contentFile instanceof ContentFile && !$this->contentFile->isEmpty()) {
            $this->data = [
                ...$this->data,
                ...$this->contentFile->frontmatter(),
                'content' => $this->contentFile->content(),
            ];
        }

        $this->fields->setValues([...$this->data, 'slug' => $this->slug, 'parent' => $this->parent()?->route(), 'template' => $this->template]);

        $this->loaded = true;
    }

    public function __toString(): string
    {
        return (string) ($this->title() ?? $this->slug());
    }

    /**
     * Return site
     */
    public function site(): Site
    {
        return $this->site;
    }

    /**
     * Return page default data
     *
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        $defaults = [
            'published'      => true,
            'publishDate'    => null,
            'unpublishDate'  => null,
            'routable'       => true,
            'listed'         => true,
            'searchable'     => true,
            'cacheable'      => true,
            'orderable'      => true,
            'allowChildren'  => true,
            'canonicalRoute' => null,
            'headers'        => [],
            'responseStatus' => 200,
            'metadata'       => [],
            'content'        => '',
        ];

        // Merge with scheme default field values
        $defaults = [...$defaults, ...Arr::reject($this->fields()->pluck('default'), fn ($value) => $value === null)];

        // If the page doesn't have a route, by default it won't be routable nor cacheable
        if ($this->route() === null) {
            $defaults['routable'] = false;
            $defaults['cacheable'] = false;
        }

        // If the page doesn't have a num, by default it won't be listed
        if ($this->num() === null) {
            $defaults['listed'] = false;
        }

        // If the page doesn't have a num or numbering is `date`, by default it won't be orderable
        if ($this->num() === null || $this->scheme->options()->get('num') === 'date') {
            $defaults['orderable'] = false;
        }

        // If the page scheme disables children, by default it won't allow children
        if ($this->scheme()->options()->get('children') === false) {
            $defaults['allowChildren'] = false;
        }

        return $defaults;
    }

    /**
     * Get page path
     */
    public function path(): ?string
    {
        return $this->path;
    }

    /**
     * Get page relative path
     */
    public function relativePath(): ?string
    {
        return $this->relativePath;
    }

    /**
     * Get page filename
     */
    public function contentFile(): ?ContentFile
    {
        return $this->contentFile;
    }

    /**
     * Get page last modified time
     */
    public function lastModifiedTime(): ?int
    {
        if ($this->path === null) {
            return null;
        }

        $lastModifiedTime = $this->contentFile() !== null
            ? $this->contentFile()->lastModifiedTime()
            : FileSystem::lastModifiedTime($this->path);

        return $this->lastModifiedTime ??= $lastModifiedTime;
    }

    /**
     * Get page route
     */
    public function route(): ?string
    {
        return $this->route;
    }

    /**
     * Get the canonical page URI, or `null` if not available
     */
    public function canonicalRoute(): ?string
    {
        return $this->canonicalRoute ?? ($this->canonicalRoute = empty($this->data['canonicalRoute'])
            ? null
            : Path::normalize($this->data['canonicalRoute']));
    }

    /**
     * Get page slug
     */
    public function slug(): ?string
    {
        return $this->slug;
    }

    /**
     * Get page num
     */
    public function num(): ?int
    {
        if ($this->num !== null) {
            return $this->num;
        }

        preg_match(self::NUM_REGEX, basename($this->relativePath() ?? ''), $matches);
        return $this->num = isset($matches[1]) ? (int) $matches[1] : null;
    }

    /**
     * Get page languages
     */
    public function languages(): Languages
    {
        return $this->languages;
    }

    /**
     * Get page language
     */
    public function language(): ?Language
    {
        return $this->language;
    }

    /**
     * Get page template
     */
    public function template(): Template
    {
        return $this->template;
    }

    /**
     * Get page metadata
     */
    public function metadata(): MetadataCollection
    {
        if (isset($this->metadata)) {
            return $this->metadata;
        }

        $metadata = $this->site()->metadata()->clone();
        $metadata->setMultiple($this->data['metadata']);
        return $this->metadata = $metadata;
    }

    /**
     * Get page files
     */
    public function files(): FileCollection
    {
        return $this->files;
    }

    /**
     * Get page HTTP response status
     */
    public function responseStatus(): ResponseStatus
    {
        if (isset($this->responseStatus)) {
            return $this->responseStatus;
        }

        // Normalize response status
        $this->responseStatus = ResponseStatus::fromCode((int) $this->data['responseStatus']);

        // Get a default 404 Not Found status for the error page
        if (
            $this->isErrorPage() && $this->responseStatus() === ResponseStatus::OK
            && $this->contentFile === null
        ) {
            $this->responseStatus = ResponseStatus::NotFound;
        }

        return $this->responseStatus;
    }

    /**
     * Set page language
     */
    public function setLanguage(Language|string|null $language): void
    {
        if ($language === null) {
            $this->language = null;
        }

        if (is_string($language)) {
            $language = new Language($language);
        }

        if (!$this->hasLoaded()) {
            $this->language = $language;
            return;
        }

        if ($this->languages()->current()?->code() !== ($code = $language?->code())) {
            if ($code !== null && !$this->languages()->available()->has($code)) {
                throw new InvalidValueException(sprintf('Invalid page language "%s"', $code), 'invalidLanguage');
            }
            $this->reload(['language' => $language]);
        }
    }

    /**
     * Set page parent
     */
    public function setParent(Page|Site|string $parent): void
    {
        if ($parent instanceof Page || $parent instanceof Site) {
            $this->parent = $parent;
        } elseif ($parent === '.') {
            $this->parent = $this->site;
        } else {
            $this->parent = $this->site->findPage($parent) ?? throw new InvalidValueException('Invalid parent', 'invalidParent');
        }
    }

    /**
     * Set page template
     */
    public function setTemplate(Template|string $template): void
    {
        if ($template instanceof Template) {
            $this->template = $template;
        } else {
            if (!$this->site->templates()->has($template)) {
                throw new InvalidValueException('Invalid page template', 'invalidTemplate');
            }
            $this->template = $this->site->templates()->get($template);
        }
    }

    /**
     * Set page slug
     */
    public function setSlug(string $slug): void
    {
        if (!$this->validateSlug($slug)) {
            throw new InvalidValueException('Invalid page slug', 'invalidSlug');
        }
        if ($slug === $this->slug) {
            return;
        }
        if ($this->isIndexPage() || $this->isErrorPage()) {
            throw new InvalidValueException('Cannot change slug of index or error pages', 'indexOrErrorPageSlug');
        }
        if ($this->site->findPage($this->parent()?->route() . $slug . '/') !== null) {
            throw new InvalidValueException('A page with the same route already exists', 'alreadyExists');
        }
        $this->slug = $slug;
    }

    /**
     * Set page num
     *
     * If no arguments are passed, the num is set based on the current mode
     */
    public function setNum(?int $num = null): void
    {
        if (func_num_args() === 0) {
            $mode = $this->scheme()->options()->get('num');

            $num = $this->num();

            if ($mode === 'date' && $num !== null) {
                $formats = [
                    $this->app->config()->get('system.date.dateFormat'),
                    $this->app->config()->get('system.date.datetimeFormat'),
                ];

                $timestamp = isset($this->data['publishDate'])
                    ? Date::toTimestamp($this->data['publishDate'], $formats)
                    : $this->contentFile()?->lastModifiedTime();

                if ($num === (int) date(self::DATE_NUM_FORMAT, $timestamp)) {
                    return;
                }
            }

            if (!$this->parent()) {
                throw new UnexpectedValueException('Unexpected missing parent');
            }

            $num = match ($mode) {
                'date'  => date(self::DATE_NUM_FORMAT),
                default => 1 + max([0, ...$this->parent()->children()->everyItem()->num()->values()])
            };
        }

        $this->num = (int) $num;
    }

    /**
     * Return all page images
     */
    public function images(): FileCollection
    {
        return $this->files()->filterBy('type', 'image');
    }

    /**
     * Return all page videos
     */
    public function videos(): FileCollection
    {
        return $this->files()->filterBy('type', 'video');
    }

    /**
     * Return all page media files (images and videos)
     */
    public function media(): FileCollection
    {
        return $this->files()->filterBy('type', fn (string $type) => in_array($type, ['image', 'video'], true));
    }

    /**
     * Render page to string
     */
    public function render(): string
    {
        return $this->template()->render(['page' => $this]);
    }

    /**
     * Return whether the page has a content file
     */
    public function hasContentFile(): bool
    {
        return $this->contentFile !== null;
    }

    /**
     * Return whether the page content data is empty
     */
    public function isEmpty(): bool
    {
        return $this->contentFile?->frontmatter() !== [];
    }

    /**
     * Return whether the page is published
     */
    public function isPublished(): bool
    {
        return $this->status() === self::PAGE_STATUS_PUBLISHED;
    }

    /**
     * Return whether this is the currently active page
     */
    public function isCurrent(): bool
    {
        return $this->site()->currentPage() === $this;
    }

    /**
     * Return whether the page is site
     */
    public function isSite(): bool
    {
        return false;
    }

    /**
     * Return whether the page is the index page
     */
    public function isIndexPage(): bool
    {
        return $this === $this->site()->indexPage();
    }

    /**
     * Return whether the page is the error page
     */
    public function isErrorPage(): bool
    {
        return $this === $this->site()->errorPage();
    }

    /**
     * Return whether the page is deletable
     */
    public function isDeletable(): bool
    {
        return !($this->hasChildren() || $this->isIndexPage() || $this->isErrorPage());
    }

    /**
     * Return whether the slug is editable
     */
    public function isSlugEditable(): bool
    {
        return !$this->isIndexPage() && !$this->isErrorPage();
    }

    /**
     * Return whether the slug is readonly
     */
    public function isSlugReadonly(): bool
    {
        return !$this->isSlugEditable();
    }

    /**
     * Return whether the page has loaded
     */
    public function hasLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * Reload page
     *
     * @param array<string, mixed> $data
     *
     * @internal
     */
    public function reload(array $data = []): void
    {
        if (!$this->hasLoaded()) {
            throw new RuntimeException('Unable to reload, the page has not been loaded yet');
        }

        $app = $this->app;
        $pageCollectionFactory = $this->pageCollectionFactory;

        $path = $this->path;
        $site = $this->site;

        $data = [...compact('site', 'path'), ...$data];

        $this->resetProperties();

        $this->__construct($data, $app, $pageCollectionFactory);
    }

    /**
     * Return page content path
     */
    public function contentPath(): ?string
    {
        return $this->path;
    }

    /**
     * Return page content relative path
     */
    public function contentRelativePath(): ?string
    {
        return $this->relativePath;
    }

    /**
     * Return page icon
     */
    public function icon(): string
    {
        return $this->icon ??= $this->data['icon'] ?? $this->scheme()->options()->get('icon', 'page');
    }

    /**
     * Save page contents and move files if needed
     *
     * @param string|null $language Language code to save the page in
     */
    public function save(?string $language = null): void
    {
        if ($this->parent() === null) {
            throw new UnexpectedValueException('Unexpected missing parent');
        }

        if ($this->parent()->contentPath() === null) {
            throw new UnexpectedValueException('Unexpected missing parent content path');
        }

        $config = $this->app->config();

        $language ??= $this->language();

        if ($language !== null && !in_array($language, $this->site->languages()->available()->keys(), true)) {
            throw new InvalidValueException('Invalid page language', 'invalidLanguage');
        }

        $frontmatter = $this->contentFile()?->frontmatter() ?? [];

        $defaults = $this->defaults();

        $fieldCollection = $this->fields
            ->setValues([...$this->data, 'slug' => $this->slug, 'parent' => $this->parent()->route(), 'template' => $this->template])
            ->validate();

        foreach ($fieldCollection as $field) {
            if (
                $field->isEmpty()
                || (Arr::has($defaults, $field->name()) && Arr::get($defaults, $field->name()) === $field->value())
                || in_array($field->name(), self::IGNORED_FIELD_NAMES, true)
                || in_array($field->type(), self::IGNORED_FIELD_TYPES, true)
            ) {
                unset($frontmatter[$field->name()]);
                continue;
            }

            $frontmatter[$field->name()] = $field->value();
        }

        $content = str_replace("\r\n", "\n", $this->data['content']);

        $contentTemplate = $this->contentFile() !== null
            ? Str::before(basename($this->contentFile()->path()), '.')
            : $this->template()->name();

        if (!$this->contentPath() && $this->num === null) {
            $this->setNum();
        }

        $contentDir = $this->num()
            ? $this->num() . '-' . $this->slug()
            : $this->slug();

        $contentPath = FileSystem::joinPaths(
            (string) $this->parent()?->contentPath(),
            $contentDir . '/'
        );

        $differ = $contentPath !== $this->contentPath()
            || $contentTemplate !== $this->template->name()
            || $frontmatter !== $this->contentFile()?->frontmatter()
            || $content !== $this->contentFile()->content();

        if ($differ) {
            $filename = $this->template->name();

            if ($language !== null) {
                $filename .= '.' . $language;
            }

            $filename .= $config->get('system.pages.content.extension');

            $fileContent = Str::wrap(Yaml::encode($frontmatter), '---' . PHP_EOL) . $content;

            if ($contentPath !== $this->contentPath()) {
                if (!FileSystem::isDirectory($contentPath, assertExists: false)) {
                    FileSystem::createDirectory($contentPath, recursive: true);
                }
                if ($this->contentPath() !== null) {
                    FileSystem::moveDirectory($this->contentPath(), $contentPath, overwrite: FileSystem::isEmptyDirectory($contentPath, assertExists: false));
                }
            } elseif ($contentTemplate !== $this->template->name() && $this->contentFile() !== null) {
                FileSystem::delete($this->contentFile()->path());
            }

            FileSystem::write($contentPath . $filename, $fileContent);

            $this->reload(['path' => $contentPath]);

            if ($this->site->contentPath() !== null) {
                FileSystem::touch($this->site->contentPath());
            }
        }
    }

    /**
     * Load files related to page
     */
    protected function loadFiles(): void
    {
        /**
         * @var array<string, array{path: string, filename: string, template: string}>
         */
        $contentFiles = [];

        /**
         * @var list<File>
         */
        $files = [];

        /**
         * @var list<string>
         */
        $languages = [];

        $config = $this->app->config();

        $site = $this->site;

        if ($this->path !== null && FileSystem::isDirectory($this->path, assertExists: false)) {
            foreach (FileSystem::listFiles($this->path) as $file) {
                $name = FileSystem::name($file);

                $extension = '.' . FileSystem::extension($file);

                if ($extension === $config->get('system.pages.content.extension')) {
                    $language = null;

                    if (preg_match('/([a-z0-9]+)\.([a-z]+)/', $name, $matches)) {
                        // Parse double extension
                        [, $name, $language] = $matches;
                    }

                    if ($site->templates()->has($name)) {
                        $contentFiles[$language] = [
                            'path'     => FileSystem::joinPaths($this->path, $file),
                            'filename' => $file,
                            'template' => $name,
                        ];
                        if ($language !== null && !in_array($language, $languages, true)) {
                            $languages[] = $language;
                        }
                    }
                } else {
                    if (Str::endsWith($file, $config->get('system.files.metadataExtension'))) {
                        continue;
                    }
                    if (in_array($extension, $config->get('system.files.allowedExtensions'), true)) {
                        $files[] = $this->app->getService(FileFactory::class)->make(FileSystem::joinPaths($this->path, $file));
                    }
                }
            }
        }

        if (!empty($contentFiles)) {
            // Get correct content file based on current language
            ksort($contentFiles);

            // Language may already be set
            $currentLanguage = $this->language ?? $site->languages()->current();

            /**
             * @var string
             */
            $key = isset($currentLanguage, $contentFiles[$currentLanguage->code()])
                ? $currentLanguage->code()
                : array_keys($contentFiles)[0];

            // Set actual language
            $this->language ??= $key !== '' ? new Language($key) : null;

            $this->contentFile ??= new ContentFile($contentFiles[$key]['path']);

            $this->template ??= $site->templates()->get($contentFiles[$key]['template']);

            $this->scheme ??= $site->schemes()->get('pages.' . $this->template);
        } else {
            $this->template ??= $site->templates()->get('default');

            $this->scheme ??= $site->schemes()->get('pages.default');
        }

        $this->fields ??= $this->scheme()->fields();
        $this->fields->setModel($this);

        $defaultLanguage = in_array((string) $site->languages()->default(), $languages, true)
            ? $site->languages()->default()
            : null;

        $this->languages ??= new Languages([
            'available' => $languages,
            'default'   => $defaultLanguage,
            'current'   => $this->language ?? null,
            'requested' => $site->languages()->requested(),
            'preferred' => $site->languages()->preferred(),
        ]);

        $this->files ??= (new FileCollection($files))->sort();

        $this->data = [...$this->defaults(), ...$this->data];
    }

    /**
     * Set page path
     */
    protected function setPath(string $path): void
    {
        $this->path = FileSystem::normalizePath($path . '/');

        if ($this->site()->contentPath() === null) {
            throw new UnexpectedValueException('Unexpected missing site path');
        }

        $this->relativePath = Str::prepend(Path::makeRelative($this->path, $this->site()->contentPath(), DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);

        $routePath = preg_replace('~[/\\\](\d+-)~', '/', $this->relativePath)
            ?? throw new RuntimeException(sprintf('Replacement failed with error: %s', preg_last_error_msg()));

        $this->route ??= Uri::normalize($routePath);

        $this->slug ??= basename($this->route);
    }

    /**
     * Reset page properties
     */
    protected function resetProperties(): void
    {
        $reflectionClass = new ReflectionClass($this);

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            unset($this->{$reflectionProperty->getName()});

            if ($reflectionProperty->hasDefaultValue()) {
                $this->{$reflectionProperty->getName()} = $reflectionProperty->getDefaultValue();
            }
        }
    }

    /**
     * Validate page slug helper
     */
    protected function validateSlug(string $slug): bool
    {
        return (bool) preg_match(self::SLUG_REGEX, $slug);
    }
}
