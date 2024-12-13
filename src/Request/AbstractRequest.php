<?php

declare(strict_types=1);

namespace App\Request;

use App\Entity\User;
use App\Security\Right\RightChecker;
use App\Security\Right\SecurityContext;
use App\Service\FieldSanitizer;
use InvalidArgumentException;
use JsonException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use const JSON_THROW_ON_ERROR;

abstract class AbstractRequest
{
    private bool $isPrepared = false;

    private readonly FieldSanitizer $sanitizer;

    /**
     * @param RequestStack $requestStack
     * @param RightChecker $rightChecker
     * @param Security     $security
     */
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Security $security,
    ) {
        $this->sanitizer = new FieldSanitizer();
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new InvalidArgumentException('No user connected');
        }

        return $user;
    }

    // /**
    //  * @return SecurityContext
    //  */
    // abstract public function getContext(): SecurityContext;

    /**
     * @param string $key
     *
     * @return bool
     *
     * @throws JsonException
     */
    protected function getBooleanInRequest(string $key): bool
    {
        return $this->getRequest()->getBoolean($key);
    }

    /**
     * @param string $key
     *
     * @return mixed
     *
     */
    protected function getInPath(string $key): mixed
    {
        return $this->getRouteParams()[$key] ?? null;
    }

    /**
     * @param string      $key
     * @param string|null $type
     *
     * @return mixed
     *
     * @throws JsonException
     */
    protected function getInRequest(string $key, ?string $type = null): mixed
    {
        try {
            $data = match ($type) {
                'bool' => $this->getRequest()->getBoolean($key),
                'int' => $this->getRequest()->getInt($key),
                default => $this->getRequest()->get($key),
            };
        } catch (BadRequestException) {
            throw new BadRequestException(sprintf('%s should be an integer, a float, a string or a boolean.', $key));
        }

        return $this->sanitizer->sanitize($data);
    }

    /**
     * @param string      $key
     * @param string|null $type
     *
     * @return mixed
     *
     */
    protected function getInQuery(string $key, ?string $type = null): mixed
    {
        return match ($type) {
            'bool' => $this->getQuery()->getBoolean($key),
            default => $this->getQuery()->get($key),
        };
    }

    /**
     * @param string $role
     *
     * @return bool
     */
    protected function userIs(string $role): bool
    {
        return $this->security->isGranted($role);
    }

    /**
     * @return InputBag
     *
     * @throws JsonException
     */
    protected function getRequest(): InputBag
    {
        $this->prepare();

        return $this->getHttpRequest()->request;
    }

    /**
     * @return Request
     *
     */
    protected function getHttpRequest(): Request
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestException();
        }

        return $request;
    }

    /**
     * @throws JsonException
     */
    protected function prepare(): void
    {
        if ($this->isPrepared) {
            return;
        }

        $this->isPrepared = true;

        if (empty($this->getHttpRequest()->getContent())) {
            return;
        }

        $this->getHttpRequest()
            ->request
            ->replace(
                json_decode(
                    $this->getHttpRequest()->getContent(),
                    true,
                    flags: JSON_THROW_ON_ERROR
                )
            )
        ;
    }

    /**
     * @param string $key
     *
     * @return bool
     *
     * @throws JsonException
     */
    protected function requestHas(string $key): bool
    {
        return $this->getRequest()->has($key);
    }

    /**
     * @param string $key
     *
     * @return array
     *
     * @throws BadRequestException
     * @throws JsonException
     */
    protected function getAll(?string $key = null): array
    {
        try {
            return $this->getRequest()->all($key) ?? [];
        } catch (BadRequestException) {
            throw new BadRequestException(sprintf('%s should be an array or an object.', $key));
        }
    }

    /**
     * @return ParameterBag
     *
     */
    protected function getAttributes(): ParameterBag
    {
        return $this->getHttpRequest()->attributes;
    }

    /**
     * @return HeaderBag
     *
     */
    protected function getHeaders(): HeaderBag
    {
        return $this->getHttpRequest()->headers;
    }

    /**
     * @return array
     *
     */
    protected function getRouteParams(): array
    {
        return $this->getAttributes()->get('_route_params', []);
    }

    /**
     * @return InputBag
     *
     */
    protected function getQuery(): InputBag
    {
        return $this->getHttpRequest()->query;
    }

    // /**
    //  * @return RightChecker
    //  */
    // protected function getRightChecker(): RightChecker
    // {
    //     return $this->rightChecker;
    // }

    /**
     * @param string $key
     *
     * @return bool
     *
     */
    protected function queryHas(string $key): bool
    {
        return $this->getQuery()->has($key);
    }

    /**
     * @return FileBag
     *
     */
    protected function getHttpFiles(): FileBag
    {
        return $this->getHttpRequest()->files;
    }

    /**
     * @param string $file
     *
     * @return UploadedFile
     *
     */
    protected function getInFile(string $file): ?UploadedFile
    {
        return $this->files()->get($file);
    }

    /**
     * @return FileBag
     *
     */
    protected function files(): FileBag
    {
        return $this->getHttpRequest()->files;
    }

    /**
     * @param string $key
     *
     * @return bool
     *
     */
    protected function filesHas(string $key): bool
    {
        return $this->files()->has($key);
    }
}
