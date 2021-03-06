<?php
namespace App\Provider;

use App;
use Azura;
use App\Controller\Api;
use Doctrine\ORM\EntityManager;
use Pimple\ServiceProviderInterface;
use Pimple\Container;

class ApiProvider implements ServiceProviderInterface
{
    public function register(Container $di)
    {
        $di[Api\Admin\PermissionsController::class] = function() {
            return new Api\Admin\PermissionsController;
        };

        $di[Api\IndexController::class] = function() {
            return new Api\IndexController;
        };

        $di[Api\InternalController::class] = function($di) {
            return new Api\InternalController(
                $di[App\Acl::class],
                $di[App\Sync\Task\NowPlaying::class],
                $di[App\Radio\AutoDJ::class]
            );
        };

        $di[Api\NowplayingController::class] = function($di) {
            return new Api\NowplayingController(
                $di[EntityManager::class],
                $di[Azura\Cache::class],
                $di[Azura\EventDispatcher::class]
            );
        };

        $di[Api\OpenApiController::class] = function($di) {
            return new Api\OpenApiController(
                $di[Azura\Settings::class],
                $di[App\Version::class]
            );
        };

        $di[Api\Stations\HistoryController::class] = function($di) {
            return new Api\Stations\HistoryController(
                $di[EntityManager::class],
                $di[App\ApiUtilities::class]
            );
        };

        $di[Api\Stations\IndexController::class] = function($di) {
            return new Api\Stations\IndexController(
                $di[EntityManager::class],
                $di[App\Radio\Adapters::class]
            );
        };

        $di[Api\Stations\ListenersController::class] = function($di) {
            return new Api\Stations\ListenersController(
                $di[EntityManager::class],
                $di[Azura\Cache::class],
                $di[\MaxMind\Db\Reader::class]
            );
        };

        $di[Api\Stations\MediaController::class] = function($di) {
            return new Api\Stations\MediaController(
                $di[App\Customization::class],
                $di[App\Radio\Filesystem::class]
            );
        };

        $di[Api\Stations\QueueController::class] = function($di) {
            return new Api\Stations\QueueController(
                $di[EntityManager::class],
                $di[\Symfony\Component\Serializer\Serializer::class],
                $di[\Symfony\Component\Validator\Validator\ValidatorInterface::class],
                $di[App\ApiUtilities::class]
            );
        };

        $di[Api\Stations\RequestsController::class] = function($di) {
            return new Api\Stations\RequestsController(
                $di[EntityManager::class],
                $di[App\ApiUtilities::class]
            );
        };

        $di[Api\Stations\ServicesController::class] = function($di) {
            return new Api\Stations\ServicesController(
                $di[EntityManager::class],
                $di[App\Radio\Configuration::class]
            );
        };

        $standard_crud_controllers = [
            Api\Admin\CustomFieldsController::class,
            Api\Admin\UsersController::class,
            Api\Admin\RolesController::class,
            Api\Admin\SettingsController::class,
            Api\Admin\StationsController::class,
            Api\Stations\MountsController::class,
            Api\Stations\RemotesController::class,
            Api\Stations\StreamersController::class,
        ];

        foreach($standard_crud_controllers as $controller) {
            $di[$controller] = function($di) use ($controller) {
                return new $controller(
                    $di[EntityManager::class],
                    $di[\Symfony\Component\Serializer\Serializer::class],
                    $di[\Symfony\Component\Validator\Validator\ValidatorInterface::class]
                );
            };
        }
    }
}
