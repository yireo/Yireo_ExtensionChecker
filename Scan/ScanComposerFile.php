<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Scan;

use GuzzleHttp\ClientFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NotFoundException;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\Composer\ComposerFileProvider;
use Yireo\ExtensionChecker\Message\MessageBucket;
use Yireo\ExtensionChecker\Message\MessageGroupLabels;

class ScanComposerFile
{
    private ComposerFileProvider $composerFileProvider;
    private MessageBucket $messageBucket;
    private ClientFactory $clientFactory;

    public function __construct(
        ComposerFileProvider $composerFileProvider,
        MessageBucket $messageBucket,
        ClientFactory $clientFactory
    ) {
        $this->composerFileProvider = $composerFileProvider;
        $this->messageBucket = $messageBucket;
        $this->clientFactory = $clientFactory;
    }

    /**
     * @param string $moduleName
     * @param Component[] $components
     * @return void
     * @throws FileSystemException
     * @throws NotFoundException
     */
    public function scan(string $moduleName)
    {
        $composerFile = $this->composerFileProvider->getComposerFileByModuleName($moduleName);
        $licenses = $composerFile->get('license');
        if (is_string($licenses)) {
            $licenses = [$licenses];
        }

        $knownLicenses = $this->fetchLicenseList();
        foreach ($licenses as $license) {
            if (in_array($license, $knownLicenses)) {
                continue;
            }

            $message = 'Composer file mentions unsupported license: '.$license;
            $suggestion = 'Use OSL-3.0';
            $this->messageBucket->add($message, MessageGroupLabels::GROUP_COMPOSER_ISSUES, $suggestion, $moduleName);

        }
    }

    private function fetchLicenseList(): array
    {
        $client = $this->clientFactory->create();
        $response = $client->get('https://raw.githubusercontent.com/spdx/license-list-data/refs/heads/main/json/licenses.json');
        $data = json_decode($response->getBody()->getContents(), true);

        $licenses = [];
        foreach ($data['licenses'] as $license) {
            $licenses[] = $license['licenseId'];
        }

        return $licenses;
    }
}
