<?php
namespace Clicalmani\Foundation\Maker;

class ApplicationBuilder
{
    private $kernels = [
        \Clicalmani\Foundation\Maker\AppKernel::class,
        \Clicalmani\Foundation\Maker\BootstrapKernel::class,
        \Clicalmani\Foundation\Maker\HttpKernel::class,
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
        foreach ($this->kernels as $kernel) {
            $this->app->addKernel($kernel);
        }

        return $this;
    }
}
