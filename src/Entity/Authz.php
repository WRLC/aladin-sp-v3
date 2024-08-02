<?php

namespace App\Entity;

class Authz
{
    private InstitutionService $institutionService;
    private bool $authorized;
    private array $match;
    private bool $errors;

    public function __construct(InstitutionService $institutionService, bool $authorized = false, array $match = [], bool $errors = false) {
        $this->institutionService = $institutionService;
        $this->authorized = $authorized;
        $this->match = $match;
        $this->errors = $errors;
    }

    public function getInstitutionService(): InstitutionService
    {
        return $this->institutionService;
    }

    public function setInstitutionService(InstitutionService $institutionService): static
    {
        $this->institutionService = $institutionService;

        return $this;
    }

    public function getAuthorized(): bool
    {
        return $this->authorized;
    }

    public function setAuthorized(bool $authorized): static
    {
        $this->authorized = $authorized;

        return $this;
    }

    public function getMatch(): array
    {
        return $this->match;
    }

    public function setMatch(array $match): static
    {
        $this->match = $match;

        return $this;
    }

    public function getErrors(): bool
    {
        return $this->errors;
    }

    public function setErrors(bool $errors): static
    {
        $this->errors = $errors;

        return $this;
    }
}