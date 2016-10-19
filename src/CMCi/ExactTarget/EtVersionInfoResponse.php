<?PHP

namespace CMCi\ExactTarget;


class EtVersionInfoResponse extends EtBaseClass
{
    public $Version; // String
    public $VersionDate; // dateTime
    public $Notes; // String
    public $VersionHistory; // EtVersionInfoResponse
}
