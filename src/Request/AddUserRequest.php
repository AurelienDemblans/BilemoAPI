<?php

namespace App\Request;

use App\Entity\Client;
use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use App\Request\AbstractRequest;
use Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class AddUserRequest extends AbstractRequest
{
    private ?Client $client = null;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ClientRepository $clientRepository,
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
        foreach ($this->getMandatoryFields() as $fieldName) {
            if (!$this->requestHas($fieldName)) {
                throw new Exception('Le champs '.$fieldName. ' est obligatoire', Response::HTTP_BAD_REQUEST);

                $this->checkStringParameter($this->getInRequest($fieldName), $fieldName);
            }
        }

        if (!preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $this->getEmail())) {
            throw new \Exception('Format d\'email invalide', Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * isAllowed
     *
     * @return void
     */
    public function isAllowed()
    {
        if ($this->getClient() !== $this->getUser()->getClient() && !$this->userIs('ROLE_SUPER_ADMIN')) {
            throw new Exception('You are not allowed to perform this request : invalid client', Response::HTTP_BAD_REQUEST);
        }

        if ($this->userRepository->findOneByEmail($this->getEmail()) !== null) {
            throw new Exception('Error : email already in use by an other user', Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * getMandatoryFields
     *
     * @return array
     */
    private function getMandatoryFields()
    {
        return [
            'firstname', 'lastname', 'password', 'client', 'email'
        ];
    }

    /**
     * checkStringParameter
     *
     * @param  mixed $parameterValue
     * @param  string $parameterName
     *
     * @return void
     */
    private function checkStringParameter($parameterValue, $parameterName)
    {
        if (!is_string($parameterValue)) {
            throw new Exception($parameterName. ' invalide : le champs ne doit être une string', Response::HTTP_BAD_REQUEST);
        }

        $parameterValue = trim($parameterValue);

        if (empty($parameterValue) || $parameterValue === '') {
            throw new Exception($parameterName. ' invalide : le champs ne doit pas être vide', Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * getEmail
     *
     * @return mixed
     */
    public function getEmail()
    {
        return $this->getInRequest('email');
    }

    /**
     * getFirstName
     *
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->getInRequest('firstname');
    }

    /**
     * getLastName
     *
     * @return mixed
     */
    public function getLastName()
    {
        return $this->getInRequest('lastname');
    }

    /**
     * getPassword
     *
     * @return mixed
     */
    public function getPassword()
    {
        return $this->getInRequest('password');
    }

    /**
     * getClient
     *
     * @return mixed
     */
    public function getClient()
    {
        if (!$this->client) {
            return  $this->client = $this->clientRepository->findOneByUsername(mb_strtolower($this->getInRequest('client')))
            ?? throw new Exception('client not found', Response::HTTP_NOT_FOUND);
        }

        return $this->client;
    }

}
