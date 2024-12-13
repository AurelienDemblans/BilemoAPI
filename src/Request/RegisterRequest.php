<?php

namespace App\Request;

use App\Request\AbstractRequest;
use Exception;

class RegisterRequest extends AbstractRequest
{
    /**
     * isValid
     *
     * @return void
     */
    public function isValid()
    {
        if (!preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $this->getEmail())) {
            throw new \Exception('Format d\'email invalide');
        }

        foreach ($this->getMandatoryFields() as $fieldName) {
            if (!$this->requestHas($fieldName)) {
                throw new Exception('Le champs '.$fieldName. ' est obligatoire');

                $this->checkStringParameter($this->getInRequest($fieldName), $fieldName);
            }
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
            throw new Exception($parameterName. ' invalide : le champs ne doit être une string');
        }

        $parameterValue = trim($parameterValue);

        if (empty($parameterValue) || $parameterValue === '') {
            throw new Exception($parameterName. ' invalide : le champs ne doit pas être vide');
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
        return $this->getInRequest('client');
    }

}
