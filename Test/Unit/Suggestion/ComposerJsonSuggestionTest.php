<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Test\Unit\Suggestion;

use Magento\Framework\Component\ComponentRegistrar;
use PHPUnit\Framework\TestCase;
use Yireo\ExtensionChecker\Component\Component;
use Yireo\ExtensionChecker\ComponentDetector\ComponentDetectorList;
use Yireo\ExtensionChecker\Composer\ComposerFileProvider;
use Yireo\ExtensionChecker\Composer\ComposerProvider;
use Yireo\ExtensionChecker\Composer\ComposerRequirementsMerger;
use Yireo\ExtensionChecker\Exception\ComposerException;
use Yireo\ExtensionChecker\Exception\ComposerFileNotFoundException;
use Yireo\ExtensionChecker\Suggestion\ComposerJsonSuggestion;
use Yireo\ExtensionChecker\Test\Unit\Behaviour\TemporaryFolder;

class ComposerJsonSuggestionTest extends TestCase
{
    use TemporaryFolder;

    public function testGetCurrentPath()
    {
        $moduleFolder = $this->createTemporaryFolder();
        $suggestion = $this->getSuggestion($moduleFolder, []);
        $this->assertSame($moduleFolder . '/composer.json', $suggestion->getCurrentPath('Foo_Bar'));
    }

    public function testGetSuggestedContents()
    {
        $moduleFolder = $this->createTemporaryFolder(['composer.json' => $this->toJson([
            'name' => 'foo/bar',
            'require' => [
                'foo/obsolete' => '^1.0',
                'magento/framework' => '^103.0|^104.0',
            ],
            'autoload' => ['psr-4' => ['Foo\\Bar\\' => '']],
        ]) . "\n"]);

        $suggestion = $this->getSuggestion($moduleFolder, [
            new Component('Magento_Framework', 'library', 'magento/framework', '103.0.7'),
            new Component('Magento_Store', 'module', 'magento/module-store', '101.0.0'),
            new Component('psr/log', 'library', 'psr/log', '3.0.0'),
            new Component('symfony/console', 'library', 'symfony/console', '7.4.1'),
            new Component('ext-json', 'library', 'ext-json', '8.4.0'),
            new Component('PHPUnit', 'library', 'phpunit/phpunit', '10.5.1', false, true),
        ]);

        $suggestedData = json_decode($suggestion->getSuggestedContents('Foo_Bar'), true);
        $this->assertSame([
            'ext-json' => '*',
            'magento/framework' => '^103.0|^104.0',
            'magento/module-store' => '^101.0',
            'psr/log' => '^1.0 || ^2.0 || ^3.0',
            'symfony/console' => '^5.0 | ^6.0 | ^7.0',
        ], $suggestedData['require']);
        $this->assertSame(['phpunit/phpunit' => '^10.5'], $suggestedData['require-dev']);
        $this->assertSame(['name', 'require', 'require-dev', 'autoload'], array_keys($suggestedData));
    }

    public function testGetSuggestedContentsWithPhp()
    {
        $moduleFolder = $this->createTemporaryFolder(['composer.json' => $this->toJson(['name' => 'foo/bar'])]);
        $suggestion = $this->getSuggestion($moduleFolder, []);

        $suggestedData = json_decode($suggestion->getSuggestedContents('Foo_Bar'), true);
        $this->assertArrayNotHasKey('php', $suggestedData['require']);

        $suggestedData = json_decode(
            $suggestion->getSuggestedContents('Foo_Bar', [ComposerJsonSuggestion::OPTION_WITH_PHP => true]),
            true
        );
        $this->assertSame('>=8.2 <8.6', $suggestedData['require']['php']);
    }

    public function testGetSuggestedContentsRemovesEmptyRequireDev()
    {
        $moduleFolder = $this->createTemporaryFolder(['composer.json' => $this->toJson([
            'name' => 'foo/bar',
            'require' => ['magento/module-store' => '^101.0'],
            'require-dev' => ['magento/module-store' => '^101.0'],
        ])]);

        $suggestion = $this->getSuggestion($moduleFolder, [
            new Component('Magento_Store', 'module', 'magento/module-store', '101.0.0'),
        ]);

        $suggestedData = json_decode($suggestion->getSuggestedContents('Foo_Bar'), true);
        $this->assertArrayNotHasKey('require-dev', $suggestedData);
    }

    public function testGetSuggestedContentsSkipsDevDependencyAlreadyInRequire()
    {
        $moduleFolder = $this->createTemporaryFolder(['composer.json' => $this->toJson([
            'name' => 'foo/bar',
            'require' => ['magento/module-store' => '^101.0'],
        ])]);

        $suggestion = $this->getSuggestion($moduleFolder, [
            new Component('Magento_Store', 'module', 'magento/module-store', '101.0.0'),
            new Component('Magento\Store', 'library', 'Magento/Module-Store', '101.0.0', false, true),
        ]);

        $suggestedData = json_decode($suggestion->getSuggestedContents('Foo_Bar'), true);
        $this->assertSame(['magento/module-store' => '^101.0'], $suggestedData['require']);
        $this->assertArrayNotHasKey('require-dev', $suggestedData);
    }

    public function testGetSuggestedContentsWithEmptyRequireRemainsAnObject()
    {
        $moduleFolder = $this->createTemporaryFolder(['composer.json' => $this->toJson([
            'name' => 'foo/bar',
            'require' => ['foo/obsolete' => '^1.0'],
        ])]);

        $suggestion = $this->getSuggestion($moduleFolder, []);
        $this->assertStringContainsString('"require": {}', $suggestion->getSuggestedContents('Foo_Bar'));
    }

    public function testGetSuggestedContentsMatchesTrailingNewline()
    {
        $composerJson = $this->toJson(['name' => 'foo/bar', 'require' => ['psr/log' => '^1.0 || ^2.0 || ^3.0']]);
        $components = [new Component('psr/log', 'library', 'psr/log', '3.0.0')];

        $moduleFolder = $this->createTemporaryFolder(['composer.json' => $composerJson]);
        $suggestion = $this->getSuggestion($moduleFolder, $components);
        $this->assertSame($composerJson, $suggestion->getSuggestedContents('Foo_Bar'));

        $moduleFolder = $this->createTemporaryFolder(['composer.json' => $composerJson . "\n"]);
        $suggestion = $this->getSuggestion($moduleFolder, $components);
        $this->assertSame($composerJson . "\n", $suggestion->getSuggestedContents('Foo_Bar'));
    }

    public function testGetSuggestedContentsWithMissingComposerJson()
    {
        $moduleFolder = $this->createTemporaryFolder();
        $suggestion = $this->getSuggestion($moduleFolder, []);
        $this->expectException(ComposerFileNotFoundException::class);
        $suggestion->getSuggestedContents('Foo_Bar');
    }

    public function testGetSuggestedContentsWithInvalidComposerJson()
    {
        $moduleFolder = $this->createTemporaryFolder(['composer.json' => '{"name": ']);
        $suggestion = $this->getSuggestion($moduleFolder, []);
        $this->expectException(ComposerException::class);
        $suggestion->getSuggestedContents('Foo_Bar');
    }

    private function toJson(array $data): string
    {
        return (string)json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param Component[] $components
     */
    private function getSuggestion(string $moduleFolder, array $components): ComposerJsonSuggestion
    {
        $componentRegistrar = $this->createMock(ComponentRegistrar::class);
        $componentRegistrar->method('getPath')
            ->with(ComponentRegistrar::MODULE, 'Foo_Bar')
            ->willReturn($moduleFolder);

        $componentDetectorList = $this->createMock(ComponentDetectorList::class);
        $componentDetectorList->method('getComponentsByModuleName')
            ->with('Foo_Bar')
            ->willReturn($components);

        $composerProvider = new ComposerProvider($this->createMock(ComposerFileProvider::class));

        return new ComposerJsonSuggestion(
            $componentRegistrar,
            $componentDetectorList,
            $composerProvider,
            new ComposerRequirementsMerger()
        );
    }
}
