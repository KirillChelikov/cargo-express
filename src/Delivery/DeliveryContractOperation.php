<?php
declare(strict_types = 1);

namespace CargoExpress\Delivery;

use CargoExpress\ClientsRepository;
use CargoExpress\TransportModelsRepository;

class DeliveryContractOperation
{
    /**
     * @var DeliveryContractsRepository
     */
    protected $contractsRepository;

    /**
     * @var ClientsRepository
     */
    protected $clientsRepository;

    /**
     * @var TransportModelsRepository
     */
    protected $transportModelsRepository;

    /**
     * DeliveryContractOperation constructor.
     *
     * @param DeliveryContractsRepository $contractsRepo
     * @param ClientsRepository $clientsRepo
     * @param TransportModelsRepository $transportModelsRepo
     */
    private function getPriceMultiplier($contracts, bool $isExpress):float {
        $multiplier = 1;
        if ($isExpress) {
            $multiplier = 2;
            if (!is_null($contracts) && count($contracts) && count($contracts) % 2 != 0) {
                $multiplier = 1.6;
            }
        }
        return $multiplier;
    }
    private function getErrors( $request) {
        $errors = [];
        $expressOnlyTypes = array('Air');
        $noExpressTypes = array('Water', 'Rail');
        if ($this->contractsRepository->getForTransportModel($request->transportModelId, $request->startDate)) {
            $errors[] =  'Извините ' .  $this->transportModelsRepository->getById($request->transportModelId)->getName() . ' занята ' . $request->startDate;
        }
        if (!$request->isExpress && in_array($this->transportModelsRepository->getById($request->transportModelId)->getType(), $expressOnlyTypes)) {
          $errors[] = 'Извините, для ' . $this->transportModelsRepository->getById($request->transportModelId)->getType() . ' транспорта доступна только экспресс доставка';
        }
        if ($request->isExpress && in_array($this->transportModelsRepository->getById($request->transportModelId)->getType(), $noExpressTypes)) {
            $errors[] = 'Извините, для ' . $this->transportModelsRepository->getById($request->transportModelId)->getType() . ' транспорта не доступна экспресс доставка';
          }
        return $errors;
    }
    public function __construct(DeliveryContractsRepository $contractsRepo, ClientsRepository $clientsRepo, TransportModelsRepository $transportModelsRepo)
    {
        $this->contractsRepository       = $contractsRepo;
        $this->clientsRepository         = $clientsRepo;
        $this->transportModelsRepository = $transportModelsRepo;
    }

    /**
     * @param DeliveryRequest $request
     * @return DeliveryResponse
     */
    public function execute(DeliveryRequest $request): DeliveryResponse
    {
        $resp = new DeliveryResponse();
        $errors = $this->getErrors($request);
        if ($errors) {
            foreach ($errors as $error) {
                $resp->pushError($error);
            }
        } else {
            $priceMultiplier = $this->getPriceMultiplier($this->contractsRepository->getClientExpressContracts($request->clientId), $request->isExpress);
            $resp->setDeliveryContract(new DeliveryContract($this->clientsRepository->getById($request->clientId), $this->transportModelsRepository->getById($request->transportModelId), $request->startDate, $request->toAddress, $priceMultiplier));
        }
        return $resp;
    }
}