<?php

namespace JenkinsLaravel\Jenkins;

use JenkinsLaravel\Jenkins;

class Factory
{

    /**
     * @param string $url
     *
     * @return Jenkins
     */
    public function build($url)
    {
        return new Jenkins($url);
    }
}
