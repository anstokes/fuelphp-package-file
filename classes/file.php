<?php

namespace Anstech;

/**
 * Description of file
 *
 * @author Adrian Stokes <adrian@anstech.co.uk>
 */
class File
{
    /**
     * The user under which files/directories should be created
     * @var string|int
     */
    protected static $web_user = 'www-data';

    /**
     * The group under which files/directories should be created
     * @var string|int
     */
    protected static $web_group = 'www-data';

    public static function fileExtension($filename)
    {
        return substr(strrchr(strtolower($filename), '.'), 1);
    }

    public static function read($filepath)
    {
        if (file_exists($filepath)) {
            return file_get_contents($filepath);
        }

        return false;
    }


    public static function write($file, $content, $create_path = false, $overwrite = false, $trim = true)
    {
        // Create path
        if ($create_path) {
            static::createPath($file);
        }

        // Write file
        $fp = fopen($file, ($overwrite ? 'w' : 'a'));
        if ($fp) {
            fwrite($fp, ($trim ? trim($content) : $content));
            fclose($fp);
            return $file;       // Return filename if written
        }

        // Unable to write file
        return false;
    }


    public static function copyFile($source, $target, $create_target_path = false, $overwrite = false)
    {
        // Create path
        if ($create_target_path) {
            static::createPath($target);
        }

        // Remove if existing
        if ($overwrite && file_exists($target)) {
            @unlink($target);
        }

        // Copy from source to target
        @copy($source, $target);

        // Ensure target created
        if (file_exists($target)) {
            // Change ownership
            static::changeOwnership($target);

            // TODO - addition checks on target (e.g. filesize)
            return true;
        }

        return false;
    }


    public static function moveFile($source, $target, $create_target_path = false)
    {
        // Create path
        if ($create_target_path) {
            static::createPath($target);
        }

        // Move file
        @rename($source, $target);

        // Change ownership
        static::changeOwnership($target);
    }


    public static function createPath($path, $root_path = false)
    {
        // Split on either forward OR back slash
        //$directories = explode(DS, $path);
        $directories = preg_split('`[\\\/]`', $path);

        // Remove last entry (filename)
        $filename = array_pop($directories);
        $directory = $root_path;

        // Loop through directories
        if ($directories) {
            foreach ($directories as $sub_directory) {
                $directory .= $sub_directory . DS;
                // Check directory exists
                if (! is_dir($directory)) {
                    try {
                        // Create subdirectory
                        mkdir($directory);
                        // Change ownership
                        static::changeOwnership($directory);
                    } catch (\Exception $e) {
                        throw new \Exception('Unable to create directory: ' . $directory);
                    }
                }
            }
        }

        return $directory . $filename;
    }


    public static function changeOwnership($path)
    {
        // Check if directory owner should be changed
        if (static::$web_user) {
            @chown($path, static::$web_user);
        }

        // Check if directory group should be changed
        if (static::$web_group) {
            @chgrp($path, static::$web_group);
        }
    }


    public static function remove($file)
    {
        // Check if file exists
        if (file_exists($file)) {
            // Attempt to remove file
            @unlink($file);
        }
    }
}
