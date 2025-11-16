<?php

namespace Modules\Core\Services;

use Illuminate\Support\Str;

class TableOfContentsService
{
    /**
     * Extract headings from content and build table of contents
     *
     * @return array{headings: array<int, array{id: string, text: string, level: int}>, content: string}
     */
    public function process(string $content, bool $isMarkdown = false): array
    {
        if ($isMarkdown) {
            // Convert markdown to HTML first
            $content = Str::markdown($content);
        }

        // Extract and add IDs to headings
        $headings = [];
        $content = $this->addIdsToHeadings($content, $headings);

        return [
            'headings' => $headings,
            'content' => $content,
        ];
    }

    /**
     * Add IDs to headings and extract heading information
     *
     * @param  array<int, array{id: string, text: string, level: int}>  $headings
     */
    protected function addIdsToHeadings(string $content, array &$headings): string
    {
        // Match all heading tags (h1-h6)
        $pattern = '/<h([1-6])(?:\s+[^>]*)?>(.+?)<\/h\1>/i';

        return preg_replace_callback($pattern, function ($matches) use (&$headings) {
            $level = (int) $matches[1];
            $text = strip_tags($matches[2]);
            $id = $this->generateId($text);

            // Check if ID already exists in the heading tag
            if (! preg_match('/id=["\']([^"\']+)["\']/', $matches[0])) {
                // Add heading to array
                $headings[] = [
                    'id' => $id,
                    'text' => $text,
                    'level' => $level,
                ];

                // Return heading with ID
                return preg_replace(
                    '/<h'.$level.'(?:\s+[^>]*)?>/i',
                    '<h'.$level.' id="'.$id.'">',
                    $matches[0],
                    1
                );
            }

            // Heading already has an ID, extract it
            preg_match('/id=["\']([^"\']+)["\']/', $matches[0], $idMatch);
            $existingId = $idMatch[1] ?? $id;

            $headings[] = [
                'id' => $existingId,
                'text' => $text,
                'level' => $level,
            ];

            return $matches[0];
        }, $content);
    }

    /**
     * Generate a URL-friendly ID from heading text
     */
    protected function generateId(string $text): string
    {
        // Remove HTML entities and special characters
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/[^a-zA-Z0-9\s-]/', '', $text);
        $text = preg_replace('/\s+/', '-', trim($text));
        $text = strtolower($text);

        return $text;
    }

    /**
     * Build nested table of contents structure
     *
     * @param  array<int, array{id: string, text: string, level: int}>  $headings
     * @return array<int, array{id: string, text: string, level: int, children?: array}>
     */
    public function buildNestedStructure(array $headings): array
    {
        if (empty($headings)) {
            return [];
        }

        $nested = [];
        $stack = [];

        foreach ($headings as $heading) {
            $item = [
                'id' => $heading['id'],
                'text' => $heading['text'],
                'level' => $heading['level'],
                'children' => [],
            ];

            // Find the appropriate parent
            while (! empty($stack) && end($stack)['level'] >= $item['level']) {
                array_pop($stack);
            }

            if (empty($stack)) {
                // Top-level heading
                $nested[] = $item;
                $stack[] = &$nested[count($nested) - 1];
            } else {
                // Add as child to the last item in stack
                $parent = &$stack[count($stack) - 1];
                $parent['children'][] = $item;
                $stack[] = &$parent['children'][count($parent['children']) - 1];
            }
        }

        return $nested;
    }
}
