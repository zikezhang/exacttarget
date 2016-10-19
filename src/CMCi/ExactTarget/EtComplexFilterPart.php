<?PHP

namespace CMCi\ExactTarget;

class EtComplexFilterPart extends EtFilterPart
{
    public $LeftOperand; // EtFilterPart
    public $LogicalOperator; // EtLogicalOperators
    public $RightOperand; // EtFilterPart
}
