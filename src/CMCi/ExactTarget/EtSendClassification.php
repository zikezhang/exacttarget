<?PHP

namespace CMCi\ExactTarget;


class EtSendClassification extends EtBaseClass
{
    public $SendClassificationType; // EtSendClassificationTypeEnum
    public $Name; // String
    public $Description; // String
    public $SenderProfile; // EtSenderProfile
    public $DeliveryProfile; // EtDeliveryProfile
}
