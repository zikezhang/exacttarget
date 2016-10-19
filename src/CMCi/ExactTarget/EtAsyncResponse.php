<?php

namespace CMCi\ExactTarget;


class EtAsyncResponse extends EtBaseClass
{
    public $ResponseType; // EtAsyncResponseType
    public $ResponseAddress; // String
    public $RespondWhen; // EtRespondWhen
    public $IncludeResults; // boolean
    public $IncludeObjects; // boolean
    public $OnlyIncludeBase; // boolean
}
