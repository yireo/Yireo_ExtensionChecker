<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use Exception;
use GuzzleHttp\ClientFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Yireo\ExtensionChecker\Composer\ComposerFileProvider;
use Yireo\ExtensionChecker\Message\MessageBucket;
use Yireo\ExtensionChecker\Message\MessageGroupLabels;
use Yireo\ExtensionChecker\Util\CacheFile;

class ScanComposerFile
{
    private const LICENSE_LIST_VERSION = 'v3.29.0';
    private const LICENSE_LIST_URL = 'https://raw.githubusercontent.com/spdx/license-list-data/refs/tags/'
        . self::LICENSE_LIST_VERSION . '/json/licenses.json';

    private ComposerFileProvider $composerFileProvider;
    private MessageBucket $messageBucket;
    private ClientFactory $clientFactory;
    private DirectoryList $directoryList;
    private CacheFile $cacheFile;
    private ?array $knownLicenses = null;
    private bool $isLicenseListLoaded = false;

    public function __construct(
        ComposerFileProvider $composerFileProvider,
        MessageBucket $messageBucket,
        ClientFactory $clientFactory,
        DirectoryList $directoryList,
        CacheFile $cacheFile
    ) {
        $this->composerFileProvider = $composerFileProvider;
        $this->messageBucket = $messageBucket;
        $this->clientFactory = $clientFactory;
        $this->directoryList = $directoryList;
        $this->cacheFile = $cacheFile;
    }

    /**
     * @param string $moduleName
     * @return void
     * @throws FileSystemException
     */
    public function scan(string $moduleName)
    {
        $composerFile = $this->composerFileProvider->getComposerFileByModuleName($moduleName);
        $licenses = $composerFile->get('license');
        if (is_string($licenses)) {
            $licenses = [$licenses];
        }

        if (false === is_array($licenses)) {
            $this->addInvalidLicenseMessage($moduleName);
            return;
        }

        $knownLicenses = $this->getKnownLicenses();
        foreach ($licenses as $license) {
            if (false === is_string($license)) {
                $this->addInvalidLicenseMessage($moduleName);
                continue;
            }

            if (null === $knownLicenses || in_array($license, $knownLicenses, true)) {
                continue;
            }

            $message = 'Composer file mentions unsupported license: '.$license;
            $suggestion = 'Use OSL-3.0';
            $this->messageBucket->add($message, MessageGroupLabels::GROUP_COMPOSER_ISSUES, $suggestion, $moduleName);

        }
    }

    private function addInvalidLicenseMessage(string $moduleName): void
    {
        $message = 'Composer file mentions an invalid license';
        $suggestion = 'Use a SPDX license identifier like OSL-3.0';
        $this->messageBucket->add($message, MessageGroupLabels::GROUP_COMPOSER_ISSUES, $suggestion, $moduleName);
    }

    /**
     * @return string[]|null
     * @throws FileSystemException
     */
    private function getKnownLicenses(): ?array
    {
        if (false === $this->isLicenseListLoaded) {
            $this->knownLicenses = $this->fetchLicenseList();
            $this->isLicenseListLoaded = true;
        }

        return $this->knownLicenses;
    }

    /**
     * @return string[]|null
     * @throws FileSystemException
     */
    private function fetchLicenseList(): ?array
    {
        $varFolder = $this->directoryList->getPath(DirectoryList::VAR_DIR);
        $cacheFile = 'yireo_extensionchecker/spdx-licenses-' . self::LICENSE_LIST_VERSION . '.json';

        try {
            $licenses = json_decode((string)$this->cacheFile->read($varFolder, $cacheFile), true);
            if ($this->isValidLicenseList($licenses)) {
                return $licenses;
            }

            $client = $this->clientFactory->create();
            $response = $client->get(self::LICENSE_LIST_URL, ['timeout' => 10, 'connect_timeout' => 5]);
            $licenses = $this->getLicenseIds(json_decode((string)$response->getBody(), true));
        } catch (Exception $exception) {
            $licenses = null;
        }

        if (null === $licenses) {
            $message = 'Unable to fetch the SPDX license list, skipping license check';
            $this->messageBucket->add($message, MessageGroupLabels::GROUP_COMPOSER_ISSUES);
            return null;
        }

        if (false === $this->cacheFile->write($varFolder, $cacheFile, (string)json_encode($licenses))) {
            $this->messageBucket->debug('Unable to cache the SPDX license list in "' . $varFolder . '/' . $cacheFile . '"');
        }

        return $licenses;
    }

    /**
     * @param mixed $data
     * @return string[]|null
     */
    private function getLicenseIds($data): ?array
    {
        if (!is_array($data) || !isset($data['licenses']) || !is_array($data['licenses'])) {
            return null;
        }

        $licenses = [];
        foreach ($data['licenses'] as $license) {
            if (!is_array($license) || !isset($license['licenseId']) || !is_string($license['licenseId'])) {
                return null;
            }

            $licenses[] = $license['licenseId'];
        }

        return $this->isValidLicenseList($licenses) ? $licenses : null;
    }

    /**
     * @param mixed $licenses
     * @return bool
     */
    private function isValidLicenseList($licenses): bool
    {
        return is_array($licenses)
            && $licenses !== []
            && count(array_filter($licenses, 'is_string')) === count($licenses);
    }
}
