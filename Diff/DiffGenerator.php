<?php declare(strict_types=1);

namespace Yireo\ExtensionChecker\Diff;

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\StrictUnifiedDiffOutputBuilder;

class DiffGenerator
{
    /**
     * Return a unified diff between two strings, or an empty string when both are identical
     */
    public function diff(
        string $original,
        string $new,
        string $originalLabel = 'original',
        string $newLabel = 'new'
    ): string {
        if ($original === $new) {
            return '';
        }

        $outputBuilder = new StrictUnifiedDiffOutputBuilder([
            'fromFile' => $originalLabel,
            'toFile' => $newLabel,
        ]);

        return (new Differ($outputBuilder))->diff($original, $new);
    }
}
