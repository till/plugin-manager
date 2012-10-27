<?php
use Composer\IO\NullIO;

class ArrayInterface extends NullIO
{
    public $messages = array();

    /**
     * {@inheritDoc}
     */
    public function write($messages, $newline = true)
    {
        $GLOBALS['COMPOSER_MESSAGES'] = $messages;
    }

    /**
     * {@inheritDoc}
     */
    public function overwrite($messages, $newline = true, $size = 80)
    {
        $GLOBALS['COMPOSER_MESSAGES'] = $messages;
    }

    protected function setupGlobals()
    {
        if (!isset($GLOBALS['COMPOSER_MESSAGES'])) {
            $GLOBALS['COMPOSER_MESSAGES'] = array();
        }
    }
}
