<?php namespace Efice\Image;

use Illuminate\Support\ServiceProvider;

class ImageServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        // Config file path
        $configFile = __DIR__ . '/../../resources/config/image.php';

        // Merge files
        $this->mergeConfigFrom($configFile, 'image');

        // Publish
        $this->publishes([
            $configFile => config_path('image.php')
        ], 'config');
        
        $app = $this->app;
        $router = $app['router'];
        $config = $app['config'];
        
        $pattern = $app['image']->pattern();
        $proxyPattern = $config->get('image.proxy_route_pattern');
        $router->pattern('image_pattern', $pattern);
        $router->pattern('image_proxy_pattern', $proxyPattern ? $proxyPattern:$pattern);

        //Serve image
        $serve = config('image.serve');
        if ($serve) {
            // Create a route that match pattern
            $serveRoute = $config->get('image.serve_route', '{image_pattern}');
            $router->get($serveRoute, array(
                'as' => 'image.serve',
                'domain' => $config->get('image.domain', null),
                'uses' => 'Efice\Image\ImageController@serve'
            ));
        }
        
        //Proxy
        $proxy = $this->app['config']['image.proxy'];
        if ($proxy) {
            $serveRoute = $config->get('image.proxy_route', '{image_proxy_pattern}');
            $router->get($serveRoute, array(
                'as' => 'image.proxy',
                'domain' => $config->get('image.proxy_domain'),
                'uses' => 'Efice\Image\ImageController@proxy'
            ));
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('image', function ($app) {
            return new ImageManager($app);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('image');
    }
}
