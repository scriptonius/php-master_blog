<?php
/**
 * Created by PhpStorm.
 * User: igorbulakh
 * Date: 23.11.2017
 * Time: 0:41
 */

namespace NTSchool\Phpblog\Core\providers;

use NTSchool\Phpblog\Core\ServiceContainer;
use NTSchool\Phpblog\Core\User;

class UserProvider implements ProviderInterface
{
    /**
     * @param \NTSchool\Phpblog\Core\ServiceContainer $container
     */
    public function register(ServiceContainer &$container)
    {
        $container->register('service.user', function($request) use ($container){
            return new User(
                $container->get('models', 'Users'),
                $container->get('models', 'Sessions'),
                $request,
                $container->get('http.session'),
                $container->get('models', 'RoleModel')
            );
        });
    }
}