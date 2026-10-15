<?php

declare(strict_types=1);

/**
 * This file is part of Kooditorm/hyperf-basic.
 *
 * @link     https://github.com/Kooditorm/hyperf-basic
 * @contact  oswin.hu@gmail.com
 * @license  https://github.com/Kooditorm/hyperf-basict/blob/master/LICENSE
 */

namespace Kooditorm\Hyperf\Basic\VO;

use Hyperf\Swagger\Annotation as OA;
use Kooditorm\Hyperf\Basic\AbstractBaseVo;

abstract class AbstractPaginateVo extends AbstractBaseVo
{
    #[OA\Property(description: '总数', type: 'int')]
    public int $total;

    #[OA\Property(description: '当前页', type: 'int')]
    public int $page;

    #[OA\Property(description: '每页条数', type: 'int')]
    public int $pre_page;

    #[OA\Property(description: '总页数', type: 'int')]
    public int $last_page;
}