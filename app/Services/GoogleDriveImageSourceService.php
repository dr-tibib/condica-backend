<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleDriveImageSourceService
{
    /**
     * @return list<array{id:string,name:string,mimeType:string}>
     */
    public function listImagesInFolder(string $folderId, string $accessToken, ?int $limit = null): array
    {
        $files = [];
        $pageToken = null;

        do {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get('https://www.googleapis.com/drive/v3/files', array_filter([
                    'q' => "'{$folderId}' in parents and trashed=false",
                    'fields' => 'nextPageToken,files(id,name,mimeType)',
                    'pageSize' => 1000,
                    'pageToken' => $pageToken,
                ], static fn (mixed $value): bool => $value !== null));

            if (! $response->successful()) {
                throw new RuntimeException('Google Drive API error: '.$response->status().' '.$response->body());
            }

            $json = $response->json();
            $pageToken = is_array($json) ? ($json['nextPageToken'] ?? null) : null;

            $pageFiles = is_array($json) && isset($json['files']) && is_array($json['files'])
                ? $json['files']
                : [];

            foreach ($pageFiles as $file) {
                if (! is_array($file)) {
                    continue;
                }

                $id = (string) ($file['id'] ?? '');
                $name = (string) ($file['name'] ?? '');
                $mimeType = (string) ($file['mimeType'] ?? '');

                if ($id === '' || $name === '') {
                    continue;
                }

                if (! str_starts_with($mimeType, 'image/')) {
                    continue;
                }

                $files[] = [
                    'id' => $id,
                    'name' => $name,
                    'mimeType' => $mimeType,
                ];

                if ($limit !== null && count($files) >= $limit) {
                    return $files;
                }
            }
        } while (is_string($pageToken) && $pageToken !== '');

        return $files;
    }

    /**
     * Public-ish download URL that Bunny sync can fetch later.
     */
    public function buildPublicDownloadUrl(string $fileId): string
    {
        return 'https://drive.google.com/uc?export=download&id='.$fileId;
    }
}
