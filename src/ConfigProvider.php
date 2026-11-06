<?php

declare(strict_types=1);

/**
 * This file is part of Kooditorm/hyperf-basic.
 *
 * @link     https://github.com/Kooditorm/hyperf-basic
 * @contact  oswin.hu@gmail.com
 * @license  https://github.com/Kooditorm/hyperf-basic/blob/master/LICENSE
 */

namespace Kooditorm\Hyperf\Basic;

use Hyperf\Swagger\Generator as HyperfGenerator;
use Kooditorm\Hyperf\Basic\Swagger\Generator;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                HyperfGenerator::class => Generator::class
            ],
            'commands' => [],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__
                    ]
                ]
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config of swagger.',
                    'source' => __DIR__ . '/../publish/swagger.php',
                    'destination' => BASE_PATH . '/config/autoload/swagger.php'
                ]
            ]
        ];
    }
}