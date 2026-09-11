<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class RestoreDatabase extends Command
{
    protected $signature = 'db:restore {file : نام فایل بک‌آپ در storage/app/backups} {--force : بدون تأیید تعاملی}';

    protected $description = 'بازیابی دیتابیس از فایل بک‌آپ';

    public function handle(): int
    {
        $name = basename((string) $this->argument('file'));
        if (! preg_match('/^backup_[\w\-\.]+\.(sqlite|sql)$/', $name)) {
            $this->error('نام فایل بک‌آپ نامعتبر است.');

            return self::FAILURE;
        }

        $path = storage_path('app/backups'.DIRECTORY_SEPARATOR.$name);
        if (! File::exists($path)) {
            $this->error('فایل پیدا نشد: '.$name);

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('این کار دیتابیس فعلی را جایگزین می‌کند. ادامه؟', false)) {
            $this->warn('لغو شد.');

            return self::FAILURE;
        }

        // Safety snapshot of current DB before restore
        $this->call('db:backup', ['--keep' => 20]);

        $connection = config('database.default');
        $config = config("database.connections.{$connection}");
        $driver = $config['driver'] ?? '';

        if ($driver === 'sqlite') {
            if (! str_ends_with($name, '.sqlite')) {
                $this->error('برای SQLite فقط فایل .sqlite مجاز است.');

                return self::FAILURE;
            }

            $database = $config['database'] ?? database_path('database.sqlite');
            DB::purge($connection);
            File::copy($path, $database);
            $this->info('SQLite بازیابی شد.');

            return self::SUCCESS;
        }

        if ($driver === 'mysql') {
            if (! str_ends_with($name, '.sql')) {
                $this->error('برای MySQL فقط فایل .sql مجاز است.');

                return self::FAILURE;
            }

            if (! $this->tryMysqlImport($config, $path)) {
                $this->error('بازیابی MySQL ناموفق بود. mysqldump/mysql را بررسی کنید یا دستی import کنید.');

                return self::FAILURE;
            }

            $this->info('MySQL بازیابی شد.');

            return self::SUCCESS;
        }

        $this->error('درایور پشتیبانی نمی‌شود.');

        return self::FAILURE;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function tryMysqlImport(array $config, string $path): bool
    {
        $binary = $this->resolveMysqlBinary();
        if (! $binary) {
            return $this->phpMysqlImport($path);
        }

        $process = Process::fromShellCommandline(
            sprintf(
                '"%s" -h %s -P %s -u %s --password=%s %s < %s',
                $binary,
                escapeshellarg((string) ($config['host'] ?? '127.0.0.1')),
                escapeshellarg((string) ($config['port'] ?? 3306)),
                escapeshellarg((string) ($config['username'] ?? 'root')),
                escapeshellarg((string) ($config['password'] ?? '')),
                escapeshellarg((string) $config['database']),
                escapeshellarg($path)
            )
        );
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->warn($process->getErrorOutput());

            return $this->phpMysqlImport($path);
        }

        return true;
    }

    private function resolveMysqlBinary(): ?string
    {
        $candidates = [
            'mysql',
            'C:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysql.exe',
            'C:\\xampp\\mysql\\bin\\mysql.exe',
        ];

        foreach (glob('C:\\laragon\\bin\\mysql\\mysql-*\\bin\\mysql.exe') ?: [] as $path) {
            $candidates[] = $path;
        }

        foreach ($candidates as $candidate) {
            if ($candidate === 'mysql') {
                $process = Process::fromShellCommandline('where mysql');
                $process->run();
                if ($process->isSuccessful() && trim($process->getOutput()) !== '') {
                    return 'mysql';
                }

                continue;
            }

            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function phpMysqlImport(string $path): bool
    {
        $sql = File::get($path);
        if ($sql === '') {
            return false;
        }

        try {
            DB::unprepared('SET FOREIGN_KEY_CHECKS=0;');
            foreach ($this->splitSqlStatements($sql) as $statement) {
                if (trim($statement) === '') {
                    continue;
                }
                DB::unprepared($statement);
            }
            DB::unprepared('SET FOREIGN_KEY_CHECKS=1;');

            return true;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return false;
        }
    }

    /**
     * @return list<string>
     */
    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        foreach (preg_split("/\r\n|\n|\r/", $sql) as $line) {
            $trim = ltrim($line);
            if ($trim === '' || str_starts_with($trim, '--')) {
                continue;
            }
            $buffer .= $line."\n";
            if (str_ends_with(rtrim($line), ';')) {
                $statements[] = $buffer;
                $buffer = '';
            }
        }
        if (trim($buffer) !== '') {
            $statements[] = $buffer;
        }

        return $statements;
    }
}
