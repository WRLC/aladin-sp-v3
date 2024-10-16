<?php

/** @noinspection PhpUnused */

namespace App\Entity;

/**
 * Class AladinError
 *
 * A class to represent an error in the Aladin-SP application
 *
 */
class AladinError
{

    /** @var string The type of error */
    private string $type;

    /** @var string The intro text for the error alert */
    private string $intro;

    /** @var array<string> An array of error strings */
    private array $errors;

    /** @var bool Whether to log the error */
    private bool $log;

    /**
     * Constructor
     *
     * Create a new AladinError object
     *
     * @param string $type
     * @param string $intro
     * @param array<string> $errors
     * @param bool $log
     */
    public function __construct(string $type, string $intro, array $errors = [], bool $log = false) {
        $this->type = $type;
        $this->intro = $intro;
        $this->errors = $errors;
        $this->log = $log;
    }

    /**
     * Method to get the type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Method to set the type
     *
     * @param string $type
     * @return $this
     */
    public function setType(string $type): AladinError
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Method to get the intro
     *
     * @return string
     */
    public function getIntro(): string
    {
        return $this->intro;
    }

    /**
     * Method to set the intro
     *
     * @param string $intro
     * @return $this
     */
    public function setIntro(string $intro): AladinError
    {
        $this->intro = $intro;

        return $this;
    }

    /**
     * Method to get the errors
     *
     * @return array<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Method to set the errors
     *
     * @param array<string> $errors
     * @return $this
     */
    public function setErrors(array $errors): AladinError
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * Method to get the log
     *
     * @return bool
     */
    public function getLog(): bool
    {
        return $this->log;
    }

    /**
     * Method to set the log
     *
     * @param bool $log
     * @return $this
     */
    public function setLog(bool $log): AladinError
    {
        $this->log = $log;

        return $this;
    }

}