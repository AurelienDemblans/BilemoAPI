<?php

namespace App\Request;

use Exception;
use App\Entity\User;
use App\Request\AbstractRequest;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;

class RemoveUserRequest extends AbstractRequest
{
    protected ?User $userToRemove = null;

    public function __construct(
        private readonly UserRepository $userRepository,
        RequestStack $requestStack,
        Security $security
    ) {
        parent::__construct($requestStack, $security);
    }


    /**
     * isValid
     *
     * @return void
     */
    public function isValid()
    {
        if (!$this->requestHas('user')) {
            throw new Exception('Invalid request, user parameter missing in the body', Response::HTTP_BAD_REQUEST);
        }

        $this->getUserToRemove();
    }

    /**
     * isValid
     *
     * @return void
     */
    public function isAllowed()
    {
        if (!$this->userToRemove) {
            $this->getUserToRemove();
        }

        if ($this->userToRemove->getClient() !== $this->getUser()->getClient()) {
            throw new Exception('You are not allowed to perform this request : invalid client', Response::HTTP_BAD_REQUEST);

        }

        if ($this->getUser() === $this->userToRemove) {
            throw new Exception('Invalid request, you cannot delete yourself', Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * getUserToRemove
     *
     * @return mixed
     */
    public function getUserToRemove()
    {
        if (!$this->userToRemove) {
            return $this->userToRemove = $this->userRepository->find($this->getInRequest('user')) ?? throw new Exception('User not found', Response::HTTP_NOT_FOUND);
        }

        return $this->userToRemove;
    }
}
