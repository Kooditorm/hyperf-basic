<?php
declare(strict_types=1);

/**
 * This file is part of Kooditorm/hyperf-basic.
 *
 * @link     https://github.com/Kooditorm/hyperf-basic
 * @contact  oswin.hu@gmail.com
 * @license  https://github.com/Kooditorm/hyperf-basic/blob/master/LICENSE
 */

namespace Kooditorm\Hyperf\Basic\Entities;

use Kooditorm\Hyperf\Auth\Authenticatable;
use Kooditorm\Hyperf\Auth\Contracts\AuthenticatableInterface;
use Kooditorm\Hyperf\Basic\AbstractBaseEntity;
use Kooditorm\Hyperf\Jwt\Contracts\JwtSubjectInterface;

abstract class AuthEntity extends AbstractBaseEntity implements JwtSubjectInterface,AuthenticatableInterface
{
    use Authenticatable;

    public function getJwtId(): string
    {
        return $this->getKey();
    }

    /**
     * 签发 JWT 时的 subject 标识（通常为主键）
     */
    public function getJwtIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * 签发 JWT 时附加的自定义 claim
     */
    public function getJwtCustomClaims(): array
    {
        return [];
    }

    public function getCheckAuthPassword():bool
    {
        return false;
    }
}