<?php

namespace JenkinsLaravel\Jenkins;

use JenkinsLaravel\Jenkins;

class Job
{

    /**
     * @var \stdClass
     */
    private $job;

    /**
     * @var Jenkins
     */
    protected $jenkins;

    /**
     * @param \stdClass $job
     * @param Jenkins   $jenkins
     */
    public function __construct($job, Jenkins $jenkins)
    {
        $this->job = $job;

        $this->setJenkins($jenkins);
    }

    /**
     * @return Build[]
     */
    public function getBuilds()
    {
        $builds = array();
        foreach ($this->job->builds as $build) {
            $builds[] = $this->getJenkinsBuild($build->number);
        }

        return $builds;
    }

    /**
     * @param int $buildId
     *
     * @return Build
     * @throws \RuntimeException
     */
    public function getJenkinsBuild($buildId)
    {
        return $this->getJenkins()->getBuild($this->getName(), $buildId);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->job->name;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->job->url;
    }

    /**
     * @return bool
     */
    public function getBuildable()
    {
        return $this->job->buildable;
    }

    /**
     * @return bool
     */
    public function getIsDisabled()
    {
        return $this->job->disabled;
    }

    /**
     * @return int
     */
    public function getNextBuildNumber()
    {
        return $this->job->nextBuildNumber;
    }

    /**
     * @return array
     */
    public function getHealthReport()
    {
        return $this->job->healthReport;
    }

    /**
     * @return string
     */
    public function getFullDisplayName()
    {
        return $this->job->fullDisplayName;
    }

    /**
     * @return array
     */
    public function getQueueItem()
    {
        $item = $this->job->queueItem;

        if (!is_null($item)) {
            return new JobQueue($item, $this->getJenkins());
        }

        return $item;
    }

    public function setQueueItem($item)
    {
        $this->job->queueItem = $item;

        return $this;
    }

    /**
     * @return array
     */
    public function getLastCompletedBuild()
    {
        if (null === $this->job->lastCompletedBuild) {
            return null;
        }

        return $this->getJenkins()->getBuild($this->getName(), $this->job->lastCompletedBuild->number);
    }

    /**
     * @return array
     */
    public function getLastFailedBuild()
    {
        if (null === $this->job->lastFailedBuild) {
            return null;
        }

        return $this->getJenkins()->getBuild($this->getName(), $this->job->lastFailedBuild->number);
    }

    /**
     * @return array
     */
    public function getLastStableBuild()
    {
        if (null === $this->job->lastStableBuild) {
            return null;
        }

        return $this->getJenkins()->getBuild($this->getName(), $this->job->lastStableBuild->number);
    }

    /**
     * @return array
     */
    public function getLastUnstableBuild()
    {

        if (null === $this->job->lastUnstableBuild) {
            return null;
        }

        return $this->getJenkins()->getBuild($this->getName(), $this->job->lastUnstableBuild->number);
    }

    /**
     * @return array
     */
    public function getLastUnsuccessfulBuild()
    {

        if (null === $this->job->lastUnsuccessfulBuild) {
            return null;
        }

        return $this->getJenkins()->getBuild($this->getName(), $this->job->lastUnsuccessfulBuild->number);
    }

    /**
     * @return array
     */
    public function getParametersDefinition()
    {
        $parameters = array();

        foreach ($this->job->actions as $action) {
            if (!property_exists($action, 'parameterDefinitions')) {
                continue;
            }

            foreach ($action->parameterDefinitions as $parameterDefinition) {
                $default = property_exists($parameterDefinition, 'defaultParameterValue') && isset($parameterDefinition->defaultParameterValue->value) ? $parameterDefinition->defaultParameterValue->value : null;
                $description = property_exists($parameterDefinition, 'description') ? $parameterDefinition->description : null;
                $choices = property_exists($parameterDefinition, 'choices') ? $parameterDefinition->choices : null;

                $parameters[$parameterDefinition->name] = array(
                    'default' => $default,
                    'choices' => $choices,
                    'description' => $description,
                );
            }
        }

        return $parameters;
    }

    /**
     * @return string
     */
    public function getColor()
    {
        return $this->job->color;
    }

    /**
     * @return string
     *
     * @throws \RuntimeException
     */
    public function retrieveXmlConfigAsString()
    {
        return $this->jenkins->retrieveXmlConfigAsString($this->getName());
    }

    /**
     * @return \DOMDocument
     */
    public function retrieveXmlConfigAsDomDocument()
    {
        $document = new \DOMDocument;
        $document->loadXML($this->retrieveXmlConfigAsString());

        return $document;
    }

    /**
     * @return Jenkins
     */
    public function getJenkins()
    {
        return $this->jenkins;
    }

    /**
     * @param Jenkins $jenkins
     *
     * @return Job
     */
    public function setJenkins(Jenkins $jenkins)
    {
        $this->jenkins = $jenkins;

        return $this;
    }

    /**
     * @return Build|null
     */
    public function getLastSuccessfulBuild()
    {
        if (null === $this->job->lastSuccessfulBuild) {
            return null;
        }

        return $this->getJenkins()->getBuild($this->getName(), $this->job->lastSuccessfulBuild->number);
    }

    /**
     * @return Build|null
     */
    public function getLastBuild()
    {
        if (null === $this->job->lastBuild) {
            return null;
        }

        return $this->getJenkins()->getBuild($this->getName(), $this->job->lastBuild->number);
    }
}
