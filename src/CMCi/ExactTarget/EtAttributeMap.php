<?PHP

namespace CMCi\ExactTarget;

/**
 * EtAttributeMap (Passive Class)
 *
 * Passive classes do not directly communicate with the Exact Target server.
 *
 * @package ExactTarget
 * @author  Zike Zhang <support@cmcigroup.com>
 * @version 1.0
 */

class EtAttributeMap extends EtBaseClass
{
    public $EntityName; // String
    public $ColumnName; // String
    public $ColumnNameMappedTo; // String
    public $EntityNameMappedTo; // String
    public $AdditionalData; // EtAPIProperty
}
