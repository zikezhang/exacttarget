<?PHP

namespace CMCi\ExactTarget;


class EtScheduleRequestMsg extends EtBaseClass
{
    public $Options; // EtScheduleOptions
    public $Action; // String
    public $Schedule; // EtScheduleDefinition
    public $Interactions; // EtInteractions
}
