<?php

return [

    /**
     * -----------------------------------------
     *  API Configuration
     * -----------------------------------------
     * This configure sevaral API related
     * Configurations.
     */
    'api'   =>  [

        'is_https'  => env('JENKINS_URL_HTTPS', false),

        /**
         * DOMAIN
         * --------------------------------------
         * This Configures the Endpoints of your
         * hosted Jenkins server. domain name
         *
         * EXAMPLE: localhost
         */

        'domain'  => env('JENKINS_DOMAIN', 'localhost'),

        /**
         * PORT
         * --------------------------------------
         * The port for jenkins instance
         */

        'port'  => env('JENKINS_PORT', 8080),

        'user'  => env('JENKINS_USER', 'admin'),

        'token'  => env('JENKINS_TOKEN', 'token_data'),

    ],

];
