<?php

/** @noinspection PhpUnused */

namespace App\Entity;

/**
 * Class Authz
 */
class Authz
{
    /** @var InstitutionService The Institution Service */
    private InstitutionService $institutionService;

   /** @var bool Authorization state */
    private bool $authorized;

    /** @var array<string> Matching authz members */
    private array $match;

    /** @var bool Error state */
    private bool $errors;

    /**
     * Authz constructor.
     *
     * @param InstitutionService $institutionService
     * @param bool $authorized
     * @param array<string> $match
     * @param bool $errors
     *
     * @return void
     */
    public function __construct(InstitutionService $institutionService, bool $authorized = false, array $match = [], bool $errors = false) {
        $this->institutionService = $institutionService;
        $this->authorized = $authorized;
        $this->match = $match;
        $this->errors = $errors;
    }

    /**
     * Get the Institution Service
     *
     * @return InstitutionService
     */
    public function getInstitutionService(): InstitutionService
    {
        return $this->institutionService;
    }

    /**
     * Set the Institution Service
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
     * Get the authorization state
     *
     * @return bool
     */
    public function getAuthorized(): bool
    {
        return $this->authorized;
    }

    /**
     * Set the authorization state
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
     * Get the matching authz members
     *
     * @return array<string>
     */
    public function getMatch(): array
    {
        return $this->match;
    }

    /**
     * Set the matching authz members
     *
     * @param array<string> $match
     * @return $this
     */
    public function setMatch(array $match): Authz
    {
        $this->match = $match;

        return $this;
    }

    /**
     * Get the error state
     *
     * @return bool
     */
    public function getErrors(): bool
    {
        return $this->errors;
    }

    /**
     * Set the error state
     *
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