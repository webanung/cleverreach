<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit134a44cb9a621cb1351fe8490d5bd2f4
{
    public static $prefixLengthsPsr4 = array (
        'C' => 
        array (
            'CleverReach\\Tests\\GenericTests\\' => 31,
            'CleverReach\\Infrastructure\\' => 27,
            'CleverReach\\BusinessLogic\\' => 26,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'CleverReach\\Tests\\GenericTests\\' => 
        array (
            0 => __DIR__ . '/..' . '/cleverreach/integration-core/generic_tests',
        ),
        'CleverReach\\Infrastructure\\' => 
        array (
            0 => __DIR__ . '/..' . '/cleverreach/integration-core/src/Infrastructure',
        ),
        'CleverReach\\BusinessLogic\\' => 
        array (
            0 => __DIR__ . '/..' . '/cleverreach/integration-core/src/BusinessLogic',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit134a44cb9a621cb1351fe8490d5bd2f4::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit134a44cb9a621cb1351fe8490d5bd2f4::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit134a44cb9a621cb1351fe8490d5bd2f4::$classMap;

        }, null, ClassLoader::class);
    }
}
