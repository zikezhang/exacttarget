<?PHP

namespace CMCi\ExactTarget;


/**
 * EtAttribute (Passive Class)
 *
 * Passive classes do not directly communicate with the Exact Target server.
 *
 * @package ExactTarget
 * @author  Zike Zhang <support@cmcigroup.com>
 * @version 1.0
 */

class EtAttribute extends EtBaseClass
{
    public $Name; // string
    public $Value; // string

    public function __construct($name = null, $value = null)
    {
        if (!is_null($name)) {
            $this->Name = $name;
        }
        if (!is_null($value)) {
            $this->Value = $value;
        }
    }
}
