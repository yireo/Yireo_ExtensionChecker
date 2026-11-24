<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Suggestion;

use DOMComment;
use DOMDocument;
use DOMElement;
use Magento\Framework\Component\ComponentRegistrar;
use Yireo\ExtensionChecker\ComponentDetector\ComponentDetectorList;
use Yireo\ExtensionChecker\Composer\MagentoPackageResolver;

class ModuleXmlSuggestion extends AbstractSuggestion
{
    private const DEFAULT_HEADER = '<?xml version="1.0"?>' . "\n";

    public function __construct(
        ComponentRegistrar $componentRegistrar,
        private ComponentDetectorList $componentDetectorList,
        private MagentoPackageResolver $magentoPackageResolver
    ) {
        parent::__construct($componentRegistrar);
    }

    public function getCurrentPath(string $moduleName): string
    {
        return $this->getModulePath($moduleName) . '/etc/module.xml';
    }

    public function getSuggestedContents(string $moduleName, array $options = []): string
    {
        $currentContents = $this->getCurrentContents($moduleName);
        $document = $this->loadDocument($currentContents);
        $moduleXml = $this->getModuleXml(
            $moduleName,
            $this->getModuleDependencies($moduleName),
            $this->getHeader($document),
            $this->getModuleAttributes($document)
        );

        return $this->matchTrailingNewline($moduleXml, $currentContents);
    }

    /**
     * @return string[]
     */
    public function getModuleDependencies(string $moduleName): array
    {
        $components = $this->componentDetectorList->getComponentsByModuleName($moduleName);
        $moduleDependencies = [];
        foreach ($components as $component) {
            if (false === $component->hasComponentName()) {
                continue;
            }

            $moduleDependencies[] = $this->magentoPackageResolver->getMagentoModuleName($component)
                ?? $component->getComponentName();
        }

        $moduleDependencies = array_values(array_unique($moduleDependencies));
        sort($moduleDependencies);

        return $moduleDependencies;
    }

    /**
     * @param string[] $moduleDependencies
     * @param array<string, string> $moduleAttributes
     */
    private function getModuleXml(
        string $moduleName,
        array $moduleDependencies,
        string $header,
        array $moduleAttributes
    ): string {
        $moduleTag = '<module name="' . htmlspecialchars($moduleName, ENT_XML1 | ENT_QUOTES) . '"';
        foreach ($moduleAttributes as $attributeName => $attributeValue) {
            $moduleTag .= ' ' . $attributeName . '="' . htmlspecialchars($attributeValue, ENT_XML1 | ENT_QUOTES) . '"';
        }

        $xml = $header;
        $xml .= '<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
            . ' xsi:noNamespaceSchemaLocation="urn:magento:framework:Module/etc/module.xsd">' . "\n";

        if (empty($moduleDependencies)) {
            $xml .= '    ' . $moduleTag . '/>' . "\n";
            $xml .= '</config>' . "\n";
            return $xml;
        }

        $xml .= '    ' . $moduleTag . '>' . "\n";
        $xml .= '        <sequence>' . "\n";
        foreach ($moduleDependencies as $moduleDependency) {
            $xml .= '            <module name="' . htmlspecialchars($moduleDependency, ENT_XML1 | ENT_QUOTES) . '"/>' . "\n";
        }

        $xml .= '        </sequence>' . "\n";
        $xml .= '    </module>' . "\n";
        $xml .= '</config>' . "\n";

        return $xml;
    }

    private function loadDocument(string $currentContents): ?DOMDocument
    {
        if (trim($currentContents, " \f\n\r\t\v\x00") === '') {
            return null;
        }

        $document = new DOMDocument();
        $previousErrorSetting = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($currentContents, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorSetting);

        if (false === $loaded || null === $document->documentElement) {
            return null;
        }

        return $document;
    }

    /**
     * Return the XML declaration and the comments before the <config> element of the current file
     */
    private function getHeader(?DOMDocument $document): string
    {
        if (null === $document) {
            return self::DEFAULT_HEADER;
        }

        $header = '<?xml version="1.0"' . (empty($document->xmlEncoding) ? '' : ' encoding="UTF-8"') . '?>' . "\n";
        foreach ($document->childNodes as $childNode) {
            if ($childNode->isSameNode($document->documentElement)) {
                break;
            }

            if ($childNode instanceof DOMComment) {
                $header .= '<!--' . $childNode->data . '-->' . "\n";
            }
        }

        return $header;
    }

    /**
     * Return additional attributes (like "setup_version") of the current <module> element
     *
     * @return array<string, string>
     */
    private function getModuleAttributes(?DOMDocument $document): array
    {
        if (null === $document) {
            return [];
        }

        $moduleAttributes = [];
        foreach ($document->documentElement->childNodes as $childNode) {
            if (false === $childNode instanceof DOMElement || $childNode->nodeName !== 'module') {
                continue;
            }

            foreach ($childNode->attributes as $attribute) {
                if ($attribute->nodeName === 'name') {
                    continue;
                }

                $moduleAttributes[$attribute->nodeName] = (string)$attribute->nodeValue;
            }

            break;
        }

        return $moduleAttributes;
    }
}
