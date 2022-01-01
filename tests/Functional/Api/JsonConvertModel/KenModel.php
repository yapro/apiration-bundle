<?php

declare(strict_types=1);

namespace YaPro\ApiRationBundle\Tests\Functional\Api\JsonConvertModel;

use Symfony\Component\Validator\Constraints as Assert;
use YaPro\ApiRationBundle\Marker\ApiRationObjectInterface;

class KenModel extends DollModel implements ApiRationObjectInterface
{
    /**
     * @Assert\Valid
     */
    private DollModel $wife;

    /**
     * @Assert\Valid
     *
     * @var DollModel[]
     */
    private array $kids;
    /**
     * Используется для проверки, что модель может быть собрана из разных типов параметров.
     * Например, когда name приходит в query, $surname в теле запроса(request), а city в slug rout'а.
     */
    private string $surname;
    private string $city;

    public function __construct(string $name, DollModel $wife, array $kids, string $surname = '', string $city = '')
    {
        parent::__construct($name);
        $this->wife = $wife;
        $this->kids = $kids;
        $this->surname = $surname;
        $this->city = $city;
    }

    public function getWife(): DollModel
    {
        return $this->wife;
    }

    public function getKids(): array
    {
        return $this->kids;
    }

    public function getSurname(): string
    {
        return $this->surname;
    }

    public function getCity(): string
    {
        return $this->city;
    }
}
