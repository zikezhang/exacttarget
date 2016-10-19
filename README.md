ExactTargetComponent  
========

**Package:** zikezhang\exactTarget  
**Uses:** EtBaseClass.php  
**Version:** 1.2.x  

General  
----------
The ExactTarget component is a collection of classes based on the 
ExactTarget PHP starter kit. The purpose of this library is to spend less time 
writing Exact Target code and more time working with your application.
The EtClient handles all SOAP communication. This class holds several 
functions (defined below) which allows the EtClient to be self-sufficent on a 
basic level.  
ExactTarget API PHP starter kit (available: 
<http://docs.code.exacttarget.com/@api/deki/files/199/=PHP_APIstarterKit-V1.zip>
Source: 
<http://docs.code.exacttarget.com/020_Web_Service_Guide/API_Starter_Kits>)  
  
Presentation: <http://prezi.com/p4ckcmlimnyi/exacttarget-component/?kw=view-p4ckcmlimnyi&rc=ref-13751401>
  
Installation  
----------

### Packagist

Add exacttarget to your composer.json

    {
        "require": {
                "zikezhang/exacttarget": "dev-master"
        }
    }

Next run a composer update

    $ php composer.phar update zikezhang/exacttarget


Documentation  
---------------

See the [CMCi/exacttarget wiki](https://github.com/zikezhang/exacttarget/wiki)

* * *

CHANGELOG  
----------
### 1.0  
#### Client Access
EtEmail, EtList, EtSubscriber, and EtTriggeredSend all now can be 
passed the EtClient allowing for save and send functions (see below).
#### Save Methods
EtEmail, EtList and EtSubscriber objects now have a save() method which 
eliminates the need for calling the client update yourself.
#### Send Methods
EtEmail and EtTriggeredSend objects now have a send() method which 
eliminates the need for calling the client sendEmail yourself.
### 1.0.2
#### composer
Added lib-openssl and ext-mcrypt to composer.json file
### 1.0.3
#### Additions

 * Added unit tests
 * Added Exception classes


* * *

Contributors
----------
 * Zike ZHANG - <https://github.com/zikezhang/>
 * Micah Breedlove - <https://github.com/druid628> - [@druid628](http://twitter.com/druid628)
 * Matt Rathbun  
 * Ryder Ross - <https://github.com/ryross>
 * Rhys Laval - <https://github.com/rlweb>
 * brainbowler - <https://github.com/brainbowler>

