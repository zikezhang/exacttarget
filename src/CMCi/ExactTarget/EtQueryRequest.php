<?PHP

namespace CMCi\ExactTarget;


class EtQueryRequest extends EtBaseClass
{
    public $ClientIDs; // EtClientID
    public $Query; // EtQuery
    public $RespondTo; // EtAsyncResponse
    public $PartnerProperties; // EtAPIProperty
    public $ContinueRequest; // String
    public $QueryAllAccounts; // boolean
    public $RetrieveAllSinceLastBatch; // boolean
}
