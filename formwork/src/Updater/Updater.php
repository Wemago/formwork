<?php

namespace Formwork\Updater;

use DateTimeImmutable;
use Formwork\Cms\App;
use Formwork\Http\Client;
use Formwork\Log\Registry;
use Formwork\Parsers\Json;
use Formwork\Utils\FileSystem;
use Formwork\Utils\Str;
use RuntimeException;
use ZipArchive;

final class Updater
{
    /**
     * GitHub repository from which updates are retrieved
     */
    private const string REPOSITORY = 'getformwork/formwork';

    /**
     * GitHub API latest release URI
     */
    private const string API_RELEASE_URI = 'https://api.github.com/repos/' . self::REPOSITORY . '/releases/latest';

    /**
     * Updates registry
     */
    private Registry $registry;

    /**
     * Updates registry default data
     *
     * @var array{lastCheck: ?int, lastUpdate: ?int, etag: ?string, release: ?array{name: string, tag: string, date: int, archive: string}, upToDate: bool}
     */
    private array $registryDefaults = [
        'lastCheck'  => null,
        'lastUpdate' => null,
        'etag'       => null,
        'release'    => null,
        'upToDate'   => false,
    ];

    /**
     * HTTP Client to make requests
     */
    private Client $client;

    /**
     * Array containing release information
     *
     * @var array{name: string, tag: string, date: int, archive: string}
     */
    private array $release;

    /**
     * Headers to send in HTTP(S) requests
     *
     * @var array<string, string>
     */
    private array $headers;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        private array $options,
        private App $app,
    ) {
        $this->registry = new Registry($this->options['registryFile']);

        if ($this->registry->toArray() === []) {
            $this->initializeRegistry();
        }

        $this->client = new Client(['headers' => ['Accept' => 'application/vnd.github.v3+json']]);
    }

    /**
     * Check for updates
     *
     * @return bool Whether updates are found or not
     */
    public function checkUpdates(): bool
    {
        if ($this->registry->has('lastCheck') && time() - $this->registry->get('lastCheck') < $this->options['time']) {
            return $this->registry->get('upToDate');
        }

        $this->loadRelease();

        $this->registry->set('release', $this->release);

        $this->registry->set('lastCheck', time());

        if (!$this->isVersionInstallable($this->release['tag'])) {
            $this->registry->set('upToDate', true);
            $this->registry->save();
            return true;
        }

        if (isset($this->getHeaders()['Etag'])) {
            $ETag = trim($this->headers['Etag'], '"');

            if ($this->registry->has('etag') && $this->registry->get('etag') === $ETag) {
                $this->registry->set('upToDate', true);
                $this->registry->save();
                return true;
            }
        }

        $this->registry->set('upToDate', false);
        $this->registry->save();
        return false;
    }

    /**
     * Update Formwork
     *
     * @return bool|null Whether Formwork was updated or not
     */
    public function update(): ?bool
    {
        $this->checkUpdates();

        if (!$this->options['force'] && $this->registry->get('upToDate')) {
            return null;
        }

        $this->loadRelease();

        $this->client->download($this->release['archive'], $this->options['tempFile']);

        if (!FileSystem::exists($this->options['tempFile'])) {
            throw new RuntimeException('Cannot update Formwork, archive not downloaded');
        }

        $zipArchive = new ZipArchive();
        $zipArchive->open($this->options['tempFile'], ZipArchive::RDONLY);
        $installedFiles = [];
        $counter = count($zipArchive);

        for ($i = 0; $i < $counter; $i++) {
            $filename = $zipArchive->getNameIndex($i);

            if ($filename === false) {
                throw new RuntimeException('Cannot get filename from zip archive');
            }

            $destination = FileSystem::joinPaths(ROOT_PATH, $filename);
            $destinationDirectory = dirname($destination);

            if ($this->isCopiable($filename)) {
                if (!FileSystem::exists($destinationDirectory)) {
                    FileSystem::createDirectory($destinationDirectory);
                }
                if (!Str::endsWith($destination, DIRECTORY_SEPARATOR)) {
                    $contents = $zipArchive->getFromIndex($i);
                    if ($contents === false) {
                        throw new RuntimeException(sprintf('Cannot read "%s" from zip archive', $filename));
                    }
                    FileSystem::write($destination, $contents);
                }
                $installedFiles[] = $destination;
            }
        }

        FileSystem::delete($this->options['tempFile']);

        if ($this->options['cleanupAfterInstall']) {
            $deletableFiles = $this->findDeletableFiles($installedFiles);
            foreach ($deletableFiles as $deletableFile) {
                FileSystem::delete($deletableFile);
            }
        }

        $this->registry->set('lastUpdate', time());

        if (isset($this->getHeaders()['Etag'])) {
            $ETag = trim($this->headers['Etag'], '"');
            $this->registry->set('etag', $ETag);
        }

        $this->registry->set('upToDate', true);
        $this->registry->save();

        return true;
    }

    /**
     * Get latest release data
     *
     * @return array{name: string, tag: string, date: int, archive: string}
     */
    public function latestRelease(): array
    {
        return $this->registry->get('release');
    }

    /**
     * Load latest release data
     */
    private function loadRelease(): void
    {
        if (isset($this->release)) {
            return;
        }

        $data = Json::parse($this->client->fetch(self::API_RELEASE_URI)->content());

        if ($data === []) {
            throw new RuntimeException('Cannot fetch latest Formwork release data');
        }

        $releaseDate = DateTimeImmutable::createFromFormat('Y-m-d\TH:i:sO', $data['published_at']);

        if ($releaseDate === false) {
            throw new RuntimeException('Cannot parse release date');
        }

        $this->release = [
            'name'    => $data['name'],
            'tag'     => $data['tag_name'],
            'date'    => $releaseDate->getTimestamp(),
            'archive' => $data['zipball_url'],
        ];

        if ($this->options['preferDistAssets'] && !empty($data['assets'])) {
            $assetName = 'formwork-' . $data['tag_name'] . '.zip';
            $key = array_search($assetName, array_column($data['assets'], 'name'), true);

            if ($key !== false) {
                $this->release['archive'] = $data['assets'][$key]['browser_download_url'];
            }
        }
    }

    /**
     * Get release archive headers
     *
     * @return array<string, string>
     */
    private function getHeaders(): array
    {
        return $this->headers ?? ($this->headers = $this->client->fetchHeaders($this->release['archive'])->toArray());
    }

    /**
     * Return whether a version is installable based on the current version of Formwork
     */
    private function isVersionInstallable(string $version): bool
    {
        $semVer = SemVer::fromString($this->app::VERSION);
        $new = SemVer::fromString($version);
        return !$new->isPrerelease() && $semVer->compareWith($new, '!=') && $semVer->compareWith($new, '^');
    }

    /**
     * Return whether a file is copiable or not
     */
    private function isCopiable(string $file): bool
    {
        foreach ($this->options['ignore'] as $pattern) {
            if (fnmatch($pattern, $file)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Return deletable files based on installed ones
     *
     * @param list<string> $installedFiles
     *
     * @return array<string>
     */
    private function findDeletableFiles(array $installedFiles): array
    {
        $list = [];
        foreach ($installedFiles as $installedFile) {
            $list[] = $installedFile;
            if (FileSystem::isDirectory($installedFile, assertExists: false)) {
                foreach (FileSystem::listContents($installedFile, FileSystem::LIST_ALL) as $item) {
                    $item = FileSystem::joinPaths($installedFile, $item);
                    if (FileSystem::isDirectory($item) && !FileSystem::isEmptyDirectory($item)) {
                        continue;
                    }
                    $list[] = $item;
                }
            }
        }
        return array_diff($list, $installedFiles);
    }

    /**
     * Initialize registry data
     */
    private function initializeRegistry(): void
    {
        foreach ($this->registryDefaults as $key => $value) {
            $this->registry->set($key, $value);
        }
        $this->registry->save();
    }
}
