<?php
/**
 * Created by PhpStorm.
 * User: AmorPro
 * Date: 08.07.2018
 * Time: 11:06
 */
namespace Exchange\Import\OneC;

use Exchange\Import\OneC\Exception\DtoValidationFailed;
use Exchange\Import\OneC\Exception\ExpertiseTypeChangingForbidden;
use Exchange\Import\OneC\Exception\ImportingDataNotExist;
use Plugin\Pluggable;

/**
 * Class Service
 * @package Exchange\Import\OneC
 */
class Service
{

    use Pluggable;

    const CACHE_KEY_LAST_DATETIME     = 'import:last_processed_date';
    const DEFAULT_DAYS_TO_PROCESS     = 3;
    const IMPORT_FILE_PERIOD_INTERVAL = 'PT1H';

    /**
     * @throws \Exception
     */
    public function process()
    {
        $this->_triggerProcessStarted();

        $lastProcessedDateHour = $this->_getLastProcessedDateHour();                $this->_triggerContinueFromDate($lastProcessedDateHour);
        $periodInHours         = $this->_getPeriodInHours($lastProcessedDateHour);  $this->_triggerPeriodToProcessDetected( $periodInHours );
        foreach($periodInHours as $dateHour){
            try {
                $this->_processDateHour($dateHour);
                $this->_setLastProcessedDateHour($dateHour);
            }catch (ImportingDataNotExist $e){}
        }

        $this->_triggerProcessFinished();
    }

    /**
     * @return \League\Flysystem\Filesystem
     */
    private function _getFtp()
    {
        return \Factory::getFtpFor1C();
    }

    /**
     * @param $lastProcessedDateHour
     * @return \DatePeriod
     * @throws \Exception
     */
    protected function _getPeriodInHours($lastProcessedDateHour)
    {
        $from     = new \DateTime($lastProcessedDateHour);
        $to       = new \DateTime();
        $interval = new \DateInterval(self::IMPORT_FILE_PERIOD_INTERVAL);

        return new \DatePeriod($from, $interval, $to);
    }

    /**
     * @param \DateTime $dateHour
     * @return string
     */
    protected function _formatFilePath(\DateTime $dateHour)
    {
        return sprintf('/%s.csv', $dateHour->format('d_m_Y_H'));
    }

    /**
     * @param $dateHour
     * @throws ImportingDataNotExist
     * @throws \Exception
     */
    protected function _processDateHour($dateHour)
    {
        $filePath = $this->_formatFilePath($dateHour);
        if ($this->_getFtp()->has($filePath)) {             $this->_triggerFileDetected($filePath);
            $csv  = $this->_getFtp()->read($filePath);
            $rows = $this->_parseCsv($csv);                 $this->_triggerCsvParsed($rows);
            foreach ($rows as $row) {
                try {
                    $this->_processExpertise($row);
                } catch (DtoValidationFailed $e) {
                    $this->_triggerEntryValidationFailed($e->getMessage());
                } catch (ExpertiseTypeChangingForbidden $e) {
                    $this->_triggerExpertiseTypeChangingFail($e->getMessage());
                } catch (\Exception $e) {
                    $this->_triggerUnexpectedFail($e->getMessage());
                    throw $e;
                }
            }
        } else {
            $this->_triggerFileDetectionFailed($filePath);
            throw new ImportingDataNotExist('Missed file "' . $filePath . '" with importing data');
        }

    }

    /**
     * @param $content
     * @return Dto[]
     */
    protected function _parseCsv($content)
    {
        $csv = new \parseCSV();
        $csv->delimiter = '|';
        $csv->enclosure = '';
        $csv->parse($content);

        return $csv->data;
    }

    /**
     * @param $row
     * @throws DtoValidationFailed
     * @throws ExpertiseTypeChangingForbidden
     */
    protected function _processExpertise($row)
    {
        $this->_triggerProcessEntry($row);

        $entry = new Dto($row);

        $expertise = \ExpertiseMapper::I()->findByReasonNumber($entry->getReasonNumber());
        if(!$expertise instanceof \Expertise){
            $expertise = $entry->isIndividual() ?
                \Expertise::createIndividual():
                \Expertise::createLegalEntity();

            $this->_triggerNewExpertise($expertise);
        }else{
            $this->_triggerExpertiseFound($expertise);

            $isAttemptToChangeTheType =
                ($entry->isIndividual() && $expertise instanceof \LegalEntityExpertise) ||
                (!$entry->isIndividual() && $expertise instanceof \IndividualExpertise);

            if($isAttemptToChangeTheType){
                throw new ExpertiseTypeChangingForbidden($entry, $expertise);
            }
        }

        $expertise
            ->SetNameId($entry->getNameId())
            ->SetReasonNumber($entry->getReasonNumber())
            ->SetReasonDate($entry->getReasonDate())
            ->SetPrice($entry->getPrice())
         ;


        $client = $entry->isIndividual() ? $expertise->GetIndividual() : $expertise->GetLegalEntity();
        $client
            ->SetName($entry->getMemberName())
            ->SetPayActual($entry->getPaidAmount())
            ->SetPaid($entry->getPaid());

        \ExpertiseMapper::I()->Save($expertise);

        $this->_triggerExpertiseSaved($expertise);

    }

    protected function _getLastProcessedDateHour()
    {
        $last = \Factory::getCache()->get(self::CACHE_KEY_LAST_DATETIME, \Cache::LIFETIME_UNLIM);
        return $last ?: strftime('%Y-%m-%d %H:00', strtotime('-' . self::DEFAULT_DAYS_TO_PROCESS . ' days'));
    }

    protected function _setLastProcessedDateHour(\DateTime $dateHour)
    {
        $lastProcessedDate = $dateHour->format('Y-m-d H:00');

        \Factory::getCache()->set(self::CACHE_KEY_LAST_DATETIME, $lastProcessedDate) ?
            $this->_triggerLastProcessedDateChanged($lastProcessedDate):
            $this->_triggerLastProcessedDateChangingFailed($lastProcessedDate);
    }


}