<?php

namespace Hassansin\DBCart;

use Hassansin\DBCart\Console\Commands\CartCleanup;
use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class CartServiceProvider extends ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerScheduler();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['cart_instances'] =  [] ;

        $this->app->bind('cart', function($app, $params){
            $instance_name = !empty($params['name']) ? $params['name'] : 'default';
            $cart_instances = $app['cart_instances'];

            //implement singleton carts
            if(empty($cart_instances[$instance_name])){
                $model = config('cart.cart_model');
                $cart_instances[$instance_name] = $model::current($instance_name);
                $app['cart_instances'] = $cart_instances;
            }
            return $app['cart_instances'][$instance_name];
        });

        $this->publishes([
            __DIR__.'/config/cart.php' => config_path('cart.php'),
        ],'config');

        $this->publishes([
            __DIR__.'/database/migrations/' => database_path('migrations')
        ], 'migrations');

        $this->mergeConfigFrom(
            __DIR__.'/config/cart.php', 'cart'
        );

        $this->commands([
            CartCleanup::class
        ]);
    }

    /*
     * @codeCoverageIgnore
     */
    protected function registerScheduler(){
        $this->app->booted(function() {
            $schedule = $this->app->make(Schedule::class);
            $schedule_frequency = config('cart.schedule_frequency', 'hourly');
            $schedule->command(CartCleanup::class)->$schedule_frequency();
        });
    }


    /**
     * Get the services provided by the provider.
     *
     * @codeCoverageIgnore
     * @return array
     */
    public function provides()
    {
        return ['cart', 'cart_instances'];
    }
}
