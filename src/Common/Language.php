<?php

namespace Common;

use Frontend\Core\Language\Language as FrontendLanguage;
use InvalidArgumentException;
use Symfony\Component\Translation\IdentityTranslator;

/**
 * This class will make it possible to have 1 function to get the correct language class.
 * This is useful for when you want to use the same code in the Back- and Frontend.
 * For instance in a trait.
 *
 * @TODO switch our translation system to completely work with symfony instead of overriding methods to make it run
 * on our system
 */
final class Language extends IdentityTranslator
{
    /**
     * @return string
     */
    public static function get()
    {
        $application = APPLICATION;

        if ($application === 'Console') {
            $application = 'Backend';
        }

        return $application . '\Core\Language\Language';
    }

    /**
     * @param $function
     * @param $parameters
     *
     * @throws InvalidArgumentException when the function can't be called
     *
     * @return mixed
     */
    public static function callLanguageFunction($function, $parameters = [])
    {
        $languageClass = self::get();
        $callback = [$languageClass, $function];
        if (!method_exists($languageClass, $function) || !is_callable($callback)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The function %s::%s does not exist',
                    $languageClass,
                    $function
                )
            );
        }

        return call_user_func_array($callback, $parameters);
    }

    /**
     * Get a label.
     * This only implements the key because the other parameters differ between the front- and backend.
     *
     * @param $key
     *
     * @return string
     */
    public static function lbl($key)
    {
        return self::callLanguageFunction('lbl', [$key]);
    }

    /**
     * Get an error.
     * This only implements the key because the other parameters differ between the front- and backend.
     *
     * @param $key
     *
     * @return string
     */
    public static function err($key)
    {
        return self::callLanguageFunction('err', [$key]);
    }

    /**
     * Get an action.
     *
     *
     * @param $key
     *
     * @throws InvalidArgumentException when used in the backend.
     *
     * @return string
     */
    public static function act($key)
    {
        return self::callLanguageFunction('act', [$key]);
    }

    /**
     * Get a message.
     * This only implements the key because the other parameters differ between the front- and backend.
     *
     * @param $key
     *
     * @return string
     */
    public static function msg($key)
    {
        return self::callLanguageFunction('msg', [$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
        if (strpos($id, '.') === false) {
            parent::trans($id, $parameters, $domain, $locale);
        }
        list($action, $string) = explode('.', $id, 2);

        $possibleActions = ['lbl', 'err', 'msg'];
        if (self::get() === FrontendLanguage::class) {
            $possibleActions[] = 'act';
        }

        if (!in_array($action, $possibleActions)) {
            parent::trans($id, $parameters, $domain, $locale);
        }

        $translatedString = self::$action($string);

        // we couldn't translate it, let the parent have a go
        if ($translatedString === '{$' . $action . $string . '}') {
            return parent::trans($id, $parameters, $domain, $locale);
        }

        return $translatedString;
    }
}
