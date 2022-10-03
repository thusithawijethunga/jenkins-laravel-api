<?php

namespace JenkinsLaravel;

use GuzzleHttp\Client;
use JenkinsLaravel\Jenkins\Build;
use JenkinsLaravel\Jenkins\Computer;
use JenkinsLaravel\Jenkins\Executor;
use JenkinsLaravel\Jenkins\Job;
use JenkinsLaravel\Jenkins\Queue;
use JenkinsLaravel\Jenkins\TestReport;
use JenkinsLaravel\Jenkins\View;

/**
 * Description of Jenkins API V1
 * @link https://www.jenkins.io/doc/book/using/remote-access-api
 * @author Thusitha
 */
class Jenkins
{

    /**
     * @var string
     */
    private $jenkinsVersion;

    /**
     * @var bool
     */
    private $isAvailable;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * Whether or not to retrieve and send anti-CSRF crumb tokens
     * with each request
     *
     * Defaults to false for backwards compatibility
     *
     * @var boolean
     */
    private $crumbsEnabled = false;

    /**
     * The anti-CSRF crumb to use for each request
     *
     * Set when crumbs are enabled, by requesting a new crumb from Jenkins
     *
     * @var string
     */
    private $crumb;

    /**
     * The header to use for sending anti-CSRF crumbs
     *
     * Set when crumbs are enabled, by requesting a new crumb from Jenkins
     *
     * @var string
     */
    private $crumbRequestField;

    /**
     * @var GuzzleHttp\Client
     */
    private $httpClient;

    /**
     * @var array
     */
    private $jobs;

    /**
     * @var array
     */
    private $views;

    public function __construct()
    {

        $this->getBaseUrl();

        $this->httpClient = new Client(
            [
                'base_uri' => $this->baseUrl,
                'defaults' => [
                    'verify' => 'false'
                ],
                'headers' => [
                    'Accept' => 'application/json',
                ]
            ]
        );

        $this->initialize();
    }

    public function getBaseUrl(): string
    {
        $this->makeAccessUrl();

        return $this->baseUrl;
    }

    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    public function getJenkinsVersion(): string
    {
        return $this->jenkinsVersion;
    }

    /**
     * @return boolean
     */
    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function setJenkinsVersion($response): void
    {
        if ($response->hasHeader("x-jenkins")) {
            $ver = isset($response->getHeader("x-jenkins")[0]) ? $response->getHeader("x-jenkins")[0] : 0;
            $this->jenkinsVersion = $ver;
        } else {
            $this->jenkinsVersion = 0;
        }
    }

    public function setIsAvailable(bool $isAvailable): void
    {
        $this->isAvailable = $isAvailable;
    }

    public function getCrumbsEnabled(): bool
    {
        return $this->crumbsEnabled;
    }

    public function getCrumb(): string
    {
        return $this->crumb;
    }

    public function getCrumbRequestField(): string
    {
        return $this->crumbRequestField;
    }

    /**
     * Disable the use of anti-CSRF crumbs on requests
     *
     * @return void
     */
    public function disableCrumbs()
    {
        $this->crumbsEnabled = false;
    }

    public function getCrumbHeader()
    {
        return "$this->crumbRequestField: $this->crumb";
    }

    /**
     * Get the status of anti-CSRF crumbs
     *
     * @return boolean Whether or not crumbs have been enabled
     */
    public function areCrumbsEnabled()
    {
        return $this->crumbsEnabled;
    }

    public function makeAccessUrl(): string
    {

        $is_https   = config('jenkins.api.is_https');
        $domain     = config('jenkins.api.domain');
        $port       = config('jenkins.api.port');
        $user       = config('jenkins.api.user');
        $token      = config('jenkins.api.token');

        $url = $is_https ? "https://{$user}:{$token}@{$domain}:{$port}" : "http://{$user}:{$token}@{$domain}:{$port}";

        $this->baseUrl = $url;

        return $url;
    }

    public function fill(\stdClass $data = null)
    {
        foreach ($data as $key => $value) {

            if ($key == 'useCrumbs') {
                $this->crumbsEnabled = $value;
            }

            // if (property_exists($this, $key)) {
            $this->$key = $value;
            // }
        }
    }

    /**
     * Enable the use of anti-CSRF crumbs on requests
     *
     * @return void
     */
    public function enableCrumbs()
    {
        $this->crumbsEnabled = true;

        $crumbResult = $this->requestCrumb();

        if (is_null($crumbResult)) {
            $this->crumbsEnabled = false;
            return;
        }

        $this->crumb = $crumbResult->crumb;
        $this->crumbRequestField = $crumbResult->crumbRequestField;
    }

    public function requestCrumb()
    {
        $response = $this->httpClient->get('/crumbIssuer/api/json');
        if ($response->getStatusCode() == 200) {
            $crumbResult = json_decode($response->getBody()->getContents());

            if (!$crumbResult instanceof \stdClass) {
                return null;
            }

            return $crumbResult;
        } else {
            return null;
        }
    }

    public function initialize()
    {

        $response = $this->httpClient->get('/api/json?pretty=true');

        if ($response->getStatusCode() == 200) {

            $infos = json_decode($response->getBody()->getContents());

            if (!$infos instanceof \stdClass) {
                return null;
            }

            $this->setJenkinsVersion($response);
            $this->setIsAvailable(true);
            $this->fill($infos);

            if ($this->areCrumbsEnabled()) {
                $this->enableCrumbs();
            }
        } else {
            $this->setIsAvailable(false);
        }
    }

    /**
     * @param       $jobName
     * @param array $parameters
     *
     * @return bool
     * @internal param array $extraParameters
     *
     */
    public function launchJob($jobName, $parameters = array())
    {
        if (0 === count($parameters)) {
            $url = sprintf('/job/%s/build', $jobName);
        } else {
            $url = sprintf('/job/%s/buildWithParameters', $jobName);
        }

        $response = $this->httpClient->post($url, [
            'headers' => [
                $this->crumbRequestField => $this->crumb,
            ]
        ]);

        if ($response->getStatusCode() == 201) {
            return true;
        }
        return false;
    }

    /**
     * @throws \RuntimeException
     * @return array
     */
    public function getAllJobs()
    {
        $this->initialize();

        $jobs = array();
        foreach ($this->jobs as $job) {
            $jobs[$job->name] = array(
                'name' => $job->name,
                'color' => isset($job->color) ?  $job->color : '',
                'url' => $job->url,
            );
        }
        return $jobs;
    }

    /**
     * @return Jenkins\Job[]
     */
    public function getJobs()
    {
        $this->initialize();

        $jobs = array();
        foreach ($this->jobs as $job) {
            $jobs[$job->name] = $this->getJob($job->name);
        }

        return $jobs;
    }

    /**
     * @param string $jobName
     *
     * @return bool|\It369Jenkin\Jenkins\Job
     * @throws \RuntimeException
     */
    public function getJob($jobName)
    {
        $url = sprintf('/job/%s/api/json', $jobName);

        $response = $this->httpClient->get($url);
        if ($response->getStatusCode() == 200) {

            $infos = json_decode($response->getBody()->getContents());

            if (!$infos instanceof \stdClass) {
                return null;
            }

            return new Job($infos, $this);
        }
    }

    /**
     * @return Jenkins\View[]
     */
    public function getViews()
    {
        $this->initialize();

        $views = array();
        foreach ($this->views as $view) {
            $views[] = $this->getView($view->name);
        }

        return $views;
    }

    /**
     * @param string $viewName
     *
     * @return Jenkins\View
     * @throws \RuntimeException
     */
    public function getView($viewName)
    {
        $url = sprintf('/view/%s/api/json', rawurlencode($viewName));
        $response = $this->httpClient->get($url);
        if ($response->getStatusCode() == 200) {
            $infos = json_decode($response->getBody()->getContents());

            if (!$infos instanceof \stdClass) {
                return null;
            }

            return new View($infos, $this);
        }
    }

    /**
     * @param        $job
     * @param        $buildId
     * @param string $tree
     *
     * @return Jenkins\Build
     * @throws \RuntimeException
     */
    public function getBuild($job, $buildId, $tree = 'actions[parameters,parameters[name,value]],result,duration,timestamp,number,url,estimatedDuration,builtOn')
    {
        if ($tree !== null) {
            $tree = sprintf('?tree=%s', $tree);
        }

        $url = sprintf('/job/%s/%d/api/json%s', $job, $buildId, $tree);

        $response = $this->httpClient->get($url);

        if ($response->getStatusCode() == 200) {
            $infos = json_decode($response->getBody()->getContents());

            if (!$infos instanceof \stdClass) {
                return null;
            }

            return new Build($infos, $this);
        }
    }


    /**
     * @param string $computerName
     *
     * @return Jenkins\Computer
     * @throws \RuntimeException
     */
    public function getComputer($computerName)
    {
        $url = sprintf('/computer/%s/api/json', $computerName);

        $response = $this->httpClient->get($url);

        if ($response->getStatusCode() == 200) {

            $infos = json_decode($response->getBody()->getContents());

            if (!$infos instanceof \stdClass) {
                return null;
            }

            return new Computer($infos, $this);
        }
    }

    /**
     * @return Jenkins\Computer[]
     */
    public function getComputers()
    {

        $return = $this->execute('/computer/api/json');

        $infos = json_decode($return);

        if (!$infos instanceof \stdClass) {
            throw new \RuntimeException('Error during json_decode');
        }

        $computers = array();
        foreach ($infos->computer as $computer) {
            $computers[] = $this->getComputer($computer->displayName);
        }

        return $computers;
    }

    /**
     * @param string $computerName
     *
     * @return string
     */
    public function getComputerConfiguration($computerName)
    {
        $url = sprintf('/computer/%s/config.xml', $computerName);
        return $this->execute($url);
    }

    /**
     * Returns the content of a page according to the jenkins base url.
     * Useful if you use jenkins plugins that provides specific APIs.
     * (e.g. "/cloud/ec2-us-east-1/provision")
     *
     * @param string $uri
     *
     * @return string
     */
    public function execute($uri)
    {
        $url = $this->baseUrl . '/' . $uri;
        return $this->httpClient->get($url);
    }

    /**
     * @param string $jobname
     *
     * @return string
     *
     * @deprecated use getJobConfig instead
     *
     * @throws \RuntimeException
     */
    public function retrieveXmlConfigAsString($jobname)
    {
        return $this->getJobConfig($jobname);
    }

    /**
     * @return Jenkins\View|null
     */
    public function getPrimaryView()
    {
        $this->initialize();

        $primaryView = null;

        if (property_exists($this, 'primaryView')) {
            $primaryView = $this->getView($this->primaryView->name);
        }

        return $primaryView;
    }


    /**
     * @param string $job
     * @param int    $buildId
     *
     * @return null|string
     */
    public function getUrlBuild($job, $buildId)
    {
        return (null === $buildId) ?
            $this->getUrlJob($job) : sprintf('%s/job/%s/%d', $this->baseUrl, $job, $buildId);
    }


    /**
     * @param string $job
     *
     * @return string
     */
    public function getUrlJob($job)
    {
        return sprintf('%s/job/%s', $this->baseUrl, $job);
    }


    /**
     * getUrlView
     *
     * @param string $view
     *
     * @return string
     */
    public function getUrlView($view)
    {
        return sprintf('%s/view/%s', $this->baseUrl, $view);
    }



    /**
     * @param string $jobname
     *
     * @return string
     */
    public function getJobConfig($jobname)
    {
        $url = sprintf('/job/%s/config.xml',  $jobname);
        $response = $this->httpClient->get($url);
        if ($response->getStatusCode() == 200) {
            return $response->getBody()->getContents();
        }
    }

    /**
     * @param string $jobname
     * @param        $configuration
     *
     * @internal param string $document
     */
    public function setJobConfig($jobname, $configuration)
    {

        $url = sprintf('/job/%s/config.xml', $jobname);

        $headers = array('Content-Type: text/xml');
        $headers[] = 'Accept : application/xml';

        if ($this->areCrumbsEnabled()) {
            $headers[] = $this->getCrumbHeader();
        }

        $options = [
            'headers' => $headers,
            'form_params' => $configuration,
        ];

        $response = $this->httpClient->post($url,  $options);

        if ($response->getStatusCode() == 200) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string       $jobname
     * @param \DomDocument $document
     *
     * @deprecated use setJobConfig instead
     */
    public function setConfigFromDomDocument($jobname, \DomDocument $document)
    {
        $this->setJobConfig($jobname, $document->saveXML());
    }


    /**
     * TODO
     *
     * @param string $jobname
     * @param string $xmlConfiguration
     *
     * @throws \InvalidArgumentException
     */
    public function createJob($jobname, $xmlConfiguration)
    {

        $url = sprintf('/createItem?name=%s',  $jobname);

        $headers = array('Content-Type: text/xml');
        $headers[] = 'Accept : application/xml';

        if ($this->areCrumbsEnabled()) {
            $headers[] = $this->getCrumbHeader();
        }

        $user       = config('jenkins.api.user');
        $token      = config('jenkins.api.token');

        $encoding =  base64_encode($user . ":" .  $token);
        $headers[] = 'Authorization : Basic ' . $encoding;

        $options = [
            'headers' => $headers,
            'auth' => ['username',  $user],
            'form_params' => $xmlConfiguration,
        ];

        $response = $this->httpClient->post($url,  $options);
        if ($response->getStatusCode() == 200) {
            return true;
        } else {
            throw new \InvalidArgumentException(sprintf('Job %s already exists', $jobname));
        }
    }

    /**
     * @param string $jobname
     * @param string $buildNumber
     *
     * @return string
     */
    public function getConsoleTextBuild($jobname, $buildNumber)
    {
        $url = sprintf('/job/%s/%s/consoleText', $jobname, $buildNumber);
        $response = $this->httpClient->get($url);
        if ($response->getStatusCode() == 200) {
            return $response->getBody()->getContents();
        }
    }

    /**
     * @param string $jobName
     * @param        $buildId
     *
     * @return array
     * @internal param string $buildNumber
     *
     */
    public function getTestReport($jobName, $buildId)
    {
        //<Jenkins URL>/job/<Job Name>/lastCompletedBuild/testReport/api/xml
        $url = sprintf('/job/%s/%d/testReport/api/json',  $jobName, $buildId);
        $response = $this->httpClient->get($url);

        $errorMessage = sprintf(
            'Error during getting information for build %s#%d on %s',
            $jobName,
            $buildId,
            $this->baseUrl
        );

        if ($response->getStatusCode() == 200) {

            $infos = json_decode($response->getBody()->getContents());

            if (!$infos instanceof \stdClass) {
                throw new \RuntimeException($errorMessage);
            }

            return new TestReport($this, $infos, $jobName, $buildId);
        }
    }



    /**
     * @param string $computerName
     *
     * @throws \RuntimeException
     * @return void
     */
    public function deleteComputer($computerName)
    {
        $url = sprintf('/computer/%s/doDelete',  $computerName);
        $headers = array('Content-Type: text/xml');
        $headers[] = 'Accept : application/xml';
        if ($this->areCrumbsEnabled()) {
            $headers[] = $this->getCrumbHeader();
        }
        $response = $this->httpClient->post($url,  $headers);
        if ($response->getStatusCode() == 200) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * @param string $jobName
     *
     * @return void
     */
    public function deleteJob($jobName)
    {
        $url = sprintf('/job/%s/doDelete',  $jobName);
        $headers = array('Content-Type: text/xml');
        $headers[] = 'Accept : application/xml';

        if ($this->areCrumbsEnabled()) {
            $headers[] = $this->getCrumbHeader();
        }
        $response = $this->httpClient->post($url,  $headers);
        if ($response->getStatusCode() == 200) {
            return true;
        } else {
            return false;
        }
    }




    /**
     * @return Jenkins\Queue
     * @throws \RuntimeException
     */
    public function getQueue()
    {
        $url = sprintf('/queue/api/json');

        $response = $this->httpClient->get($url);

        if ($response->getStatusCode() == 200) {

            $infos = json_decode($response->getBody()->getContents());

            if (!$infos instanceof \stdClass) {
                throw new \RuntimeException('Error during json_decode');
            }

            return new Queue($infos, $this);
        }
    }

    /**
     * @param Jenkins\JobQueue $queue
     *
     * @throws \RuntimeException
     * @return void
     */
    public function cancelQueue(Jenkins\JobQueue $queue)
    {
        $url = sprintf('/queue/item/%s/cancelQueue',  $queue->getId());

        $headers = array('Content-Type: text/xml');
        $headers[] = 'Accept : application/xml';

        if ($this->areCrumbsEnabled()) {
            $headers[] = $this->getCrumbHeader();
        }
        $response = $this->httpClient->post($url,  $headers);
        if ($response->getStatusCode() == 200) {
            return true;
        } else {
            return false;
        }
    }





    /**
     * @param string $computer
     *
     * @return array
     * @throws \RuntimeException
     */
    public function getExecutors($computer = '(master)')
    {
        $this->initialize();

        $executors = array();

        for ($i = 0; $i < $this->numExecutors; $i++) {

            $url = sprintf('/computer/%s/executors/%s/api/json',  $computer, $i);

            $response = $this->httpClient->get($url);

            if ($response->getStatusCode() == 200) {

                $infos = json_decode($response->getBody()->getContents());

                if (!$infos instanceof \stdClass) {
                    throw new \RuntimeException('Error during json_decode');
                }

                return new Executor($infos, $computer, $this);
            }
        }

        return $executors;
    }


    /**
     * @param Jenkins\Executor $executor
     *
     * @throws \RuntimeException
     */
    public function stopExecutor(Jenkins\Executor $executor)
    {
        $url = sprintf(
            '/computer/%s/executors/%s/stop',
            $executor->getComputer(),
            $executor->getNumber()
        );

        $headers = array('Content-Type: text/xml');
        $headers[] = 'Accept : application/xml';

        if ($this->areCrumbsEnabled()) {
            $headers[] = $this->getCrumbHeader();
        }
        $response = $this->httpClient->post($url,  $headers);
        if ($response->getStatusCode() == 200) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * @param string $computerName
     *
     * @throws \RuntimeException
     * @return void
     */
    public function toggleOfflineComputer($computerName)
    {
        $url = sprintf('/computer/%s/toggleOffline',  $computerName);
        $headers = array('Content-Type: text/xml');
        $headers[] = 'Accept : application/xml';
        if ($this->areCrumbsEnabled()) {
            $headers[] = $this->getCrumbHeader();
        }
        $response = $this->httpClient->post($url,  $headers);
        if ($response->getStatusCode() == 200) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return \get_object_vars($this);
    }
}
