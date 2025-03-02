<?php

namespace App\Plugins\Activity\Listener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events; // Ensure this is used
use Doctrine\Persistence\Event\LifecycleEventArgs;
use App\Plugins\Activity\Service\LogService;
use App\Plugins\Account\Entity\UserEntity;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Plugins\Activity\Entity\LogEntity;
use Doctrine\ORM\UnitOfWork;

class EntityListener implements EventSubscriber
{
    private LogService $logService;
    private RequestStack $requestStack;

    public function __construct(LogService $logService, RequestStack $requestStack)
    {
        $this->logService = $logService;
        $this->requestStack = $requestStack;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
            Events::postRemove,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->logActivity('create', $args->getObject());
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        $entityManager = $args->getObjectManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        $unitOfWork->computeChangeSets();
        
        $changeSet = $unitOfWork->getEntityChangeSet($entity);

        unset($changeSet['updated']);

        if(empty($changeSet)) 
        {
            return;
        }

        $action = 'update';
        
        if(isset($changeSet['deleted']) && $changeSet['deleted'][1] === true) 
        {
            $action = 'delete';
        }

        $this->logActivity($action, $entity, function(array &$data) use ($changeSet)
        {
            $data['data']['changes'] = array_keys($changeSet);
        });
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        // $this->logActivity('delete', $args->getObject());
    }

    private function logActivity(string $action, object $entity, ?callable $callback = null): void
    {
        if($entity instanceof LogEntity) 
        {
            return;
        }

        if(!method_exists($entity, 'onLog') || !method_exists($entity, 'getLogName')) 
        {
            return;
        }

        $entityClass = get_class($entity);

        if(!str_starts_with($entityClass, 'App\\')) 
        {
            return;
        }

        $entityClass = explode('\\', $entityClass);
        $entityClass = $entityClass[2] . ':' . substr($entityClass[4], 0, -6);

        if(!$request = $this->requestStack->getCurrentRequest())
        {
            return;
        }

        $organization = $request->attributes->get('organization');
        $user = $request->attributes->get('user');

        try 
        {
            $data = [
                'identifier' => $entity->getId() ?? 0,
                'entity'     => $entityClass,
                'data'       => [
                    'action'   => $action,
                    'changes'  => [],
                    'resource' => [
                        'id'   => $entity->getId(),
                        'name' => $entity->getLogName()
                    ],
                    'user'     => [
                        'id'    => $user?->getId() ?? null,
                        'name'  => $user?->getName() ?? 'Guest',
                        'email' => $user?->getEmail() ?? 'guest@rentsera.com'
                    ]
                ],
            ];

            if($callback)
            {
                $callback($data);
            }

            $this->logService->create($organization, $user, $data, function(LogEntity $log, array &$data) use($entity)
            {
                $entity->onLog($log, $data);
            });
        }
        catch (\Exception $e) 
        {
            echo $e->getMessage();
            exit;
        }
    }
}
