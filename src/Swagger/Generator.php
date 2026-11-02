<?php

declare(strict_types=1);

/**
 * This file is part of Kooditorm/hyperf-basic.
 *
 * @link     https://github.com/Kooditorm/hyperf-basic
 * @contact  oswin.hu@gmail.com
 * @license  https://github.com/Kooditorm/hyperf-basict/blob/master/LICENSE
 */

namespace Kooditorm\Hyperf\Basic\Swagger;

use Hyperf\Swagger\Generator as HyperfGenerator;

class Generator extends HyperfGenerator
{
    public function generate(): void
    {
        print_r('basic generator');
        parent::generate();
    }
}