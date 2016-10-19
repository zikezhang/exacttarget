<?PHP

namespace CMCi\ExactTarget;


/**
 * EtEmail (Active Class)
 *
 * Active Classes accept an instance of CMCi\ExactTarget\EtClient
 * to communicate with the Exact Target server.
 *
 * @package ExactTarget
 * @author  Zike <support@cmcigroup.com>
 * @version 1.0
 */
class EtEmail extends EtBaseClass
{
    protected $client;
    public $Name; // String
    public $Folder; // String
    public $CategoryID; // int
    public $HTMLBody; // String
    public $TextBody; // String
    public $ContentAreas; // EtContentArea
    public $Subject; // String
    public $IsActive; // boolean
    public $IsHTMLPaste; // boolean
    public $ClonedFromID; // int
    public $Status; // String
    public $EmailType; // String
    public $CharacterSet; // String
    public $HasDynamicSubjectLine; // boolean
    public $ContentCheckStatus; // String

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
     * @param drudi628\ExactTarget\EtClient $EtClient
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
        $this->client->updateEmail($this);
    }

    /**
     * send() - uses client to send email
     *
     * @return boolean
     */
    public function send()
    {
        return $this->client->sendEmail($this, "Email");
    }
}
