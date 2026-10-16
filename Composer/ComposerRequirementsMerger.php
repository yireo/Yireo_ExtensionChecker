<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Composer;

class ComposerRequirementsMerger
{
    /**
     * Merge detected requirements into the original "require" section, removing undetected packages
     *
     * @param array $original
     * @param array $new
     * @return array
     */
    public function mergeRequire(array $original, array $new): array
    {
        foreach ($original as $packageName => $version) {
            if (!isset($new[$packageName])) {
                unset($original[$packageName]);
            }
        }

        return $this->addRequirements($original, $new);
    }

    /**
     * Merge detected dev requirements into the original "require-dev" section, keeping undetected packages
     * (like static analysis tools) but removing packages that are already part of "require"
     *
     * @param array $original
     * @param array $new
     * @param array $require
     * @return array
     */
    public function mergeRequireDev(array $original, array $new, array $require): array
    {
        foreach ($original as $packageName => $version) {
            if (isset($require[$packageName])) {
                unset($original[$packageName]);
            }
        }

        foreach ($new as $packageName => $version) {
            if (isset($require[$packageName])) {
                unset($new[$packageName]);
            }
        }

        return $this->addRequirements($original, $new);
    }

    private function addRequirements(array $original, array $new): array
    {
        foreach ($new as $packageName => $version) {
            if (isset($original[$packageName]) && $original[$packageName] === '*') {
                $original[$packageName] = $version;
                continue;
            }

            if (!isset($original[$packageName])) {
                $original[$packageName] = $version;
            }
        }

        ksort($original);

        return $original;
    }
}
