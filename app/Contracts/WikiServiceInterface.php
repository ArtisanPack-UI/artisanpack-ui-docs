<?php

namespace App\Contracts;

interface WikiServiceInterface
{
    /**
     * Get all wiki pages with their content
     *
     * @param  string  $wikiUrl  The URL to the wiki
     * @return array<int, array{slug: string, title: string, content: string}>
     *
     * @throws \Exception
     */
    public function getWikiPagesWithContent(string $wikiUrl): array;

    /**
     * Get raw file content from a repository
     *
     * @param  string  $fileUrl  The URL to the file
     *
     * @throws \Exception
     */
    public function getFileContent(string $fileUrl): string;
}
