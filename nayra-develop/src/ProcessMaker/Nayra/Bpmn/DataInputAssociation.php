<?php

namespace ProcessMaker\Nayra\Bpmn;

use ProcessMaker\Nayra\Contracts\Bpmn\DataInputAssociationInterface;

/**
 * Transition to check if the activity is a loop not yet completed or a single instance
 */
class DataInputAssociation implements DataInputAssociationInterface
{
    use BaseTrait;

    protected function initDataInputAssociation()
    {
        $this->properties[static::BPMN_PROPERTY_ASSIGNMENT] = new Collection;
    }

    public function getSource()
    {
        return $this->getProperty(static::BPMN_PROPERTY_SOURCES_REF);
    }

    public function getTarget()
    {
        return $this->getProperty(static::BPMN_PROPERTY_TARGET_REF);
    }

    public function getTransformation()
    {
        return $this->getProperty(static::BPMN_PROPERTY_TRANSFORMATION);
    }

    public function getAssignments()
    {
        return $this->getProperty(static::BPMN_PROPERTY_ASSIGNMENT);
    }
}
