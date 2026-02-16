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
        $response = $this->imageKit->upload([
            'file' => fopen($file->getRealPath(), 'r'),
            'fileName' => uniqid() . '.' . $file->getClientOriginalExtension(),
            'folder' => $folder,
        ]);

        if (!isset($response->result)) {
            throw new \Exception('ImageKit upload failed');
        }

        return [
            'url' => $response->result->url,
            'fileId' => $response->result->fileId,
            'name' => $response->result->name,
            'size' => $response->result->size,
            'mime' => $response->result->mime,
        ];
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

