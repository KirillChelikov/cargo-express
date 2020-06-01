<?php
declare(strict_types = 1);

namespace CargoExpress;

class TransportModel
{
    /** @var int id модели транспорта */
    protected $id;

    /** @var string название модели транспорта */
    protected $name;

    /** @var float Стоимость модели транспорта за час килеметр движения */
    protected $pricePerKilometer;
  /** @var string Тип модели */
    protected $type;
 /** @var float Скорость модели */
    protected $speed;
    /**
     * TransportModel constructor.
     *
     * @param int $id
     * @param string $name
     * @param float $pricePerHour
     * @param string $type
     * @param float $speed;
     */
    public function __construct(int $id, string $name, float $pricePerHour, string $type='none', float $speed = 60)
    {
        $this->id                = $id;
        $this->name              = $name;
        $this->pricePerKilometer = $pricePerHour;
        $this->type = $type; 
        $this->speed = $speed;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return float
     */
    public function getPricePerKilometer(): float
    {
        return $this->pricePerKilometer;
    }
    /**
     * @return string 
     */

    public function getType():string {
        return $this->type;
    }

    public function getSpeed():float {
        return $this->speed;
    }
}