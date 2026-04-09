<?php

namespace App\Services;

use ImageKit\ImageKit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ImageKitService
{
    protected ImageKit $imageKit;

    public function __construct()
    {
        $this->imageKit = new ImageKit(
            config('services.imagekit.public_key'),
            config('services.imagekit.private_key'),
            config('services.imagekit.url_endpoint')
        );
    }

    public function upload(UploadedFile $file, string $folder): array
    {
        $fileHandle = fopen($file->getRealPath(), 'r');

        try {
            $response = $this->imageKit->upload([
                'file' => $fileHandle,
                'fileName' => uniqid() . '.' . $file->getClientOriginalExtension(),
                'folder' => $folder,
            ]);
        } finally {
            if (is_resource($fileHandle)) {
                fclose($fileHandle);
            }
        }

        if (isset($response->error)) {
            throw new \Exception($this->resolveUploadErrorMessage($response->error));
        }

        if (!isset($response->result) || !is_object($response->result)) {
            throw new \Exception('ImageKit upload failed');
        }

        $result = $response->result;

        return [
            'url' => $result->url,
            'fileId' => $result->fileId,
            'name' => $result->name,
            'size' => $result->size,
            'mime' => $result->mime ?? $result->mimeType ?? $file->getMimeType(),
            'fileType' => $result->fileType ?? null,
        ];
    }

    private function resolveUploadErrorMessage(mixed $error): string
    {
        if (is_string($error) && $error !== '') {
            return $error;
        }

        if (is_object($error)) {
            if (isset($error->message) && is_string($error->message) && $error->message !== '') {
                return $error->message;
            }

            if (isset($error->help) && is_string($error->help) && $error->help !== '') {
                return $error->help;
            }
        }

        return 'ImageKit upload failed';
    }

    /**
     * Delete image from ImageKit
     */
    public function delete(string $fileId): bool
    {
        try {
            $response = $this->imageKit->deleteFile($fileId);
            return isset($response->result) && $response->result === 'success';
        } catch (\Exception $e) {
            Log::error('ImageKit delete failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete image by URL (extracts fileId from URL)
     */
    public function deleteByUrl(string $url): bool
    {
        try {
            // Extract fileId from ImageKit URL
            // ImageKit URLs format: https://ik.imagekit.io/your_imagekit_id/folder/file.jpg
            // We need to get fileId by querying ImageKit API
            $path = parse_url($url, PHP_URL_PATH);
            if (!$path) {
                return false;
            }

            // Remove leading slash and get file path
            $filePath = ltrim($path, '/');
            
            // Get file details from ImageKit
            $response = $this->imageKit->listFiles([
                'path' => $filePath,
                'limit' => 1
            ]);

            if (isset($response->result) && count($response->result) > 0) {
                $fileId = $response->result[0]->fileId;
                return $this->delete($fileId);
            }

            return false;
        } catch (\Exception $e) {
            Log::error('ImageKit deleteByUrl failed: ' . $e->getMessage());
            return false;
        }
    }
}
