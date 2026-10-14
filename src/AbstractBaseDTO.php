<?php
declare(strict_types=1);

/**
 * This file is part of Kooditorm/hyperf-basic.
 *
 * @link     https://github.com/Kooditorm/hyperf-basic
 * @contact  oswin.hu@gmail.com
 * @license  https://github.com/Kooditorm/hyperf-basict/blob/master/LICENSE
 */


namespace Kooditorm\Hyperf\Basic;

use ArrayAccess;
use Hyperf\Contract\Arrayable;
use Hyperf\Contract\Jsonable;
use Hyperf\HttpServer\Contract\RequestInterface;
use JsonSerializable;
use Kooditorm\Hyperf\Basic\Traits\HasAttributes;

abstract class AbstractBaseDTO implements Jsonable, Arrayable, ArrayAccess, JsonSerializable
{
    use HasAttributes;

    public function __construct(protected RequestInterface $request)
    {
        $this->initProperties();
    }

    /**
     * DTO 的数据来源：当前请求入参
     */
    protected function source(): array
    {
        return $this->request->all();
    }
}