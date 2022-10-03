Jenkins Larave API
===============


Jenkins Laravel API is a set of classes designed to interact with Jenkins CI using its API.

Installation
------------

The recommended way to install Jenkins Laravel API is through [Composer](http://getcomposer.org).

```bash
curl -sS https://getcomposer.org/installer | php
```

Then, run the Composer command to install the latest version:

In bash terminal

```bash
composer.phar require thusithawijethunga/jenkins-laravel-api
```

In windows cmd terminal

```cmd
composer require thusithawijethunga/jenkins-laravel-api
```

or Update your composer json

```json
      "require": {
        ...,
        "thusithawijethunga/jenkins-laravel-api": "^1.0"
      }
```

Basic Usage
----------------

```php 

php artisan vendor:publish --tag=config

```
 or 

```php 
php artisan vendor:publish --provider="JenkinsLaravel\JenkinServiceProvider" --tag="config"

```


Before anything, you need to instantiate the client :

Update your Laravel Env File

```php

# Url Is Https
JENKINS_URL_HTTPS   =   false
JENKINS_DOMAIN      =   host.org
JENKINS_PORT        =   8080
JENKINS_USER        =   admin
JENKINS_TOKEN       =   token

```

or update config file

```php

\config\jenkinapi.php

```

```php
    $jenkins = new \JenkinsLaravel\Jenkins();
    
```

If your Jenkins needs authentication, you need to pass a URL like this : `'http://user:token@host.org:8080'`.


Generate Api Token
----------------------

```php

https://{jenkins}/user/{user-name}/configure

```

Here are some examples of how to use it:


Import Api Class
----------------------

```php

    use JenkinsLaravel\Jenkins as JenkinsApi;

```

Get the job information
----------------------

```php

    $jenkins = new JenkinsApi();
    
    $jenkins->initialize();

    $job = $jenkins->getJob("dev2-pull");

    $job->getName();

    $job->getFullDisplayName();

    $job->getColor();

    $job->getIsDisabled();

    $job->getNextBuildNumber();

    $job->getUrl();

    foreach ($job->getHealthReport() as $health) {

        $health->iconClassName;

        $health->description;

        $health->score;

    }

    $job->getColor(); // blue,red,notbuilt

    if($job->getQueueItem())
    {
        
        $job->getQueueItem()->getUrl();
        $job->getQueueItem()->getInQueueSince();
        $job->getQueueItem()->getWhy();

    }

    foreach ($job->getBuilds() as $build) {
        
        $build->getUrl();

    }

    if($job->getLastBuild())
    {

        $job->getLastBuild()->getUrl();
        $job->getLastBuild()->getNumber();

    }


    if($job->getLastCompletedBuild())
    {

        $job->getLastCompletedBuild()->getUrl();
        $job->getLastCompletedBuild()->getNumber();

    }

    if($job->getLastFailedBuild())
    {

        $job->getLastFailedBuild()->getUrl();
        $job->getLastFailedBuild()->getNumber();

    }

    if($job->getLastStableBuild())
    {

        $job->getLastStableBuild()->getUrl();
        $job->getLastStableBuild()->getNumber();

    }

    if($job->getLastSuccessfulBuild())
    {

        $job->getLastSuccessfulBuild()->getUrl();
        $job->getLastSuccessfulBuild()->getNumber();

    }

    if($job->getLastUnstableBuild())
    {

        $job->getLastUnstableBuild()->getUrl();
        $job->getLastUnstableBuild()->getNumber();

    }

    if($job->getLastUnsuccessfulBuild())
    {

        $job->getLastUnsuccessfulBuild()->getUrl();
        $job->getLastUnsuccessfulBuild()->getNumber();

    }

    // is Job Buildable?
    $job->getBuildable();

```

Get All Jobs
----------------------

```php

$allJobs = $jenkins->getJobs();

foreach ($allJobs as $job) {
    # color
    $job->getColor() 
    # name
    $job->getName()
    # url
    $job->getUrl()          
}

```


Launch a Job
------------

```php

    $jenkins = new JenkinsApi();

    $jenkins->initialize();

    $job = $jenkins->launchJob("clone-deploy");
    var_dump($job);
    // bool(true) if successful or throws a RuntimeException
```


List the jobs of a given view
-----------------------------

```php

    $jenkins = new JenkinsApi();

    $jenkins->initialize();

    $view = $jenkins->getView('madb_deploy');
    foreach ($view->getJobs() as $job) {
      var_dump($job->getName());
    }
    //string(13) "altlinux-pull"
    //string(8) "dev-pull"
    //string(9) "dev2-pull"
    //string(11) "fedora-pull"
```


Get All Views
----------------------

```php

$allViews = $jenkins->getViews();

foreach ($allViews as $view) {
    # name
    $job->getName()
    # url
    $job->getUrl()          
}

```


List builds and their status
----------------------------

```php

    $jenkins = new JenkinsApi();

    $jenkins->initialize();

    $job = $jenkins->getJob('dev2-pull');
    foreach ($job->getBuilds() as $build) {
      var_dump($build->getNumber());
      var_dump($build->getResult());
    }
    //int(122)
    //string(7) "SUCCESS"
    //int(121)
    //string(7) "FAILURE"
```


Check if Jenkins is available
-----------------------------

```php
    var_dump($jenkins->isAvailable());
    //bool(true);
```

Get Jenkins Version
-----------------------------

```php
    var_dump($jenkins->getJenkinsVersion());
    //string(7) "2.361.1";
```

Using Facade
------------------

```php

use JenkinsLaravel\Facade\Jenkin as JenkinsFacade;

```

```php

# Call initialize function before calling each of other functions
JenkinsFacade::initialize();

$isAvailable = JenkinsFacade::isAvailable();

$jenkinsVersion = JenkinsFacade::getJenkinsVersion();

$allJobs = JenkinsFacade::getJobs();

$allViews = JenkinsFacade::getViews();

```

For more information, see the [Jenkins API](https://wiki.jenkins-ci.org/display/JENKINS/Remote+access+API).


Coding standards
----------------

This projects follows 
[PSR-0](https://www.php-fig.org/psr/psr-0/),
[PSR-1](https://www.php-fig.org/psr/psr-1/),
[PSR-2](https://www.php-fig.org/psr/psr-2/),
[PSR-4](https://www.php-fig.org/psr/psr-4/)

TODO
----------------

* createJob function need confirm
