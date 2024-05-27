<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'database-backup:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // For First Database
        $database_1 = env('BACKUP_1_DATABASE');
        $host_1 = env('BACKUP_1_HOST');
        $user_1 = env('BACKUP_1_USER');
        $password_1 = env('BACKUP_1_PASSWORD');
        if ($database_1 != null && $user_1 != null) {
            $this->createDatabasebackup($host_1, $database_1, $user_1, $password_1);
        }
  
        // For second Database
        $database_2 = env('BACKUP_2_DATABASE');
        $host_2 = env('BACKUP_2_HOST');
        $user_2 = env('BACKUP_2_USER');
        $password_2 = env('BACKUP_2_PASSWORD');
        if ($database_2 != null && $user_2 != null) {
            $this->createDatabasebackup($host_2, $database_2, $user_2, $password_2);
        }
    }

    /**
     * Function for creating database backup.
     *
     * @var array
     */
    public function createDatabasebackup($host, $database, $user, $password) 
    {
        info("Backup cron job started for database ".$database." at ". now());
        try {
            $filename = "backup-" .$database.'-'.Str::random(4).'-'. Carbon::now()->format('Y-m-d') . ".sql";
            // Create backup folder and set permission if not exist.
            $storageAt = storage_path() . "/db-backups/".Carbon::now()->format('Y').'/';

            if (!File::exists($storageAt)) {
                File::makeDirectory($storageAt, 0755, true, true);
            }
            
            $env = env('APP_ENV');
            if ($env == 'local') {
                $command = sprintf('C:\xampp\mysql\bin\mysqldump --host=%s %s -u %s -p%s> %s', $host, $database, $user, $password, $storageAt.$filename);
            } else {
                $command = sprintf('mysqldump --host=%s %s -u %s -p%s > %s', $host, $database, $user, $password, $storageAt.$filename);
            }
            $returnVar = NULL;
            $output = NULL;
            exec($command, $output, $returnVar);
            
            if ($returnVar == 0) {
                info("Mysqldump process completed for database ".$database." at ". now());
                // Upload backup file to AWS S3 Bucket 
                $backupFilePath = $storageAt . $filename;
                $s3Uploadpath = "/db-backups/".Carbon::now()->format('Y').'/'. $filename;
                if (File::exists($backupFilePath)) {
                    Storage::disk('s3')->put($s3Uploadpath, file_get_contents($backupFilePath), 'public');
                    $urlPath = Storage::disk('s3')->url($s3Uploadpath);
                    if ($urlPath) {
                        info("Backup cron job completed for database ".$database." at ". now());
                    } else {
                        info("Backup cron job encountered an error for database ".$database." at ", now());
                    }
                    unlink($backupFilePath);
                }
            } else {
                info("Mysqldump process encountered a problem for database ".$database." at ". now());
            }
        } catch (Exception $e) {
            info("Error backing up database ".$database.".ERR-MSG=". $e->getMessage());
        }
    } 
}
