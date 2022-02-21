<?php

namespace RlWebdiensten\LaravelViper\Commands;

use Illuminate\Console\Command;
use RlWebdiensten\LaravelViper\LaravelViper;

class ViperRefresh extends Command
{
    private LaravelViper $Service;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'viper:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the viper jwt token';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(LaravelViper $viperService)
    {
        $this->Service = $viperService;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->Service->checkToken();
        return 0;
    }

}
