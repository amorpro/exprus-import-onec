<?php
/**
 * Created by PhpStorm.
 * User: AmorPro
 * Date: 08.07.2018
 * Time: 15:06
 */

namespace Exchange\Import\OneC\Plugin\Trace;

use Plugin\Plugin;

class MySQL extends Plugin
{
    protected $uniqueId;

    /**
     * Trace constructor.
     */
    public function __construct()
    {
        $this->uniqueId = time();
    }

    public function onExpertiseSaved(\Expertise $expertise)
    {
        $this->_trace('info', 'Expertise saved', [
            'expertise_id'     => $expertise->GetId(),
            'expertise_number' => $expertise->GetReasonNumber()
        ]);
    }

    protected function _trace($status, $message, $context = null)
    {
        if (null !== $context && !is_array($context)) {
            $context = [$context];
        }

        $importLog = (new \ImportLog())
            ->setProcess($this->uniqueId)
            ->setStatus($status)
            ->setMessage($message)
            ->setContext($context)
        ;

        \ImportLogMapper::I()->Save($importLog);
    }

    public function onPeriodToProcessDetected(\DatePeriod $period)
    {
        $this->_trace('info', 'Period to process detected', [
            $period->getStartDate()->format('Y-m-d H'),
            $period->getEndDate()->format('Y-m-d H')
        ]);
    }

    public function onNewExpertise(\Expertise $expertise)
    {
        $this->_trace('info', 'New expertise', get_class($expertise));
    }

    public function onExpertiseFound(\Expertise $expertise)
    {
        $this->_trace('info', 'Expertise found', [
            'type'          => get_class($expertise),
            'expertise_id'  => $expertise->GetId(),
            'reason_number' => $expertise->GetReasonNumber()
        ]);
    }

    public function onProcessEntry($row)
    {
        $this->_trace('info', 'Process Entry', $row);
    }

    public function on($event, $arguments)
    {
        if (!parent::on($event, $arguments)) {
            $this->_trace(
                $this->_detectFromEvent($event),
                $this->_getCamelCaseToMessage($event),
                $arguments
            );
        }
    }

    protected function _detectFromEvent($event)
    {
        return preg_match('/(Failed|Error|Exception|Fail)/', $event) ? 'error' : 'info';
    }

    protected function _getCamelCaseToMessage($str)
    {
        return ucfirst(strtolower(preg_replace('/(?<!^)[A-Z]/', ' $0', $str)));
    }


}