<?php

namespace Api\LongPoll\Service;

use Api\LongPoll\Models\LongPollModel;
use Api\Models\TokenModel;
use Api\Services\MessageService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;

class EventService
{
    private EntityRepository $entityRepository;
    private EntityManager $entityManager;

    /**
     * @param EntityManager $entityManager
     * @throws NotSupported
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->entityRepository = $entityManager->getRepository(LongPollModel::class);
    }

    /**
     * Прослушивание новых событий
     * @param int $timeout
     * @param string $token
     * @return array
     * @throws NotSupported
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function listen(int $timeout, string $token): array
    {
        $me = $this->entityManager->getRepository(TokenModel::class)->findOneBy(
            ["token" => $token]
        )->getAId();

        for ($i = 0; $i < $timeout; $i++) {
            /** @var LongPollModel[] $events */
            $events = $this->entityRepository->findBy(["aId" => $me, "checked" => false]);

            if ($events === []) {
                sleep(1);
                continue;
            }

            $preparedData = [];

            foreach ($events as $event) {
                $event->setChecked(true);

                switch ($event->getData()["type"]) {
                    case "newMessage":
                        $preparedData[] = $this->changeValue(
                            "message",
                            MessageService::decryptMessage($event->getData()["data"]["message"]),
                            $event->getData()
                        );
                        break;
                    default:
                        $preparedData[] = $event->getData();
                        break;
                }

                $this->entityManager->flush();
            }

            return $preparedData;
        }
        return [];
    }

    private function changeValue($findKey, $newValue, $array): array
    {
        $newArray = [];
        foreach ($array as $key => $value) {
            $newArray[$key] = $value;
            if ($findKey === $key) $newArray[$key] = $newValue;
            if (is_array($value)) $newArray[$key] = $this->changeValue($findKey, $newValue, $value);
        }
        return $newArray;
    }
}