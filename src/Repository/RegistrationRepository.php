<?php

namespace App\Repository;

use App\Entity\Registration;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Registration|null find($id, $lockMode = null, $lockVersion = null)
 * @method Registration|null findOneBy(array $criteria, array $orderBy = null)
 * @method Registration[]    findAll()
 * @method Registration[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RegistrationRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Registration::class);
    }

    /**
     * Displays registrations for user on provided collection.
     *
     * @param User          $user
     * @param \collection[] $collections
     *
     * @return Registration[]
     */
    public function getUserRegistrations(User $user, array $collections)
    {
        $qb = $this->createQueryBuilder('d');
        $qb->where($qb->expr()->eq('d.user', ':user'));
        $qb->setParameter(':user', $user->getId());

        if (count($collections) > 0) {
            $qb->andWhere('d.baseId IN (:bases)');
            $qb->setParameter(':bases', array_map(function (\collection $collection) {
                return $collection->get_base_id();
            }, $collections));
        }

        $qb->orderBy('d.created', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get Current pending registrations.
     *
     * @param \collection[] $collections
     * @return Registration[]
     */
    public function getPendingRegistrations(array $collections)
    {
        $builder = $this->createQueryBuilder('r');
        $builder->where('r.pending = 1');

        if (!empty($collections)) {
            $builder->andWhere('r.baseId IN (:bases)');
            $builder->setParameter('bases', array_map(function (\collection $collection) {
                return $collection->get_base_id();
            }, $collections));
        }

        $builder->orderBy('r.created', 'DESC');

        return $builder->getQuery()->getResult();
    }

    /**
     * Gets registration registrations for a user.
     *
     * @param User $user
     *
     * @return array
     */
    public function getRegistrationsSummaryForUser(User $user)
    {
        $data = [];
        $rsm = $this->createResultSetMappingBuilder('d');
        $rsm->addScalarResult('sbas_id','sbas_id');
        $rsm->addScalarResult('base_id','base_id');
        $rsm->addScalarResult('dbname','dbname');
        $rsm->addScalarResult('time_limited', 'time_limited');
        $rsm->addScalarResult('limited_from', 'limited_from');
        $rsm->addScalarResult('limited_to', 'limited_to');
        $rsm->addScalarResult('actif', 'actif');

        $sql = "
        SELECT dbname, sbas.sbas_id, time_limited,
               UNIX_TIMESTAMP( limited_from ) AS limited_from,
               UNIX_TIMESTAMP( limited_to ) AS limited_to,
               bas.server_coll_id, Users.id, basusr.actif,
               bas.base_id, " . $rsm->generateSelectClause(['d' => 'd',]) . "
        FROM (Users, bas, sbas)
          LEFT JOIN basusr ON ( Users.id = basusr.usr_id AND bas.base_id = basusr.base_id )
          LEFT JOIN Registrations d ON ( d.user_id = Users.id AND bas.base_id = d.base_id )
        WHERE basusr.actif = 1 AND bas.sbas_id = sbas.sbas_id
        AND Users.id = ?";

        $query = $this->_em->createNativeQuery($sql, $rsm);
        $query->setParameter(1, $user->getId());

        foreach ($query->getResult() as $row) {
            $registrationEntity = $row[0];
            $data[$row['sbas_id']][$row['base_id']] = [
                'base-id' => $row['base_id'],
                'db-name' => $row['dbname'],
                'active' => (Boolean) $row['actif'],
                'time-limited' => (Boolean) $row['time_limited'],
                'in-time' => $row['time_limited'] && ! ($row['limited_from'] >= time() && $row['limited_to'] <= time()),
                'registration' => $registrationEntity
            ];
        }

        return $data;
    }
}
