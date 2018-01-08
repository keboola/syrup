UPGRADE FROM 7.0 to 8.0
=======================

ObjectEncryptor was moved to a separate library. Namespace changes from 
`Keboola\Syrup\Service\ObjectEncryptor` to `Keboola\ObjectEncryptor\ObjectEncryptor`.
To get the encryptor use:

```php
$encryptor = $client->getContainer()->get('syrup.object_encryptor_factory')->getEncryptor();
$encryptedToken = $encryptor->encrypt(TOKEN);
```

Service `syrup.object_encryptor` was removed and is replaced by `syrup.object_encryptor_factory`. 
Use `getEncryptor()` method of the factory to obtain actual encryptor.

UPGRADE FROM 2.x to 3.0
=======================

### StorageApi
 
 * `StorageApiService` constructor order of parameter changed, $requestStack is now required.
 * `StorageApiService::setRequest()` method was removed, request is now injected via `request_stack` service.
     
    Basic usage, when $request is provided via service container (Controller):
    
    ```php        
    $storageApiService = $this->container->get('syrup.storage_api');
    $storageApiClient = $storageApiService->getClient();    
    ```
    
    Usage in scenarios, where client need to created and set manually (Command):
    
    ```php            
    $client = new Keboola\StorageApi\Client([
        'token' => TOKEN        
    ]);
    $client->setRunId($this->job->getRunId());
    $storageApiService = $this->getContainer()->get('syrup.storage_api');
    $storageApiService->setClient($client);        
    ```
    
    Usage with custom request stack (Tests):
    
    ```php
    $storageApiService = new StorageApiService(new RequestStack());
    $storageApiService->setClient(
        new Keboola\StorageApi\Client([
            'token' => TOKEN
        ])
    );
    ```
    
 * `StorageApiHandler::setStorageApiClient($client)` method was removed. StorageApi Client is obtained via StorageApiService.
 * `Keboola\Syrup\Job\Metadata\JobFactory::setStorageApiClient($client)` method was removed. StorageApi Client is obtained via StorageApiService.
 * `Keboola\StorageApi\Client updated` to 4.0. Method getLogData() was removed, use StorageApiService::getTokenData() instead.
 
    Before:
    
    ```php
    $client = new Keboola\StorageApi\Client([
        'token' => TOKEN        
    ]);
    $logData = $client->getLogData();
    ```        
        
    After:
    
    ```php    
    $storageApiService = $this->getContainer()->get('syrup.storage_api');    
    $logData = $storageApiService->getTokenData();
    ```
 
### Encryptor
 
 * `Keboola\Syrup\Encryption\Encryptor` was deprecated, use ObjectEncryptor instead.
 
    Usage is similar:
    
    ```php
    $encryptor = $client->getContainer()->get('syrup.object_encryptor');
    $encryptedToken = $encryptor->encrypt(TOKEN);
    ```
    
### Executor
    
 * `Keboola\Syrup\Job\ExecutorFactory` has been refactored, `create()` method doesn't use $job argument anymore.
 
    Before, it was possible to do this:
    
    ```php
    $executorFactory = $this->getContainer()->get('syrup.job_executor_factory');
    $executor = $executorFactory->create($job);
    $executor->run();
    ```
    
    After:
    
    ```php
    $executorFactory = $this->getContainer()->get('syrup.job_executor_factory');
    $executor = $executorFactory->create();
    $executor->run($job);
    ```
    
    Also, the create method used to construct executor service name from the name of the component. 
    This behaviour has been removed, when you want to override executor just use "syrup.job_executor" name in your service container.               
    
    Before:
    ```yml
        #services.yml        
        ex-google-drive.job_executor:
            class: Keboola\GoogleDriveExtractor\JobExecutor
            arguments: [@logger]
    ```
    
    After:
    ```yml
        #services.yml        
        syrup.job_executor:
            class: Keboola\GoogleDriveExtractor\JobExecutor
            arguments: [@logger]
    ```
    
 * `Keboola\Syrup\Job\ExecutorInterface` has now methods:
 
    - `cleanup($job)`
    - `postCleanup($job)`
    - `execute($job)`
    - `postExecute($job)`
    
    Theese are triggered in JobCommand in this order. So The HookExecutorInterface is now obsolete.
    
 * `Keboola\Syrup\Job\HookExecutorInterface` is now deprecated. Use Executor::postExecute() method instead.
    