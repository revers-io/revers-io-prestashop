<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit31cab9a6be78df53c60b2355520e3190
{
    public static $prefixLengthsPsr4 = array (
        'R' => 
        array (
            'ReversIO\\' => 9,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'ReversIO\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'CategoryMap' => __DIR__ . '/../..' . '/src/Entity/CategoryMap.php',
        'ProductForExport' => __DIR__ . '/../..' . '/src/Entity/ProductForExport.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit31cab9a6be78df53c60b2355520e3190::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit31cab9a6be78df53c60b2355520e3190::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit31cab9a6be78df53c60b2355520e3190::$classMap;

        }, null, ClassLoader::class);
    }
}
