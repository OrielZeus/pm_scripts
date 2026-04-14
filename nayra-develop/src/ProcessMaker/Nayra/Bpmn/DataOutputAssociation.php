<?php

namespace ProcessMaker\Nayra\Bpmn;

use ProcessMaker\Nayra\Contracts\Bpmn\DataOutputAssociationInterface;

/**
 * DataOutputAssociation class for handling data output associations in BPMN processes
 */
class DataOutputAssociation implements DataOutputAssociationInterface
{
    use BaseTrait;

    protected function initDataOutputAssociation()
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
