<?php

namespace A11ySpeechSynthesis;

abstract class Plugin_Base {

	/**
	 * @var array
	 */
	public $config = array();

	/**
	 * @var string
	 */
	public $slug;

	/**
	 * @var string
	 */
	public $dir_path;

	/**
	 * @var string
	 */
	public $dir_url;

	/**
	 * @var string
	 */
	protected $autoload_class_dir = 'php';

	function __construct() {
		$location = $this->locate_plugin();
		$this->slug = $location['dir_basename'];
		$this->dir_path = $location['dir_path'];
		$this->dir_url = $location['dir_url'];
		spl_autoload_register( array( $this, 'autoload' ) );
	}

	/**
	 * @return \ReflectionObject
	 */
	function get_object_reflection() {
		static $reflection;
		if ( empty( $reflection ) ) {
			$reflection = new \ReflectionObject( $this );
		}
		return $reflection;
	}

	/**
	 * Autoload for classes that are in the same namespace as $this, and also for
	 * classes in the Twig library.
	 *
	 * @param  string $class
	 * @return void
	 */
	function autoload( $class ) {
		if ( ! preg_match( '/^(?P<namespace>.+)\\\\(?P<class>[^\\\\]+)$/', $class, $matches ) ) {
			return;
		}
		if ( $this->get_object_reflection()->getNamespaceName() !== $matches['namespace'] ) {
			return;
		}
		$class_name = $matches['class'];

		$class_path = \trailingslashit( $this->dir_path );
		if ( $this->autoload_class_dir ) {
			$class_path .= \trailingslashit( $this->autoload_class_dir );
		}
		$class_path .= sprintf( 'class-%s.php', strtolower( str_replace( '_', '-', $class_name ) ) );
		if ( is_readable( $class_path ) ) {
			require_once $class_path;
		}
	}

	/**
	 * Version of plugin_dir_url() which works for plugins installed in the plugins directory,
	 * and for plugins bundled with themes.
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function locate_plugin() {
		$reflection = new \ReflectionObject( $this );
		$file_name = $reflection->getFileName();
		if ( '/' !== \DIRECTORY_SEPARATOR ) {
			$file_name = str_replace( \DIRECTORY_SEPARATOR, '/', $file_name ); // Windows compat
		}
		$plugin_dir = preg_replace( '#(.*plugins[^/]*/[^/]+)(/.*)?#', '$1', $file_name, 1, $count );
		if ( 0 === $count ) {
			throw new \Exception( "Class not located within a directory tree containing 'plugins': $file_name" );
		}

		// Make sure that we can reliably get the relative path inside of the content directory
		$content_dir = trailingslashit( WP_CONTENT_DIR );
		if ( '/' !== \DIRECTORY_SEPARATOR ) {
			$content_dir = str_replace( \DIRECTORY_SEPARATOR, '/', $content_dir ); // Windows compat
		}
		if ( 0 !== strpos( $plugin_dir, $content_dir ) ) {
			throw new \Exception( 'Plugin dir is not inside of WP_CONTENT_DIR' );
		}
		$content_sub_path = substr( $plugin_dir, strlen( $content_dir ) );
		$dir_url = content_url( trailingslashit( $content_sub_path ) );
		$dir_path = $plugin_dir;
		$dir_basename = basename( $plugin_dir );
		return compact( 'dir_url', 'dir_path', 'dir_basename' );
	}

}
