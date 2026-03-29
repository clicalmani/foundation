<?php
namespace Clicalmani\Foundation\Maker;

class ApplicationBuilder
{
    private $kernels = [
        \Clicalmani\Foundation\Maker\AppKernel::class,
        \Clicalmani\Foundation\Maker\BootstrapKernel::class,
        \Clicalmani\Foundation\Maker\HttpKernel::class,
        \Clicalmani\Foundation\Resources\Kernel::class,
    ];

    public function __construct(private Application $app)
    {
        \Clicalmani\Foundation\Support\Helper::include();
        $this->app->console = new \Clicalmani\Console\Application($this->app);
    }

    public function run()
    {
        return $this->app;
    }

    /**
     * Loads kernels
     * 
     * @return static
     */
    public function withKernels() : static
    {
        \Clicalmani\Foundation\Providers\ServiceProvider::provideServices([
            \Clicalmani\Foundation\Providers\EnvServiceProvider::class
        ]);
        
        foreach ($this->kernels as $kernel) {
            $this->app->addKernel($kernel);
        }
        
        $commands = array_merge($this->app->commands(), $this->app->config['bootstrap']['commands']);
        $this->app->commands($commands);
        
        return $this;
    }

    /**
     * Loads middlewares
     * 
     * @return static
     */
    public function withMiddleware(\Closure $callback) : static
    {
        \Closure::bind($callback, null);
        $callback(new \Clicalmani\Foundation\Http\Middlewares\Web);
        return $this;
    }

    public function withService(\Closure $callback) : static
    {
        \Closure::bind($callback, null);
        $callback($this->app);
        return $this;
    }
}