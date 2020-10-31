<?php

namespace Lucid\Generators;

use Exception;
use Lucid\Entities\Service;
use Lucid\Str;

class ServiceGenerator extends Generator
{
    /**
     * The directories to be created under the service directory.
     *
     * @var array
     */
    protected $directories = [
        'Console/',
        'database/',
        'database/factories/',
        'database/migrations/',
        'database/seeds/',
        'Http/',
        'Http/Controllers/',
        'Http/Middleware/',
        'Http/Requests/',
        'Providers/',
        'Features/',
        'resources/',
        'resources/lang/',
        'resources/views/',
        'routes',
        'Tests/',
        'Tests/Features/',
    ];


    public function generate($name)
    {
        $name = Str::service($name);
        $slug = Str::snake($name);
        $path = $this->findServicePath($name);

        if ($this->exists($path)) {
            throw new Exception('Service already exists!');

            return false;
        }

        // create service directory
        $this->createDirectory($path);
        // create .gitkeep file in it
        $this->createFile($path.'/.gitkeep');

        $this->createServiceDirectories($path);

        $this->addServiceProviders($name, $slug, $path);

        $this->addRoutesFiles($name, $slug, $path);

        $this->addWelcomeViewFile($path);

        $this->addModelFactory($path);

        return new Service(
            $name,
            $slug,
            $path,
            $this->relativeFromReal($path)
        );
    }

    /**
     * Create the default directories at the given service path.
     *
     * @param  string $path
     *
     * @return void
     */
    public function createServiceDirectories($path)
    {
        foreach ($this->directories as $directory) {
            $this->createDirectory($path.'/'.$directory);
            $this->createFile($path.'/'.$directory.'/.gitkeep');
        }
    }

    /**
     * Add the corresponding service provider for the created service.
     *
     * @param string $name
     * @param string $path
     *
     * @return bool
     */
    public function addServiceProviders($name, $slug, $path)
    {
        $namespace = $this->findServiceNamespace($name).'\\Providers';

        $this->createRegistrationServiceProvider($name, $path, $slug, $namespace);

        $this->createRouteServiceProvider($name, $path, $slug, $namespace);
    }

    /**
     * Create the service provider that registers this service.
     *
     * @param  string $name
     * @param  string $path
     */
    public function createRegistrationServiceProvider($name, $path, $slug, $namespace)
    {
        $content = file_get_contents(__DIR__ . '/stubs/serviceprovider.stub');
        $content = str_replace(
            ['{{name}}', '{{slug}}', '{{namespace}}'],
            [$name, $slug, $namespace],
            $content
        );

        $this->createFile($path.'/Providers/'.$name.'ServiceProvider.php', $content);
    }

    /**
     * Create the routes service provider file.
     *
     * @param  string $name
     * @param  string $path
     * @param  string $slug
     * @param  string $namespace
     */
    public function createRouteServiceProvider($name, $path, $slug, $namespace)
    {
        $serviceNamespace = $this->findServiceNamespace($name);
        $controllers = $serviceNamespace.'\Http\Controllers';
        $foundation = $this->findUnitNamespace();

        $content = file_get_contents(__DIR__ . '/stubs/routeserviceprovider.stub');
        $content = str_replace(
            ['{{name}}', '{{namespace}}', '{{controllers_namespace}}', '{{unit_namespace}}'],
            [$name, $namespace, $controllers, $foundation],
            $content
        );

        $this->createFile($path.'/Providers/RouteServiceProvider.php', $content);
    }

     /**
     * Add the routes files.
     *
     * @param string $name
     * @param string $slug
     * @param string $path
     */
    public function addRoutesFiles($name, $slug, $path)
    {
        $controllers = 'src/Services/' . $name . '/Http/Controllers';

        $contentApi = file_get_contents(__DIR__ . '/stubs/routes-api.stub');
        $contentApi = str_replace(['{{slug}}', '{{controllers_path}}'], [$slug, $controllers], $contentApi);

        $contentWeb = file_get_contents(__DIR__ . '/stubs/routes-web.stub');
        $contentWeb = str_replace(['{{slug}}', '{{controllers_path}}'], [$slug, $controllers], $contentWeb);

        $this->createFile($path . '/routes/api.php', $contentApi);
        $this->createFile($path . '/routes/web.php', $contentWeb);

        unset($contentApi, $contentWeb);
    }

    /**
     * Add the welcome view file.
     *
     * @param string $path
     */
    public function addWelcomeViewFile($path)
    {
        $this->createFile(
            $path.'/resources/views/welcome.blade.php',
            file_get_contents(__DIR__ . '/stubs/welcome.blade.stub')
        );
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/service.stub';
    }

    /**
     * Add the ModelFactory file.
     *
     * @param string $path
     */
    public function addModelFactory($path)
    {
        $modelFactory = file_get_contents(__DIR__ . '/stubs/model-factory.stub');
        $this->createFile($path . '/database/factories/ModelFactory.php', $modelFactory);

        unset($modelFactory);
    }
}
