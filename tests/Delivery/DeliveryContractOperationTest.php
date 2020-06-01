<?php
declare(strict_types = 1);

namespace CargoExpress\Delivery;

use PHPUnit\Framework\TestCase;
use CargoExpress\Client;
use CargoExpress\ClientsRepository;
use CargoExpress\TransportModel;
use CargoExpress\TransportModelsRepository;
use CargoExpress\GroundTransportModel;
class DeliveryContractOperationTest extends TestCase
{
    /**
     * Stub репозитория клиентов
     *
     * @param Client[] ...$clients
     * @return ClientsRepository
     */
    private function makeFakeClientRepository(...$clients): ClientsRepository
    {
        $clientsRepository = $this->prophesize(ClientsRepository::class);
        foreach ($clients as $client) {
            $clientsRepository->getById($client->getId())->willReturn($client);
        }

        return $clientsRepository->reveal();
    }

    /**
     * Stub репозитория моделей транспорта
     *
     * @param TransportModel[] ...$transportModels
     * @return TransportModelsRepository
     */
    private function makeFakeTransportModelRepository(...$transportModels): TransportModelsRepository
    {
        $transportModelsRepository = $this->prophesize(TransportModelsRepository::class);
        foreach ($transportModels as $transportModel) {
            $transportModelsRepository->getById($transportModel->getId())->willReturn($transportModel);
        }

        return $transportModelsRepository->reveal();
    }

    /**
     * Если транспорт занят, то нельзя его арендовать
     */
    public function test_periodIsBusy_failedWithOverlapInfo()
    {
        // -- Arrange
        {
            // Клиенты
            $client1    = new Client(1, 'Джонни');
            $client2    = new Client(2, 'Роберт');
            $clientRepo = $this->makeFakeClientRepository($client1, $client2);

            // Модель транспорта
            $transportModel1 = new TransportModel(1, 'Турбо Пушка', 20);

            $transportModelsRepo = $this->makeFakeTransportModelRepository($transportModel1);

            // Контракт доставки. 1й клиент арендовал транпорт 1
            $deliveryContract = new DeliveryContract($client1, $transportModel1, '2020-01-01 00:00', 'Москва', 1);

            // Stub репозитория договоров
            $contractsRepo = $this->prophesize(DeliveryContractsRepository::class);
            $contractsRepo
                ->getForTransportModel($transportModel1->getId(), '2020-01-01 10:00')
                ->willReturn([ $deliveryContract ]);

            // Запрос на новую доставку. 2й клиент выбрал время когда транспорт занят.
            $deliveryRequest = new DeliveryRequest($client2->getId(), $transportModel1->getId(), '2020-01-01 10:00', 'Нью-Йорк', false);

            // Операция заключения договора на доставку
            $deliveryContractOperation = new DeliveryContractOperation($contractsRepo->reveal(), $clientRepo, $transportModelsRepo);
        }

        // -- Act
        $response = $deliveryContractOperation->execute($deliveryRequest);

        // -- Assert
        $this->assertCount(1, $response->getErrors());

        $message = 'Извините Турбо Пушка занята 2020-01-01 10:00';
        $this->assertStringContainsString($message, $response->getErrors()[0]);
    }

    /**
     * Если транспорт свободен, то его легко можно арендовать
     */
    public function test_successfullyOperation()
    {
        // -- Arrange
        {
            // Клиент
            $client1    = new Client(1, 'Джонни');
            $clientRepo = $this->makeFakeClientRepository($client1);

            // Модель транспорта
            $transportModel1    = new TransportModel(1, 'Турбо Пушка', 20);
            $transportModelRepo = $this->makeFakeTransportModelRepository($transportModel1);

            $contractsRepo = $this->prophesize(DeliveryContractsRepository::class);
            $contractsRepo
                ->getForTransportModel($transportModel1->getId(), '2020-01-01 17:30')
                ->willReturn([]);
            $contractsRepo
                ->getClientExpressContracts($client1->getId())
                ->willReturn([]);
            // Запрос на новую доставку
            $deliveryRequest = new DeliveryRequest($client1->getId(), $transportModel1->getId(), '2020-01-01 17:30', 'Нью-Йорк', false);

            // Операция заключения договора на доставку
            $deliveryContractOperation = new DeliveryContractOperation($contractsRepo->reveal(), $clientRepo, $transportModelRepo);
        }

        // -- Act
        $response = $deliveryContractOperation->execute($deliveryRequest);

        // -- Assert
        $this->assertEmpty($response->getErrors());
        $this->assertInstanceOf(DeliveryContract::class, $response->getDeliveryContract());

        $this->assertEquals(5000, $response->getDeliveryContract()->getPrice());
    }
      /**
     * Тест цены экспресс доставки с учетом скидки на каждый второй заказ
     */
    public function test_ExpressDeliveryPrice() 
    {
        {
        $client1    = new Client(1, 'Джонни');
        $clientRepo = $this->makeFakeClientRepository($client1);
        $transportModel1    = new TransportModel(1, 'Турбо Пушка', 20);
        $transportModelRepo = $this->makeFakeTransportModelRepository($transportModel1);
        $deliveryContract = new DeliveryContract($client1, $transportModel1, '2020-01-01 00:00', 'Москва', 1);
        $contractsRepo = $this->prophesize(DeliveryContractsRepository::class);
        $contractsRepo
                ->getClientExpressContracts($client1->getId())
                ->willReturn([$deliveryContract]);
        $contractsRepo
                ->getForTransportModel($transportModel1->getId(), '2021-01-01 00:00')
                ->willReturn([]);
        $deliveryRequest = new DeliveryRequest($client1->getId(), $transportModel1->getId(), '2021-01-01 00:00', 'Нью-Йорк', true);
        $deliveryContractOperation = new DeliveryContractOperation($contractsRepo->reveal(), $clientRepo, $transportModelRepo);

        }
        $response = $deliveryContractOperation->execute($deliveryRequest);
        $this->assertEquals(8000, $response->getDeliveryContract()->getPrice());
    }
      /**
     * Экспресс доставка недоступна для жд и водного транспорта, но обязательна для воздушного транспорта
     */
    public function test_ExpressFail() 
    {
        {
        $client1    = new Client(1, 'Джонни');
        $clientRepo = $this->makeFakeClientRepository($client1);
        $transportModel1    = new TransportModel(1, 'Летающая Турбо Пушка', 40, 'Air');
        $transportModel2 = new TransportModel(2, 'Водная Турбо Пушка', 10, 'Water');
        $transportModelRepo = $this->makeFakeTransportModelRepository($transportModel1, $transportModel2 );
        $deliveryContract = new DeliveryContract($client1, $transportModel1, '2020-01-01 00:00', 'Москва', 1);
        $contractsRepo = $this->prophesize(DeliveryContractsRepository::class);
        $contractsRepo
                ->getClientExpressContracts($client1->getId())
                ->willReturn([$deliveryContract]);
        $contractsRepo
                ->getForTransportModel($transportModel1->getId(), '2021-01-01 00:00')
                ->willReturn([]);
        $contractsRepo
                ->getForTransportModel($transportModel2->getId(), '2022-01-01 00:00')
                ->willReturn([]);
        $deliveryRequest = new DeliveryRequest($client1->getId(), $transportModel1->getId(), '2021-01-01 00:00', 'Нью-Йорк', false);
        $deliveryRequest2 = new DeliveryRequest($client1->getId(), $transportModel2->getId(), '2022-01-01 00:00', 'Нью-Йорк', true);
        $deliveryContractOperation = new DeliveryContractOperation($contractsRepo->reveal(), $clientRepo, $transportModelRepo);
        $deliveryContractOperation2 = new DeliveryContractOperation($contractsRepo->reveal(), $clientRepo, $transportModelRepo);
        }
        $response = $deliveryContractOperation->execute($deliveryRequest);
        $response2 = $deliveryContractOperation2->execute($deliveryRequest2);
        $this->assertCount(1, $response->getErrors());
        $this->assertCount(1, $response2->getErrors());
        $message = 'Извините, для Air транспорта доступна только экспресс доставка';
        $this->assertStringContainsString($message, $response->getErrors()[0]);
        $message2 = 'Извините, для Water транспорта не доступна экспресс доставка';
        $this->assertStringContainsString($message2, $response2->getErrors()[0]);
    }
     /**
     * Для наземных моделей скорость не может быть выше ограничения скорости
     */
    public function test_maxSpeedOfGroundTransport() 
    {
        {
        $transportModel  = new GroundTransportModel(1, 'Быстрая Турбо Пушка', 500, 'Ground', 9000);
        $transportModel1 = new GroundTransportModel(2, 'Медленная Турбо Пушка', 1, 'Ground', 20);
        }
    $this->assertInstanceOf(TransportModel::class, $transportModel);
     $this->assertEquals($GLOBALS['speedRestriction'], $transportModel->getSpeed());
     $this->assertEquals(20, $transportModel1->getSpeed());
    
    }
}