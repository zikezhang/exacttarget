<?PHP

namespace CMCi\ExactTarget;


/**
 * EtList (Active Class)
 *
 * Active Classes accept an instance of CMCi\ExactTarget\EtClient
 * to communicate with the Exact Target server.
 *
 * @package ExactTarget
 * @author  Zike ZHANG <support@cmcigroup.com>
 * @version 1.0
 */
class EtList extends EtBaseClass
{
    protected $client;
    public $ListName; // String
    public $Category; // int
    public $Type; // EtListTypeEnum
    public $Description; // String
    public $Subscribers; // array() of EtSubscriber
    public $ID; // int
    public $ObjectID; // String
    public $CustomerKey; // String

    /**
     * allow for passing optional client class to [some] Et-classes
     * so they can take advantage of client specific functions.
     * e.g. send() and save()
     *
     * @param CMCi\ExactTarget\EtClient $EtClient
     */
    public function __construct($EtClient = null)
    {
        $this->client = $EtClient;
    }

    /**
     * Used for setting client after class instantiation
     *
     * @param CMCi\ExactTarget\EtClient $EtClient
     */
    public function setClient($EtClient)
    {
        $this->client = $EtClient;
    }

    /**
     * Get active client instance.
     *
     * @return CMCi\ExactTarget\EtClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * save() - uses client to save/update
     *
     */
    public function save()
    {
        $this->client->updateList($this);
    }
}
