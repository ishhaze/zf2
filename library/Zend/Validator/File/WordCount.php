<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Validator\File;

use Zend\Validator\AbstractValidator;
use Zend\Validator\Exception;

/**
 * Validator for counting all words in a file
 */
class WordCount extends AbstractValidator
{
    /**
     * @const string Error constants
     */
    const TOO_MUCH  = 'fileWordCountTooMuch';
    const TOO_LESS  = 'fileWordCountTooLess';
    const NOT_FOUND = 'fileWordCountNotFound';

    /**
     * @var array Error message templates
     */
    protected $messageTemplates = array(
        self::TOO_MUCH => "Too many words, maximum '%max%' are allowed but '%count%' were counted.",
        self::TOO_LESS => "Too less words, minimum '%min%' are expected but '%count%' were counted",
        self::NOT_FOUND => "File is not readable or does not exist",
    );

    /**
     * @var array Error message template variables
     */
    protected $messageVariables = array(
        'min'   => array('options' => 'min'),
        'max'   => array('options' => 'max'),
        'count' => 'count'
    );

    /**
     * Word count
     *
     * @var integer
     */
    protected $count;

    /**
     * Options for this validator
     *
     * @var array
     */
    protected $options = array(
        'min' => null,  // Minimum word count, if null there is no minimum word count
        'max' => null,  // Maximum word count, if null there is no maximum word count
    );

    /**
     * Sets validator options
     *
     * Min limits the word count, when used with max=null it is the maximum word count
     * It also accepts an array with the keys 'min' and 'max'
     *
     * If $options is a integer, it will be used as maximum word count
     * As Array is accepts the following keys:
     * 'min': Minimum word count
     * 'max': Maximum word count
     *
     * @param  integer|array|\Traversable $options Options for the adapter
     */
    public function __construct($options = null)
    {
        if (is_string($options) || is_numeric($options)) {
            $options = array('max' => $options);
        }

        if (1 < func_num_args()) {
            $options['min'] = func_get_arg(0);
            $options['max'] = func_get_arg(1);
        }

        parent::__construct($options);
    }

    /**
     * Returns the minimum word count
     *
     * @return integer
     */
    public function getMin()
    {
        return $this->options['min'];
    }

    /**
     * Sets the minimum word count
     *
     * @param  integer|array $min The minimum word count
     * @return WordCount Provides a fluent interface
     * @throws Exception\InvalidArgumentException When min is greater than max
     */
    public function setMin($min)
    {
        if (is_array($min) and isset($min['min'])) {
            $min = $min['min'];
        }

        if (!is_string($min) and !is_numeric($min)) {
            throw new Exception\InvalidArgumentException('Invalid options to validator provided');
        }

        $min = (integer) $min;
        if (($this->getMax() !== null) && ($min > $this->getMax())) {
            throw new Exception\InvalidArgumentException(
                "The minimum must be less than or equal to the maximum word count, but $min >"
                . " {$this->getMax()}");
        }

        $this->options['min'] = $min;
        return $this;
    }

    /**
     * Returns the maximum word count
     *
     * @return integer
     */
    public function getMax()
    {
        return $this->options['max'];
    }

    /**
     * Sets the maximum file count
     *
     * @param  integer|array $max The maximum word count
     * @return WordCount Provides a fluent interface
     * @throws Exception\InvalidArgumentException When max is smaller than min
     */
    public function setMax($max)
    {
        if (is_array($max) and isset($max['max'])) {
            $max = $max['max'];
        }

        if (!is_string($max) and !is_numeric($max)) {
            throw new Exception\InvalidArgumentException('Invalid options to validator provided');
        }

        $max = (integer) $max;
        if (($this->getMin() !== null) && ($max < $this->getMin())) {
            throw new Exception\InvalidArgumentException(
                "The maximum must be greater than or equal to the minimum word count, but "
                . "$max < {$this->getMin()}");
        }

        $this->options['max'] = $max;
        return $this;
    }

    /**
     * Returns true if and only if the counted words are at least min and
     * not bigger than max (when max is not null).
     *
     * @param  string $value|array Filename to check for word count
     * @return bool
     */
    public function isValid($value)
    {
        if (is_array($value)) {
            if (!isset($value['tmp_name']) || !isset($value['name'])) {
                throw new Exception\InvalidArgumentException(
                    'Value array must be in $_FILES format'
                );
            }
            $file     = $value['tmp_name'];
            $filename = $value['name'];
        } else {
            $file     = $value;
            $filename = basename($file);
        }
        $this->setValue($filename);

        // Is file readable ?
        if (false === stream_resolve_include_path($file)) {
            $this->error(self::NOT_FOUND);
            return false;
        }

        $content     = file_get_contents($file);
        $this->count = str_word_count($content);
        if (($this->getMax() !== null) && ($this->count > $this->getMax())) {
            $this->error(self::TOO_MUCH);
            return false;
        }

        if (($this->getMin() !== null) && ($this->count < $this->getMin())) {
            $this->error(self::TOO_LESS);
            return false;
        }

        return true;
    }
}
