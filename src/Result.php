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

use Hyperf\Contract\Arrayable;
use OpenApi\Attributes as OA;

class Result implements Arrayable
{
    public function __construct(
        #[OA\Property(ref: 'ResultCode', title: '响应码')]
        public ResultCode $code = ResultCode::SUCCESS,
        #[OA\Property(title: '响应消息', type: 'string')]
        public ?string $message = null,
        #[OA\Property(title: '响应数据', type: 'array')]
        public mixed $data = []
    ){
        if ($this->message === null) {
            $this->message = ResultCode::getMessage($this->code->value);
        }
    }
    public function toArray(): array
    {
        $data = $this->data;

        if ($this->data instanceof AbstractBaseVo) {
            $data = $this->data->toArray();
        }

        return [
            'code' => $this->code->value,
            'message' => $this->message,
            'data' => $data,
        ];
    }
}