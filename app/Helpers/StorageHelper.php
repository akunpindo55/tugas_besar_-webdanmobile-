<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class StorageHelper
{
    public static function publicUrl(string $path, string $disk = 'supabase'): string
    {
        $endpoint = rtrim(config("filesystems.disks.{$disk}.endpoint"), '/');
        $endpoint = str_replace('/s3', '/object/public', $endpoint);
        $bucket = config("filesystems.disks.{$disk}.bucket");
        return "$endpoint/$bucket/$path";
    }

    public static function storeFile($file, string $directory, string $disk = 'supabase'): ?string
    {
        try {
            $path = $file->store($directory, $disk);
            if (!$path) return null;
            return self::publicUrl($path, $disk);
        } catch (\Exception $e) {
            return null;
        }
    }
}
