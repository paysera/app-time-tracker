<?php
/**
 * Created by: Gediminas Samulis
 * Date: 2013-10-01
 */

namespace Paysera\TimeTrackerBundle\Repository;


use Doctrine\ORM\EntityRepository;
use Paysera\TimeTrackerBundle\Entity\Entry;


class EntryRepository extends EntityRepository
{

    /**
     * @param null $date
     *
     * @return Entry[]
     */
    public function findWaitingForRenewReservation($date = null)
    {
        if ($date === null) {
            $date = new \DateTime();
        }
        $qb = $this->createQueryBuilder('e')
            ->select('e')
            ->andWhere('e.status = :status')
            ->andWhere('e.nextReservationAt < :date')
            ->setParameter('status', Entry::STATUS_ACTIVE)
            ->setParameter('date', $date)
        ;
        return $qb
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param $number
     * @return Entry
     */
    public function findOneNewest($number)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('e')
            ->andWhere('e.number = :number')
            ->setParameter('number', $number)
            ->setMaxResults(1)
            ->orderBy('e.updatedAt', 'DESC')
        ;

        return $qb
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

}