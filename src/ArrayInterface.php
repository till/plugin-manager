<?php
use Composer\IO\NullIO;

/**
 * This is a temporary ArrayInterface for composer.
 *
 * Still have to discuss the details with the composer people because this is
 * a little atrocious.
 *
 * What it does: writes messages to a global (!)
 * Why: because Composer\Composer does not allow the retrieval of the IOInterface yet.
 */
class ArrayInterface extends NullIO
{
    public $messages = array();

    /**
     * {@inheritDoc}
     */
    public function write($messages, $newline = true)
    {
        $this->setupGlobals();
        $GLOBALS['COMPOSER_MESSAGES'] = $messages;
    }

    /**
     * {@inheritDoc}
     */
    public function overwrite($messages, $newline = true, $size = 80)
    {
        $this->setupGlobals();
        $GLOBALS['COMPOSER_MESSAGES'] = $messages;
    }

    /**
     * @return void
     */
    protected function setupGlobals()
    {
        if (!isset($GLOBALS['COMPOSER_MESSAGES'])) {
            $GLOBALS['COMPOSER_MESSAGES'] = array();
        }
    }
}
