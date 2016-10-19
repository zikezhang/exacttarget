<?PHP

namespace CMCi\ExactTarget;


class EtImportDefinitionUpdateType extends EtBaseClass
{
    const ADD_UPDATE      = 'AddAndUpdate';
    const ADD_NO_UPDATE   = 'AddAndDoNotUpdate';
    const UPDATE_NO_ADD   = 'UpdateButDoNotAdd';
    const MERGE           = 'Merge';
    const OVERWRITE       = 'Overwrite';
}
