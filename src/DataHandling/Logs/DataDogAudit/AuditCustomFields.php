<?php

namespace CubeTools\CubeCommonBundle\DataHandling\Logs\DataDogAudit;

use CubeTools\CubeCommonBundle\DataHandling\Logs\DataDogAudit;
use DataDog\AuditBundle\Entity\AuditLog;

/**
 * Rewrites the history of customFields to separate fields.
 *
 * customFields are from CubeTools\CubeCustomFieldBundle
 */
class AuditCustomFields extends DataDogAudit
{
    /**
     * Get the label for a CustomField.
     *
     * To be called in getColumnNameForAssociation() for logs with CustomFields.
     *
     * @param AuditLog $currentVersion
     *
     * @return string|null label of custom field, or null if not a custom field
     */
    protected function getCustomFieldLabel(AuditLog $currentVersion)
    {
        if (!$currentVersion->getTarget() || 'custom_fields' !== $currentVersion->getTarget()->getTbl()) {
            return null;
        }
        $cfId = $currentVersion->getTarget()->getFk();
        if (isset($this->instanceCache['customfield_labels'][$cfId])) {
            return $this->instanceCache['customfield_labels'][$cfId];
        }

        $cfQb = $this->em->getRepository(AuditLog::class)->createQueryBuilder('c')
            ->join('c.source', 's')
            ->where("c.action = 'insert'")
            ->andWhere('s.fk = :cfId')->setParameter('cfId', $cfId)
            ->andWhere("s.tbl = 'custom_fields'")
        ;
        $cfLogs = $cfQb->getQuery()->getResult();
        $fieldId = null;
        foreach ($cfLogs as $log) {
            if (isset($log->getDiff()['fieldId'])) {
                $fieldId = $log->getDiff()['fieldId']['new'];
                break;
            }
        }

        if (is_null($fieldId)) { // fallback
            $fieldId = 'customfield_'.$cfId;
        }
        $this->instanceCache['customfield_labels'][$cfId] = $fieldId;

        return $fieldId;
    }

    /**
     * Filters the diff format of CustomFields.
     *
     * To be used in overwritten getAllVersionsDiff() after calling it from parent class.
     * Rewrites the values as simple value instead of array.
     *
     * @param array $diff
     *
     * @return array modified $diff
     */
    protected function filterCustomFieldsAllVersionsDiff($diff)
    {
        if (empty($this->instanceCache['customfield_labels'])) {
            return $diff;
        }
        $cfLabels = array_unique($this->instanceCache['customfield_labels']);
        foreach ($diff as $key => $changeEntry) {
            $changes = $changeEntry['changes'];
            foreach ($cfLabels as $label) {
                if (!isset($changes[$label])) {
                    continue;
                }
                $value = isset($changes[$label][self::KEY_ADD]) ? $changes[$label][self::KEY_ADD] : '';
                $diff[$key]['changes'][$label] = $value;
            }
        }

        return $diff;
    }
}
