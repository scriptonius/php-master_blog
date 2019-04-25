<?php
/**
 * Created by PhpStorm.
 * User: igorbulakh
 * Date: 29.09.17
 * Time: 3:19
 */

namespace NTSchool\Phpblog\Core\Traits;


trait Singleton
{
    /**
     * @var
     */
    protected static $singleton_instance;

    /**
     * @return mixed
     */
    public static function instance()
    {
        if(static::$singleton_instance === null) {
            static::$singleton_instance = new static();
        }
        return static::$singleton_instance;
    }

    /**
     * Singleton constructor.
     */
    protected function __construct()
    {
    }

    /**
     *
     */
    protected function __clone()
    {
    }

    /**
     *
     */
    protected function __sleep()
    {
    }

    /**
     *
     */
    protected function __wakeup()
    {
    }
}