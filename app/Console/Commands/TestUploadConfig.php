<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\UploadDebugger;

class TestUploadConfig extends Command
{
    protected $signature = 'upload:test-config';
    protected $description = 'Test upload configuration and display potential issues';

    public function handle()
    {
        $this->info('Testing Upload Configuration...');
        $this->newLine();

        // Debug environment
        $env = UploadDebugger::debugEnvironment();
        
        $this->table(
            ['Setting', 'Value'],
            [
                ['PHP Version', $env['php_version']],
                ['File Uploads', $env['file_uploads']],
                ['Upload Max Filesize', $env['upload_max_filesize']],
                ['Post Max Size', $env['post_max_size']],
                ['Max Execution Time', $env['max_execution_time']],
                ['Max Input Time', $env['max_input_time']],
                ['Memory Limit', $env['memory_limit']],
                ['Upload Tmp Dir', $env['upload_tmp_dir']],
                ['Max File Uploads', $env['max_file_uploads']],
            ]
        );

        return 0;
    }
}