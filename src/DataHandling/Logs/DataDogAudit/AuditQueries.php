<?php

namespace CubeTools\CubeCommonBundle\DataHandling\Logs\DataDogAudit;

use DataDog\AuditBundle\Entity\AuditLog;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Queries for DataDog\AuditBundle\Entity\AuditLog.
 */
class AuditQueries
{
    /**
     * @var \Doctrine\ORM\EntityRepository
     */
    private $auditLogRepo;

    public function __construct(EntityManagerInterface $em)
    {
        $this->auditLogRepo = $em->getRepository(AuditLog::class);
    }

    /**
     * Query for basic info about an entity (alias "a").
     *
     * @param int|object $id
     * @param string     $class
     *
     * @return QueryBuilder
     */
    public function createAuditLogQb($id, $class)
    {
        return $this->auditLogRepo
            ->createQueryBuilder('a')
            ->join('a.source', 's')
            ->where('s.fk = :entity')->setParameter('entity', $id)
            ->andWhere('s.class = :class')->setParameter('class', $class)
            ->orderBy('a.id', 'ASC')
        ;
    }

    /**
     * Extends the QueryBuilder from createAuditLogQb with logs of attributes.
     *
     * @param QueryBuilder $qb
     * @param string       $attributeClass
     * @param int[]        $ids
     */
    public static function extendAuditLogWithAttributeQb(QueryBuilder $qb, $attributeClass, $ids)
    {
        $className = substr($attributeClass, (strrpos($attributeClass, '\\') ?: -1) + 1);
        $unique = count($qb->getParameters()).$className;

        $qb->orWhere('s.fk IN (:idsAttr'.$unique.') AND s.class = :classAttr'.$unique)
            ->setParameter('classAttr'.$unique, $attributeClass)
            ->setParameter('idsAttr'.$unique, $ids)
        ;
    }

    /**
     * Base query for a OneToMany association where the entity is the mapping side (alias "al").
     *
     * @param string $attrClass
     * @param string $entInAttr
     * @param string $entityClass
     * @param string $entityId
     *
     * @return QueryBuilder
     */
    public function create1toNAssociationQb($attrClass, $entInAttr, $entityClass, $entityId)
    {
        $entClassJson = json_encode($entityClass);

        return $this->auditLogRepo
            ->createQueryBuilder('al')
            ->join('al.source', 's')
            ->where('s.class = :attrClass')->setParameter('attrClass', $attrClass)
            ->andWhere("al.action = 'insert'")
            ->andWhere("al.diff LIKE :diffLikeClsS ESCAPE '°'")
            ->setParameter('diffLikeClsS', '%"'.$entInAttr.'":%"class":'.$entClassJson.'%,"fk":"'.$entityId.'",%') // fk as string
        ;
    }

    public function extend1toNAssociationQb(QueryBuilder $qb, array $additionalConditions)
    {
        $i = 1;
        foreach ($additionalConditions as $condition) {
            $condValue = json_encode($condition['value']);
            $qb->andWhere('al.diff LIKE :diffLike'.$i)->setParameter('diffLike'.$i, '%"'.$condition['attr'].'"%"new":'.$condValue.'%');
            ++$i;
        }
    }
}
