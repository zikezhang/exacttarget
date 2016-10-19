<?PHP

namespace CMCi\ExactTarget;


class EtExecuteResponse extends EtBaseClass
{
    public $StatusCode; // String
    public $StatusMessage; // String
    public $OrdinalID; // int
    public $Results; // EtAPIProperty
    public $ErrorCode; // int
}
