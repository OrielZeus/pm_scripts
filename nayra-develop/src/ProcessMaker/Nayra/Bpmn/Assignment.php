<?php

namespace ProcessMaker\Nayra\Bpmn;

use ProcessMaker\Nayra\Contracts\Bpmn\AssignmentInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\FormalExpressionInterface;

/**
 * Assignment class that implements AssignmentInterface
 */
class Assignment implements AssignmentInterface
{
    use BaseTrait;

    /**
     * Get the 'from' formal expression.
     *
     * @return FormalExpressionInterface|callable
     */
    public function getFrom()
    {
        return $this->getProperty(self::BPMN_PROPERTY_FROM);
    }

    /**
     * Get the 'to' formal expression.
     *
     * @return FormalExpressionInterface|callable
     */
    public function getTo()
    {
        return $this->getProperty(self::BPMN_PROPERTY_TO);
    }
}
