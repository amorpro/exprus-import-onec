<?php
/**
 * Created by PhpStorm.
 * User: AmorPro
 * Date: 08.07.2018
 * Time: 11:44
 */

namespace Exchange\Import\OneC;


use Exchange\Import\OneC\Exception\DtoValidationFailed;

class Dto
{
    protected static $expectedFields = [
        'expertise_name',
        'expertise_reason_number',
        'expertise_reason_date',
        'expertise_member_name',
        'expertise_price',
        'expertise_paid_amount',
        'expertise_paid',
        'expertise_type'
    ];

    private static $expertiseNames;

    protected
                     $name,
                     $reasonNumber,
                     $reasonDate,
                     $memberName,//
                     $price,
                     $paidAmount,
                     $paid,
                     $type;

    /**
     * Dto constructor.
     * @param $options
     * @throws DtoValidationFailed
     */
    public function __construct($options)
    {
        if (!$this->_validateFields($options)) {
            throw new DtoValidationFailed('Some fields is missed');
        }

        if (!$this->_validateName($options)) {
            throw new DtoValidationFailed('Name "' . $options[self::$expectedFields[0]] . '" is not found in the Dictionary');
        }

        if (!$this->_validExpertiseType($options)) {
            throw new DtoValidationFailed('Expertise type could be only 1 or 2 but "' . $options[self::$expectedFields[7]] . '" found');
        }

        $this->name         = $this->_getString($options[self::$expectedFields[0]]);
        $this->reasonNumber = $this->_getString($options[self::$expectedFields[1]]);
        $this->reasonDate   = $this->_getDateTime($options[self::$expectedFields[2]]);
        $this->memberName   = $this->_getString($options[self::$expectedFields[3]]);
        $this->price        = $this->_getInt($options[self::$expectedFields[4]]);
        $this->paidAmount   = $this->_getInt($options[self::$expectedFields[5]]);
        $this->paid         = $this->_getDateTime($options[self::$expectedFields[6]]);
        $this->type         = $this->_getInt($options[self::$expectedFields[7]]);

    }

    /**
     * @param $options
     * @return bool
     */
    protected function _validateFields($options)
    {
        return !count(array_diff(self::$expectedFields, array_keys($options)));
    }

    private function _validateName($options)
    {
        return (boolean)$this->_getNameId($options[self::$expectedFields[0]]);
    }

    private function _getNameId($name)
    {
        if (!self::$expertiseNames) {
            $namesDictionaryId    = \Factory::GetConfig('expertise_names_dictionary_id');
            self::$expertiseNames = \DictionaryMapper::I()->FindDictionaryItemsForInterfaces($namesDictionaryId);
        }

        return array_search($name, self::$expertiseNames, true);
    }

    private function _validExpertiseType($options)
    {
        return in_array((int)$options[self::$expectedFields[7]], [\Expertise::TYPE_INDIVIDUAL, \Expertise::TYPE_LEGAL_ENTITY], true);
    }

    private function _getString($v)
    {
        return (string)$v;
    }

    /**
     * @param $v
     * @return \DateTime
     */
    protected function _getDateTime($v)
    {
        $date    = new \DateTime($v);
        $isValid = (new \DateTime())->diff(new \DateTime($v))->days < 365 * 40; // Some time 1C returns the date in format 01.01.0001 00:00

        return $isValid ? $date : null;
    }

    /**
     * @param $v
     * @return int
     */
    private function _getInt($v)
    {
        return (int)str_replace(' ', '', $v); // 1C returns the int values separated by space
    }

    /**
     * @return int
     */
    public function getReasonNumber()
    {
        return $this->reasonNumber;
    }

    /**
     * @return \DateTime
     */
    public function getReasonDate()
    {
        return $this->reasonDate;
    }

    /**
     * @return string
     */
    public function getMemberName()
    {
        return $this->memberName;
    }

    /**
     * @return int
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @return int
     */
    public function getPaidAmount()
    {
        return $this->paidAmount;
    }

    /**
     * @return int
     */
    public function getNameId()
    {
        return $this->_getNameId($this->getName());
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return \DateTime
     */
    public function getPaid()
    {
        return $this->paid;
    }

    public function isIndividual()
    {
        return $this->getType() === \Expertise::TYPES_INDIVIDUAL;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }
}