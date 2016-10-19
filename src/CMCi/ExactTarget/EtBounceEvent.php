<?PHP

namespace CMCi\ExactTarget;


class EtBounceEvent extends EtBaseClass
{
    public $SMTPCode; // String
    public $BounceCategory; // String
    public $SMTPReason; // String
    public $BounceType; // String
}
