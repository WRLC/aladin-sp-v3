<?php

/** @noinspection PhpUnused */

namespace App\Entity;

/**
 * Class Authz
 */
class Authz
{
    private InstitutionService $institutionService;
    private bool $authorized;

    /** @var array<string> */
    private array $match;
    private bool $errors;

    /**
     * Authz constructor
     *
     * @param InstitutionService $institutionService
     * @param bool $authorized
     * @param array<string> $match
     * @param bool $errors
     */
    public function __construct(InstitutionService $institutionService, bool $authorized = false, array $match = [], bool $errors = false)
    {
        $this->institutionService = $institutionService;
        $this->authorized = $authorized;
        $this->match = $match;
        $this->errors = $errors;
    }

    /**
     * Get the InstitutionService
     *
     * @return InstitutionService
     */
    public function getInstitutionService(): InstitutionService
    {
        return $this->institutionService;
    }

    /**
     * Set the InstitutionService
     *
     * @param InstitutionService $institutionService
     *
     * @return $this
     */
    public function setInstitutionService(InstitutionService $institutionService): Authz
    {
        $this->institutionService = $institutionService;

        return $this;
    }

    /**
     * @return bool
     */
    public function getAuthorized(): bool
    {
        return $this->authorized;
    }

    /**
     * Set the authorized value
     *
     * @param bool $authorized
     *
     * @return $this
     */
    public function setAuthorized(bool $authorized): Authz
    {
        $this->authorized = $authorized;

        return $this;
    }

    /**
     * Get the match
     *
     * @return array<string>
     */
    public function getMatch(): array
    {
        return $this->match;
    }

    /**
     * Set the match
     *
     * @param array<string> $match
     *
     * @return $this
     */
    public function setMatch(array $match): Authz
    {
        $this->match = $match;

        return $this;
    }

    /**
     * Get the errors
     *
     * @return bool
     */
    public function getErrors(): bool
    {
        return $this->errors;
    }

    /**
     * Set the errors
     * @param bool $errors
     *
     * @return $this
     */
    public function setErrors(bool $errors): Authz
    {
        $this->errors = $errors;

        return $this;
    }
}
