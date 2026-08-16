<?php

namespace HMsoft\Tools\Features\Attribute\Support;

final class PrunesEmptyLocales
{
    /**
     * Drop locale rows that have no content so a single filled language is enough.
     * Also copies `title` onto `label` when the option payload uses title.
     *
     * @param  array<int, mixed>  $locales
     * @return list<array<string, mixed>>
     */
    public static function prune(array $locales, string $requiredField = 'label'): array
    {
        $kept = [];

        foreach ($locales as $row) {
            if (! is_array($row)) {
                continue;
            }

            if ($requiredField === 'label' && ! filled($row['label'] ?? null) && filled($row['title'] ?? null)) {
                $row['label'] = $row['title'];
            }

            $content = $row[$requiredField] ?? null;
            if (is_string($content)) {
                $content = trim($content);
                $row[$requiredField] = $content;
            }

            if (! filled($content)) {
                continue;
            }

            $kept[] = $row;
        }

        return array_values($kept);
    }
}
