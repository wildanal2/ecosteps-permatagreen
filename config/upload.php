<?php

return [
    /*
    |--------------------------------------------------------------------------
    | File Upload Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for file uploads including validation rules and limits
    |
    */

    'max_file_size' => 15360, // 15MB in KB
    'allowed_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/jpg', 
        'image/gif',
        'image/webp'
    ],
    'allowed_extensions' => ['jpeg', 'png', 'jpg', 'gif', 'webp'],
    
    'validation_messages' => [
        'required' => 'File wajib diunggah.',
        'file' => 'File yang diunggah tidak valid.',
        'image' => 'File harus berupa gambar.',
        'mimes' => 'Format file harus JPEG, PNG, JPG, GIF, atau WebP.',
        'max' => 'Ukuran file maksimal 15MB.',
    ],
    
    'upload_errors' => [
        UPLOAD_ERR_INI_SIZE => 'File terlalu besar (melebihi batas server PHP).',
        UPLOAD_ERR_FORM_SIZE => 'File terlalu besar (melebihi batas form HTML).',
        UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian, coba lagi.',
        UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diupload.',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary server tidak tersedia.',
        UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke server.',
        UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh konfigurasi server.'
    ]
];