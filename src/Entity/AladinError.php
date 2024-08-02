<?php

namespace App\Entity;

class AladinError
{

    private string $type;

    private string $intro;

    private array $errors;

    private bool $log;

    public function __construct(string $type, string $intro, array $errors = [], bool $log = false) {
        $this->type = $type;
        $this->intro = $intro;
        $this->errors = $errors;
        $this->log = $log;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getIntro(): string
    {
        return $this->intro;
    }

    public function setIntro(string $intro): static
    {
        $this->intro = $intro;

        return $this;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function setErrors(array $errors): static
    {
        $this->errors = $errors;

        return $this;
    }

    public function getLog(): bool
    {
        return $this->log;
    }

    public function setLog(bool $log): static
    {
        $this->log = $log;

        return $this;
    }

}