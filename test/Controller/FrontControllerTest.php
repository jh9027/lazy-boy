<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\LazyBoy\Test;

use Silktide\LazyBoy\Controller\FrontController;
use Silktide\Syringe\ContainerBuilder;
use Silktide\LazyBoy\Config\RouteLoader;
use Silex\Application;
use Silex\Provider\ServiceControllerServiceProvider;

/**
 *
 */
class FrontControllerTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \Mockery\Mock|ContainerBuilder
     */
    protected $builder;

    /**
     * @var \Mockery\Mock|RouteLoader
     */
    protected $routeLoader;

    /**
     * @var \Mockery\Mock|Application
     */
    protected $application;

    /**
     * @var \Mockery\Mock|ServiceControllerServiceProvider
     */
    protected $serviceProvider;

    public function setUp()
    {
        $this->builder = \Mockery::mock("Silktide\\Syringe\\ContainerBuilder")->shouldIgnoreMissing();
        $this->routeLoader = \Mockery::mock("Silktide\\LazyBoy\\RouteLoader")->shouldIgnoreMissing();
        $this->application = \Mockery::mock("Silex\\Application")->shouldIgnoreMissing();
        $this->serviceProvider = \Mockery::mock("Pimple\\ServiceProviderInterface")->shouldIgnoreMissing();

    }

    public function testSettingApplicationClass()
    {
        // default class
        $class = FrontController::DEFAULT_APPLICATION_CLASS;
        $controller = new FrontController($this->builder, "", $class);
        $this->assertAttributeEquals($class, "applicationClass", $controller);

        // subclass
        $class = get_class($this->application);
        $controller = new FrontController($this->builder, "", $class);
        $this->assertAttributeEquals($class, "applicationClass", $controller);

        // invalid class
        try {
            $controller = new FrontController($this->builder, "", __CLASS__);
            $this->fail("Should not be able to create a FrontController with an invalid application class");
        } catch (\InvalidArgumentException $e) {
        }

    }

    public function testApplicationRun()
    {
        $appClass = FrontController::DEFAULT_APPLICATION_CLASS;
        $configDir = "configDir";

        $this->builder->shouldReceive("setContainerClass")->with($appClass)->once();
        $this->builder->shouldReceive("createContainer")->once()->andReturn($this->application);

        $this->application->shouldReceive("offsetSet")->with("app", \Mockery::type("callable"))->once();
        $this->application->shouldReceive("offsetGet")->with("routeLoader")->once()->andReturn($this->routeLoader);
        $this->application->shouldReceive("run")->once();

        $this->routeLoader->shouldReceive("parseRoutes")->with("/^$configDir/")->once();

        $controller = new FrontController($this->builder, $configDir, $appClass);
        $controller->runApplication();
    }

    public function testSettingProviders()
    {
        $providers = [
            $this->serviceProvider,
            $this->serviceProvider,
            $this->serviceProvider,
            [$this->serviceProvider, ["myValue" => "value"]]
        ];

        $this->builder->shouldReceive("createContainer")->once()->andReturn($this->application);
        $this->application->shouldReceive("offsetGet")->with("routeLoader")->once()->andReturn($this->routeLoader);
        $this->application->shouldReceive("register")->with($this->serviceProvider, [])->times(3);
        $this->application->shouldReceive("register")->with($this->serviceProvider, ["myValue" => "value"])->once();

        $controller = new FrontController($this->builder, "configDir", FrontController::DEFAULT_APPLICATION_CLASS, $providers);
        $controller->runApplication();

    }

    public function tearDown()
    {
        \Mockery::close();
    }

}
 