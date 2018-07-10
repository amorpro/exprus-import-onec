<?php
/**
 * Created by PhpStorm.
 * User: AmorPro
 * Date: 08.07.2018
 * Time: 12:39
 */
namespace Exchange\Import\OneC\Exception;

use Exchange\Import\OneC\Dto;

class ExpertiseTypeChangingForbidden extends \Exception
{
    /**
     * ExpertiseTypeChangingForbidden constructor.
     * @param Dto $entry
     * @param \Expertise $expertise
     */
    public function __construct(Dto $entry, \Expertise $expertise)
    {
        $message = sprintf('Attempt to change the expertise type from %s to %s is forbidden',
            ($expertise instanceof \IndividualExpertise ? '"Personal"' : '"Legal entity"'),
            ($entry->isIndividual() ? '"Personal"' : '"Legal entity"')
        );

        parent::__construct($message);
    }


}