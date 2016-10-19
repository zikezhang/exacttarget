<?PHP

namespace CMCi\ExactTarget;

class EtSimpleFilterPart extends EtFilterPart
{
    public $Property; // String
    public $SimpleOperator; // EtSimpleOperators
    public $Value; // String
    public $DateValue; // dateTime
}
