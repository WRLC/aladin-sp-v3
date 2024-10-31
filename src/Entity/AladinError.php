<?php /** @noinspection PhpUnused */

namespace App\Entity;

/**
 * Class AladinError
 */
class AladinError
{

    private string $type;

    private string $intro;

    /** @var array<string>  */
    private array $errors;

    private bool $log;

    /**
     * AladinError constructor.
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
     * Get the type of error
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set the type of error
     *
     * @param string $type
     *
     * @return $this
     */
    public function setType(string $type): AladinError
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get the intro of the error
     *
     * @return string
     */
    public function getIntro(): string
    {
        return $this->intro;
    }

    /**
     * @param string $intro
     *
     * @return $this
     */
    public function setIntro(string $intro): AladinError
    {
        $this->intro = $intro;

        return $this;
    }

    /**
     * Get the errors
     *
     * @return array<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Set the errors
     *
     * @param array<string> $errors
     *
     * @return $this
     */
    public function setErrors(array $errors): AladinError
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * Get the log
     *
     * @return bool
     */
    public function getLog(): bool
    {
        return $this->log;
    }

    /**
     * Set the log
     *
     * @param bool $log
     *
     * @return $this
     */
    public function setLog(bool $log): AladinError
    {
        $this->log = $log;

        return $this;
    }

}