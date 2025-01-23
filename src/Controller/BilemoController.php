<?php

namespace App\Controller;

use App\Entity\User;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class BilemoController extends AbstractController
{
    /**
     * @param  User[] $userList
     *
     * @return array
     */
    protected function cleanUserListByRole(array $userList): array
    {
        return array_filter($userList, function (User $user) {
            return $this->validateRoleAuthorization($user);
        });
    }

    /**
     * @param  int|string $queryParamName
     * @param  int|string $value
     *
     * @return void
     */
    protected function checkQueryParameter(int|string $queryParamName, int|string $value): void
    {
        if (!is_numeric($value) || $value <= 0 || !ctype_digit((string)$value)) {
            throw new Exception($queryParamName.' parameter must be positive integer only, '.$queryParamName.' was : '.$value, Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @param  User $user
     *
     * @return bool
     */
    protected function validateRoleAuthorization(User $user): bool
    {
        foreach ($user->getRoles() as $role) {
            if (!$this->isGranted($role)) {
                return false;
            }
        }
        return true;
    }
}
