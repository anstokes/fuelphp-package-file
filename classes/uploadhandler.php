<?php

namespace Anstech;

/**
 * Description of file
 *
 * @author Adrian Stokes <adrian@anstech.co.uk>
 */
class UploadHandler
{
    // Allowed file types
    protected static $allowed_file_types = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'pdf',
    ];

    // Local path for uploaded file to be stored
    protected static $file_path = DOCROOT . 'uploads' . DS;


    /**
     * Returns an array of uploaded files
     *
     * @param array $files
     * @return array
     */
    public static function uploadedFiles($files, $ignore_blank = false, $input_name = 'name')
    {
        $uploaded_files = [];

        // Check for upload
        if ($ignore_blank && ! $files[$input_name]) {
            // No upload
        } else {
            // Check for multiple files
            if (is_array($files[$input_name])) {
                // Multiple file upload
                $file_count = count($files[$input_name]);
                $file_keys = array_keys($files);
                for ($i = 0; $i < $file_count; $i++) {
                    if ($ignore_blank && ( ! $files[$input_name][$i])) {
                        continue;
                    }

                    foreach ($file_keys as $key) {
                        $uploaded_files[$i][$key] = $files[$key][$i];
                    }
                }
            } else {
                // Single file; convert to array for processing
                $uploaded_files = [$files];
            }
        }

        return $uploaded_files;
    }


    /**
     * Returns local path for file, by adding relevant date-related folders to root file path above
     *
     * @param string $timestamp
     * @return string
     */
    public static function filePath($timestamp = false, $include_date_folder = true)
    {
        $file_path = static::$file_path . ($include_date_folder ? static::dateFolder($timestamp) : '');
        File::create_path($file_path);  // Ensure path exists
        return $file_path;
    }


    /**
     * Attempts to store/move uploaded file to local path before executing the postMove function
     *
     * @param string $uploaded_file
     * @param string $additional_data
     * @return array
     */
    public static function addFile($uploaded_file, $additional_data = [])
    {
        if (! empty($uploaded_file['tmp_name'])) {
            // Read variables
            $temporary_name = $uploaded_file['tmp_name'];
            $original_name = $uploaded_file['name'];
            $file_extension = static::fileExtension($original_name);
            $timestamp = isset($additional_data['timestamp']) ? $additional_data['timestamp'] : false;

            // Check for allowed file type
            if (in_array($file_extension, static::$allowed_file_types)) {
                // Copy uploaded file to relevant directory
                $target_file_path = static::filePath($timestamp) . static::targetFileName($original_name, $additional_data);
                if (isset($uploaded_file['localFile']) && $uploaded_file['localFile']) {
                    // File already exists on local file system, copy it
                    File::copy_file($uploaded_file['tmp_name'], $target_file_path);
                } elseif (is_uploaded_file($temporary_name)) {
                    // Move uploaded file
                    move_uploaded_file($temporary_name, $target_file_path);
                } else {
                    // Uploaded file check failed
                    return [
                        false,
                        'Not uploaded file',
                    ];
                }

                // Check file copied/moved successfully
                if (file_exists($target_file_path)) {
                    // Post move function
                    static::postMove($target_file_path, $additional_data);

                    // Moved file
                    return [
                        $target_file_path,
                        'Added file',
                    ];
                } else {
                    // Failed to move file to correct directory
                    return [
                        false,
                        'Failed to move file',
                    ];
                }
            } else {
                // Reject unsupported file type
                return [
                    false,
                    'Unsupported file type',
                ];
            }
        }

        // File not uploaded
        return [
            false,
            'File not uploaded',
        ];
    }


    /**
     * Attempts to remove uploaded file.
     *
     * @param string            $filename   Filename to remove
     * @param false|string|int  $timestamp  Timestamp used to get the file path which includes the date folder structure
     *
     * @return array
     */
    public static function removeFile(string $filename, $timestamp = false): array
    {
        $target_file_path = static::filePath($timestamp) . $filename;

        if (file_exists($target_file_path) && unlink($target_file_path)) {
            // Post move function
            return [
                true,
                'File removed',
            ];
        }

        // Failed to remove file
        return [
            false,
            'Failed to remove file: Could not find file',
        ];
    }



    /**
     * Builds target filename from original filename
     *
     * @param string $original_name
     * @return string
     */
    public static function targetFileName($original_name, $additional_data = false)
    {
        // By default, add random number to start of file
        return rand(1111, 9999) . '-' . $original_name;
    }


    /**
     * Function which is executed after file has been moved to local path; used to save DB entries etc.
     *
     * @param string $target_file_path
     * @param string $additional_data
     */
    public static function postMove($target_file_path, $additional_data = [])
    {
        // By default, do nothing
    }


    public static function validateUploadedFiles($files, $source_model = false)
    {
        // Convert uploaded files format
        if ($uploaded_files = static::uploadedFiles($files, true)) {
            // Check if source model supplied
            if ($source_model && class_exists($source_model)) {
                // Check for allowed file types
                if (method_exists($source_model, 'allowed_file_types') && ($allowed_file_types = $source_model::allowedFileTypes())) {
                    foreach ($uploaded_files as $uploaded_file) {
                        // Read file extension
                        $file_extension = static::fileExtension($uploaded_files['name']);

                        // Check for allowed file type
                        if (! in_array($file_extension, $allowed_file_types)) {
                            return [
                                false,
                                'File extension "' . $file_extension . '" is not supported',
                            ];
                        }
                    }
                }
            }

            // Files validated
            return [
                true,
                count($uploaded_files) . ' file(s) validated',
            ];
        }

        // No files to validate
        return [
            true,
            'No files uploaded',
        ];
    }


    public static function fileExtension($filename)
    {
        return substr(strrchr(strtolower($filename), '.'), 1);
    }


    protected static function dateFolder($timestamp = false)
    {
        // Default to now
        if (! $timestamp) {
            $timestamp = time();
        }

        return gmdate('Y', $timestamp) . DS . gmdate('m', $timestamp) . DS . gmdate('d', $timestamp) . DS;
    }
}
