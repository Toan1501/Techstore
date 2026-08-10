<?php

namespace App\Repository;

use App\Entity\StoreSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StoreSettings>
 */
class StoreSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreSettings::class);
    }

    /** Return the singleton settings row, creating defaults on first use. */
    public function get(): StoreSettings
    {
        $s = $this->findOneBy([]);
        if (!$s) {
            $s = new StoreSettings();
            $em = $this->getEntityManager();
            $em->persist($s);
            $em->flush();
        }

        return $s;
    }
}
