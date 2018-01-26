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
    protected function getColumnNameForAssociation(AuditLog $currentVersion)
    {
        $label = $this->getCustomFieldLabel($currentVersion);
        if ($label) {
            return $label;
        }

        return parent::getColumnNameForAssociation($currentVersion);
    }

    protected function filterMultiValueProperty(array $propertyDiff, $propertyName)
    {
        return $this->filterCustomFieldsProperty($propertyDiff, $propertyName);
    }

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
     * Filters the diff of one customField property. Merges the changes array to one new value.
     *
     * To be used in filterAddRemoveProperty() for logs with CustomFields.
     *
     * @param string[][] $propertyDiff
     * @param string     $propertyName
     *
     * @return string|string[][]
     */
    protected function filterCustomFieldsProperty(array $propertyDiff, $propertyName)
    {
        if (empty($this->instanceCache['customfield_labels'])) {
            // no customFields, keep diff as is
        } elseif (in_array($propertyName, $this->instanceCache['customfield_labels'])) {
            $propertyDiff = isset($propertyDiff[self::KEY_ADD]) ? implode(' , ', $propertyDiff[self::KEY_ADD]) : '';
        }

        return $propertyDiff;
    }
}
