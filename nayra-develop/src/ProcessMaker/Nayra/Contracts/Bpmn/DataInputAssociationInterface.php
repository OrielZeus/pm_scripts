<?php

namespace ProcessMaker\Nayra\Contracts\Bpmn;

/**
 * DataInputAssociation interface.
 */
interface DataInputAssociationInterface extends DataAssociationInterface
{
    const BPMN_PROPERTY_ASSIGNMENT = 'assignment';
    const BPMN_PROPERTY_SOURCES_REF = 'sourceRef';
    const BPMN_PROPERTY_TARGET_REF = 'targetRef';
    const BPMN_PROPERTY_TRANSFORMATION = 'transformation';
}
