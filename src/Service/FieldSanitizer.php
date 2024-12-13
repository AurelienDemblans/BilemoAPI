<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'never')]
class FieldSanitizer
{
    /**
     * @param mixed $data
     *
     * @return mixed
     */
    public function sanitize(mixed $data): mixed
    {
        if (is_string($data)) {
            return trim(strip_tags($data));
        }

        return $data;
    }
}
