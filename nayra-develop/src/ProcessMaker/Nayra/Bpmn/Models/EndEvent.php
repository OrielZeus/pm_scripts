<?php

namespace ProcessMaker\Nayra\Bpmn\Models;

use ProcessMaker\Nayra\Bpmn\Collection;
use ProcessMaker\Nayra\Bpmn\EndEventTrait;
use ProcessMaker\Nayra\Contracts\Bpmn\EndEventInterface;
use ProcessMaker\Nayra\Model\DataInputAssociationInterface;
use ProcessMaker\Nayra\Model\DataInputInterface;
use ProcessMaker\Nayra\Model\EventDefinitionInterface;
use ProcessMaker\Nayra\Model\InputSetInterface;
use ProcessMaker\Nayra\Model\TokenInterface;

/**
 * Class EndEvent
 *
 * @codeCoverageIgnore
 */
class EndEvent implements EndEventInterface
{
    use EndEventTrait;

    private $inputSet;

    /**
     * Initialize intermediate throw event.
     */
    protected function initEndEvent()
    {
        $this->properties[static::BPMN_PROPERTY_DATA_INPUT_ASSOCIATION] = new Collection();
        $this->properties[static::BPMN_PROPERTY_DATA_INPUT] = new Collection;
        $this->setProperty(static::BPMN_PROPERTY_EVENT_DEFINITIONS, new Collection);
    }

    /**
     * Array map of custom event classes for the bpmn element.
     *
     * @return array
     */
    protected function getBpmnEventClasses()
    {
        return [];
    }

    /**
     * @param EventDefinitionInterface $message
     * @param TokenInterface $token
     * @return \ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface[]
     */
    public function getTargetInstances(EventDefinitionInterface $message, TokenInterface $token)
    {
        return $this->getOwnerProcess()->getInstances();
    }

    /**
     * Get Data Inputs for the throw Event.
     *
     * @return DataInputInterface[]
     */
    public function getDataInputs()
    {
        return $this->getProperty(static::BPMN_PROPERTY_DATA_INPUT);
    }

    /**
     * Get Data Associations of the throw Event.
     *
     * @return DataInputAssociationInterface[]
     */
    public function getDataInputAssociations()
    {
        return $this->getProperty(static::BPMN_PROPERTY_DATA_INPUT_ASSOCIATION);
    }

    /**
     * Get InputSet for the throw Event.
     *
     * @return InputSetInterface
     */
    public function getInputSet()
    {
        return $this->inputSet;
    }
}
