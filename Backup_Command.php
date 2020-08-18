<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
class backup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:go {--path=} {--file=}';

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
     * @return int
     */
    public function handle()
    {
        $tables = $this->getNamesTablesDB();
        $tableIDs = [0];
        $location = $this->option('path')==null?public_path('backups'):public_path($this->option('path'));
        if(!is_dir($location)) mkdir($location);
        $host = env('DB_HOST');
        $port = env('DB_PORT');
        $db = env('DB_DATABASE');
        $user = env('DB_USERNAME');
        $pass = env('DB_PASSWORD');
        $file=$this->option('file')==null? date('Y_m_d_His_') .$db:$this->option('file');
        $sqlpath = $location .'/'.  $file . '.sql';
        $tablenames ='';
        $command = "mysqldump --host={$host} --port={$port} --user={$user} --password={$pass} {$db}{$tablenames} >  {$sqlpath}";
 
        shell_exec($command);

        $line = '-- ' . json_encode([
            'tables' => implode(', ', array_map(function($id) use ($tables){
                return $tables[$id];
            }, $tableIDs)),
           
        ]);
 
        file_put_contents($sqlpath, $line, FILE_APPEND);
        $this->info('backup operation finished');
        return;
    }
    public function handleDeprecated()
    {
        $tables = implode(' ', $this->argument('table'));
        if($tables) $tables = ' ' . $tables;

        $location = base_path('database/backups');
        if(!is_dir($location)) mkdir($location);
        chmod($location, '774');

        $host = env('DB_HOST');
        $port = env('DB_PORT');
        $db = env('DB_DATABASE');
        $user = env('DB_USERNAME');
        $pass = env('DB_PASSWORD');


        $filename = count($this->argument('table')) ?
            implode('__', $this->argument('table')) . '.sql': 'all.sql';

        $sqlpath = $location .'/'. date('Y_m_d_His_') . $filename;

        $command = "mysqldump --host={$host} --port={$port} --user={$user} --password={$pass} {$db}{$tables} >  {$sqlpath}";
        shell_exec($command);

        $this->info('backup operation finished');
    }
    protected function getNamesTablesDB(){

        $database = Config::get('database.connections.mysql.database');
        $tables = DB::select('SHOW TABLES');
        $combine = "Tables_in_".$database;

        $collection = ['* ALL Tables'];

        foreach($tables as $table){
            $collection[] = $table->$combine;
        }

        return $collection;
    }
}
