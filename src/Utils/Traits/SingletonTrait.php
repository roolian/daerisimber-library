<?php
namespace Daerisimber\Utils\Traits;



/**
 * Singleton pattern
 * @see https://www.php.net/manual/fr/language.oop5.traits.php
 *
 * @since 1.0.0
 */
trait SingletonTrait {

    /**
     * Singleton pattern - single instance
     */
    private static $instance;

    /**
     * Gets the instance.
     * Ensures only one instance can be loaded.
     *
     * @since 1.0.0
     * @return the singleton instance
     */
    protected function __construct() {}

    final public static function instance() {

        if ( is_null( static::$instance ) ) {
            static::$instance = new static();
            static::$instance->init();
        }

        return static::$instance;
    }

    /**
     * Cloning instances is forbidden due to singleton pattern.
     *
     * @since 1.0.0
     */
    public function __clone() {}

    /**
     * Unserializing instances is forbidden due to singleton pattern.
     *
     * @since 1.0.0
     */
    public function __wakeup() {}

    /**
     * Default init method called when instance created
     * This method can be overridden if needed.
     *
     * @since 1.0.0
     * @access protected
     */
    public function init() {}


    public static function load() {
        return self::instance();
    }
}
