<?php

namespace RlWebdiensten\LaravelViper\Commands;

use Illuminate\Console\Command;
use RlWebdiensten\LaravelViper\LaravelViper;

class ViperLogin extends Command
{
    private LaravelViper $service;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'viper:login';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Login to the viper365 system';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(LaravelViper $viperService)
    {
        $this->service = $viperService;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $username = strval($this->ask("Please type the username of the viper365 account"));
        $password = strval($this->secret('Please type the password'));

        $result = $this->service->authenticateUser($username, $password);
        if ($result) {
            $this->info("Successfully logged in to Viper365 as $username!");
        } else {
            $this->error("Could not login to Viper365, please try again!");
        }

        return 1;
    }
}
