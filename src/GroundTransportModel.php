<?php
declare(strict_types = 1);

namespace CargoExpress;
use CargoExpress\TransportModel;
include 'Constants.php';
class GroundTransportModel extends TransportModel
{
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
        parent::__construct($id, $name, $pricePerHour, $type, $speed);
        $this->type = 'Ground';
        $this->decreaseSpeedToMaxAllowed();
    }

    private function decreaseSpeedToMaxAllowed() {
        if ($this->speed > $GLOBALS['speedRestriction']) {
            $this->speed = $GLOBALS['speedRestriction'];
        }
    }
}