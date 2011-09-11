# WebServiceBundle

This bundle allows to configure the mapping between an entity and a webservice.

## Installation

Download sources from github:

```ini

    [HeriJobQueueBundle]
        git=https://github.com/heristop/HeriWebServiceBundle.git
        target=/bundles/Heri/WebServiceBundle/
```

Register namespace in autoload:

```php

    $loader->registerNamespaces(array(
        ...
        'Heri' => __DIR__.'/../vendor/bundles',
    ));
```

Load in AppKernel: 

```php
    $bundles[] = new Heri\JobQueueBundle\HeriWebServiceBundle();
```

## ZF Installation

Use this unofficial github mirror:

```shell
    git clone https://github.com/tjohns/zf.git vendor/zf
```

Register a prefix in AppKernel:

```php

    $loader->registerPrefixes(array(
        ...
        'Zend_' => __DIR__.'/../vendor/zf/library',
    ));

```

Following the [official ZF documentation](http://framework.zend.com/manual/en/performance.classloading.html#performance.classloading.striprequires.sed), remove all _require_once()_:

```shell

    $ cd vendor/zf/library
    $ find . -name '*.php' -not -wholename '*/Loader/Autoloader.php' \
    -not -wholename '*/Application.php' -print0 | \
    xargs -0 sed --regexp-extended --in-place 's/(require_once)/\/\/ \1/g'

```

## Usage

First, add the column _toUpdate_ in your entity definition.
This field will be set to _false_ after the synchronization:

```php

    /**
     * @ORM\Column(name="to_update", type="boolean")
     */
    protected $toUpdate;
```

Generate getters and setters:

```shell
    app/console doctrine:generate:entities %YourBundle%
```

Create a class in _%YourBundle%/Service_ directory to apply the mapping with the WSDL.
The bundle contains an example:

```php

    namespace Heri\WebServiceBundle\Service;
    
    use Heri\WebServiceBundle\ClientSoap\ClientObject;
    
    class Sample extends ClientObject
    {
        public function configure()
        {
            $this->name  = 'sample';
            $this->table = 'HeriWebServiceBundle:Sample';
            $this->func  = 'addSample';
        }
        
        public function hydrate($record)
        {  
            $this->params = array(
                'id'    => $record->getId(),
                'label' => $record->getLabel(),
            );
        }
    
    }
```

Configure the webservices connection in config.yml:

```yaml

    heri_web_service:  
        namespaces:             [ %YourBundleNamespace%\Service ]
        authentication:                      # optional
            login:              %login%
            password:           %password%
        webservices:
            brand:
                name:           brand
                url:            %soap_url%
                authentication: true         # optional
```

Then, use this command to call a webservice and retrieve all the records with _toUpdate_ to _true_:

```shell
    app:console webservice:load %Service%
```

To see the list of available functions add _list_ option.

## Configuration

Edit config.yml to add _SyncListener_:

```yaml

    services:
       sync.listener:
            class: Heri\WebServiceBundle\Listener\SyncListener
            tags:
                - { name: doctrine.event_listener, event: prePersist, connection: default }
                - { name: doctrine.event_listener, event: postPersist, connection: default }
```

## Synchronization with the JobQueue manager

This bundle can be used with [HeriJobQueueBundle](https://github.com/heristop/HeriJobQueueBundle) to manage multiple webservice connections.

Override configuration and add the depedency to jobqueue service in config.yml:

```yaml

    services:
        sync.listener:
            class: Heri\WebServiceBundle\Listener\SyncListener
            arguments: [@jobqueue]
            tags:
                - { name: doctrine.event_listener, event: prePersist, connection: default }
                - { name: doctrine.event_listener, event: postPersist, connection: default }
        jobqueue:
            class: Heri\JobQueueBundle\Service\QueueService
            arguments: [@logger]
            tags:
                - { name: monolog.logger, channel: jobqueue }
```

Add a method called _synchronize()_ in the object which return the name of queue:

```php

    /**
     * Adds synchronization in specified queue
     * 
     * @return string
     */
    public function synchronize()
    {
        return '%queue_name%';
    }
```

When the record will be saved in database, the synchronization to the webservice will be pushed in queue.

## Note

You can override the _ClientObject_ class in order to apply a specific configuration.

The example below shows the method to connect your application to a Magento plateform: 

```yaml

heri_web_service:
    namespaces:             [ %YourBundleNamespace%\Service ]
    authentication:
        login:              sampleuser
        password:           123456
    webservices:
        magento:
            name:           api
            url:            http://myshop-local.com/index.php/api/
```

```php

abstract class ClientMagento extends ClientObject
{
    protected $name = 'api';
    
    protected function callFunction($func, array $params = array())
    {
        $connection = $this->getContainer()->getConnection();
        $sessionId = $this->client->login($connection->getLogin(), $connection->getPassword());
        
        return $this->client->call(
            $sessionId,
            $func,
            $params
        );
    }
}
```