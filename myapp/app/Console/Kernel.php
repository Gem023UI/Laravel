<?php

namespace App\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Application;

class CommandHandler extends Command
{
    /**
     * The Laravel application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Create a new command handler instance.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    public function __construct(Application $app)
    {
        parent::__construct();
        $this->app = $app;
    }

    /**
     * Handle the command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            // Your command logic here
            return $this->info('Command executed successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}

// Alternative approach using a service provider
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CommandServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('command.handler', function ($app) {
            return new \App\Console\CommandHandler($app);
        });
    }
}