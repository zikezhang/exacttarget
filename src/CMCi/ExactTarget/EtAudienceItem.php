<?PHP

namespace CMCi\ExactTarget;

/**
 * EtAudienceItem (Passive Class)
 *
 * Passive classes do not directly communicate with the Exact Target server.
 *
 * @package ExactTarget
 * @author  Zike Zhang <support@cmcigroup.com>
 * @version 1.0
 */

class EtAudienceItem extends EtBaseClass
{
    public $List; // EtList
    public $SendDefinitionListType; // EtSendDefinitionListTypeEnum
    public $CustomObjectID; // String
    public $DataSourceTypeID; // EtDataSourceTypeEnum
}
