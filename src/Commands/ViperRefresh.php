<?php

namespace RlWebdiensten\LaravelViper\Commands;

use Illuminate\Console\Command;
use RlWebdiensten\LaravelViper\LaravelViper;

class ViperRefresh extends Command
{
    private LaravelViper $service;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'viper:refresh
                            {--force : Force refresh of token}';

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
        parent::__construct();
        $this->service = $viperService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $force = $this->option('force');
        if ($force) {
            $result = $this->service->refreshToken();
            if ($result) {
                $this->info("Successfully refreshed Viper365 token!");
            } else {
                $this->error("Could not refresh Viper365 token, please login!");
            }

            return 0;
        }

        $this->service->checkToken();

        return 0;
    }
}
