<?PHP

namespace CMCi\ExactTarget;


class EtTrackingEvent extends EtBaseClass
{
    public $SendID; // int
    public $SubscriberKey; // String
    public $EventDate; // dateTime
    public $EventType; // EtEventType
    public $TriggeredSendDefinitionObjectID; // String
    public $BatchID; // int
}
