<?php

namespace ProcessMaker\Nayra\Contracts\Bpmn;

/**
 * Assignment interface.
 */
interface AssignmentInterface extends EntityInterface
{
    const BPMN_PROPERTY_FROM = 'from';
    const BPMN_PROPERTY_TO = 'to';

    /**
     * @return FormalExpressionInterface
     */
    public function getFrom();

    /**
     * @return FormalExpressionInterface
     */
    public function getTo();
}
