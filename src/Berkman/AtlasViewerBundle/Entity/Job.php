<?php

namespace Berkman\AtlasViewerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Berkman\AtlasViewerBundle\Entity\Job
 */
class Job
{
    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var string $command
     */
    private $command;

    /**
     * @var integer $timeout
     */
    private $timeout;

    public function __construct($command, $timeout = 60)
    {
        $this->setCommand($command);
        $this->setTimeout($timeout);
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set command
     *
     * @param string $command
     */
    public function setCommand($command)
    {
        $this->command = $command;
    }

    /**
     * Get command
     *
     * @return string 
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Set timeout
     *
     * @param integer $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * Get timeout
     *
     * @return integer 
     */
    public function getTimeout()
    {
        return $this->timeout;
    }
    /**
     * @var string $alert_email
     */
    private $alert_email;


    /**
     * Set alert_email
     *
     * @param string $alertEmail
     */
    public function setAlertEmail($alertEmail)
    {
        $this->alert_email = $alertEmail;
    }

    /**
     * Get alert_email
     *
     * @return string 
     */
    public function getAlertEmail()
    {
        return $this->alert_email;
    }
}