<?PHP

namespace CMCi\ExactTarget;


class EtScheduleResponse extends EtBaseClass
{
    public $StatusCode; // String
    public $StatusMessage; // String
    public $OrdinalID; // int
    public $Results; // EtResults
    public $ErrorCode; // int
}
