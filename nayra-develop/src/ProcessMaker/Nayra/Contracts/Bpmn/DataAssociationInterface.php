<?php

namespace ProcessMaker\Nayra\Contracts\Bpmn;

/**
 * DataAssociation interface.
 */
interface DataAssociationInterface extends EntityInterface
{
    const BPMN_PROPERTY_ASSIGNMENT = 'assignment';
    const BPMN_PROPERTY_SOURCES_REF = 'sourceRef';
    const BPMN_PROPERTY_TARGET_REF = 'targetRef';
    const BPMN_PROPERTY_TRANSFORMATION = 'transformation';

    /**
     * Get the source of the data association.
     *
     * @return ItemAwareElementInterface
     */
    public function getSource();

    /**
     * Get the target of the data association.
     *
     * @return ItemAwareElementInterface
     */
    public function getTarget();

    /**
     * Get an optional transformation Expression.
     *
     * @return FormalExpressionInterface
     */
    public function getTransformation();

    /**
     * @return AssignmentInterface[]
     */
    public function getAssignments();
}
