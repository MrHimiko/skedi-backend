<?php

namespace App\Plugins\Storage\Service;

use Aws\S3\S3Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\MimeTypes;

use App\Plugins\Storage\Exception\StorageException;

use App\Plugins\Storage\Entity\FolderEntity;
use App\Plugins\Storage\Entity\FileEntity;

use App\Plugins\Account\Entity\OrganizationEntity;

use App\Plugins\Storage\Service\FileService;
use App\Plugins\Storage\Repository\FileRepository;

class UploadService
{
    private array $mimeTypes = [];

    private FileRepository $fileRepository;
    private EntityManagerInterface $entityManager;
    private S3Client $s3Client;
    private FileService $fileService;

    public function __construct(
        FileRepository $fileRepository,
        EntityManagerInterface $entityManager,
        S3Client $s3Client,
        FileService $fileService
    )
    {
        $this->fileRepository = $fileRepository;
        $this->entityManager = $entityManager;
        $this->s3Client = $s3Client;
        $this->fileService = $fileService;

        $this->mimeTypes = [
            'image/jpeg' => 5 * 1024 * 1024,
            'image/png' => 5 * 1024 * 1024,
            'image/gif' => 2 * 1024 * 1024,
            'image/webp' => 5 * 1024 * 1024,
            'image/svg+xml' => 1 * 1024 * 1024,
            'video/mp4' => 50 * 1024 * 1024,
            'video/mpeg' => 50 * 1024 * 1024,
            'video/quicktime' => 50 * 1024 * 1024,
            'audio/mpeg' => 10 * 1024 * 1024,
            'audio/ogg' => 5 * 1024 * 1024,
            'audio/wav' => 20 * 1024 * 1024,
            'application/pdf' => 10 * 1024 * 1024,
            'application/msword' => 5 * 1024 * 1024,
            'application/vnd.ms-excel' => 5 * 1024 * 1024,
            'application/vnd.ms-powerpoint' => 10 * 1024 * 1024,
            'text/plain' => 1 * 1024 * 1024,
            'text/html' => 1 * 1024 * 1024,
            'application/zip' => 50 * 1024 * 1024,
            'application/x-rar-compressed' => 50 * 1024 * 1024,
            'application/gzip' => 50 * 1024 * 1024,
            'application/json' => 2 * 1024 * 1024,
        ];
    }

    public function uploadFile(OrganizationEntity $organization, UploadedFile $file, ?FolderEntity $folder = null, ?callable $callback = null): FileEntity
    {
        $content = file_get_contents($file->getPathname());

        return $this->uploadContent($organization, $content, $file->getClientOriginalName(), $file->getMimeType(), $folder, $callback);
    }
    
    public function uploadFromUrl(OrganizationEntity $organization, string $url, ?FolderEntity $folder = null, ?callable $callback = null): FileEntity
    {
        if(!filter_var($url, FILTER_VALIDATE_URL)) 
        {
            throw new StorageException('Invalid URL.');
        }

        if(!$content = @file_get_contents($url)) 
        {
            throw new StorageException('Failed to retrieve content from URL.');
        }

        return $this->uploadContent($organization, $content, $this->generateNameFromUrl($url), null, $folder, $callback);
    }

    public function uploadContent(OrganizationEntity $organization, string $content, string $name, ?string $mimeType = null, ?FolderEntity $folder = null, ?callable $callback = null): FileEntity
    {
        $hash = $this->generateHash($content);
        $tempFile = $this->generateTempFile($content);

        $size = strlen($content);

        $mimeTypes = new MimeTypes();

        if(!$mimeType)
        {
            $mimeType = $mimeTypes->guessMimeType($tempFile); 
        }

        if(!$extension = $mimeTypes->getExtensions($mimeType)[0] ?? null)
        {
            throw new StorageException('File extension is required.');
        }

        if(!isset($this->mimeTypes[$mimeType]))
        {
            throw new StorageException("File type '" . $mimeType . "' is not allowed.");
        }

        if($size > $this->mimeTypes[$mimeType])
        {
            throw new StorageException("File size '" . number_format(($size / 1024 / 1024), 3) .  "mb' is too large. Allowed: " . number_format(($this->mimeTypes[$mimeType] / 1024 / 1024), 3) . "mb.");
        }

        unlink($tempFile); 

        try 
        {
            $this->uploadToS3(($hash . '.' . $extension), $content, $mimeType);
            
            return $this->createFile($organization, $name, $hash, $size, $mimeType, $extension, $folder, $callback);
        }
        catch(StorageException $e)
        {
            throw new StorageException($e->getMessage());
        }
        catch (\Exception $e) 
        {
            throw new \Exception($e->getMessage());
        }
    }

    private function uploadToS3(string $key, string $body, string $mimeType, string $bucket = 'rentsera'): void
    {
        $this->s3Client->putObject([
            'Bucket'      => $bucket,
            'Key'         => $key,
            'Body'        => $body,
            'ContentType' => $mimeType,
        ]);
    }

    private function createFile(OrganizationEntity $organization, string $name, string $hash, int $size, string $mimeType, string $extension, ?FolderEntity $folder = null, ?callable $callback = null): FileEntity
    {
        return $this->fileService->create($organization, [
            'name'      => $name,
            'hash'      => $hash,
            'size'      => $size,
            'type'      => $mimeType,
            'extension' => $extension,
        ], $folder, $callback);
    }

    private function generateHash(string $content): string
    {
        return hash('sha256', $content);
    }

    private function generateTempFile(string $content): string
    {
        $tempFilePath = tempnam(sys_get_temp_dir(), 'upload_');
        file_put_contents($tempFilePath, $content);

        return $tempFilePath;
    }

    private function generateNameFromUrl(string $url): string
    {
        return basename(parse_url($url, PHP_URL_PATH));
    }
}