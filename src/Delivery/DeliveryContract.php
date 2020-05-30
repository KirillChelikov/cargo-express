<?php
declare(strict_types = 1);

namespace CargoExpress\Delivery;

use CargoExpress\Client;
use CargoExpress\TransportModel;
use CargoExpress\DistanceCalculator;

class DeliveryContract
{
    /** @var Client */
    protected $client;

    /** @var TransportModel */
    protected $transportModel;

    /** @var float Стоимость */
    protected $city;

    /**
     * @var string
     */
    protected $startDate;
    private $priceMultiplier;
    /**
     * DeliveryContract constructor.
     * @param Client $client
     * @param TransportModel $transportModel
     * @param string $startDate
     */
    public function __construct(Client $client, TransportModel $transportModel, string $startDate, string $city, float $priceMultiplier)
    {
        $this->client         = $client;
        $this->transportModel = $transportModel;
        $this->startDate      = $startDate;
        $this->city         = $city;
        $this->priceMultiplier = $priceMultiplier;
    }
    
    /**
     * @return float
     */
    public function getPrice(): float
    {
       return DistanceCalculator::calculateDistance($this->city) * $this->transportModel->getPricePerKilometer() * $this->priceMultiplier;
    }
}
