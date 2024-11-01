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
     * @param array<string> $match
     */
    public function __construct(InstitutionService $institutionService, array $match = [])
    {
        $this->institutionService = $institutionService;
        $this->match = $match;
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
    public function isAuthorized(): bool
    {
        if ($this->authorized) {
            return true;
        }
        return false;
    }

    /**
     * Set the authorized value
     *
     * @return $this
     */
    public function setAuthorized(): Authz
    {
        $this->authorized = true;

        return $this;
    }

    /**
     * Unset the authorized value
     *
     * @return $this
     */
    public function unsetAuthorized(): Authz
    {
        $this->authorized = false;

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
    public function isErrors(): bool
    {
        if ($this->errors) {
            return true;
        }
        return false;
    }

    /**
     * Set the errors
     *
     * @return $this
     */
    public function setErrors(): Authz
    {
        $this->errors = true;

        return $this;
    }

    /**
     * Unset the errors
     *
     * @return $this
     */
    public function unsetErrors(): Authz
    {
        $this->errors = false;

        return $this;
    }
}
