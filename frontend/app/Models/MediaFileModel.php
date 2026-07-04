<?php

namespace App\Models;

use CodeIgniter\Model;

class MediaFileModel extends Model
{
    protected $table         = 'media_files';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = false;

    protected $allowedFields = [
        'uploader_id', 'file_type', 'original_name', 'stored_name',
        'file_path', 'file_url', 'mime_type', 'file_size',
        'thumbnail_path', 'duration', 'width', 'height',
        'checksum', 'storage_driver', 'created_at',
    ];

    public function getUserMedia(int $userId, string $type = '', int $limit = 30, int $offset = 0): array
    {
        $q = $this->where('uploader_id', $userId);
        if ($type) $q->where('file_type', $type);
        return $q->orderBy('created_at', 'DESC')->limit($limit, $offset)->findAll();
    }
}
