<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2017-2021
 */


class TestHelperJapi
{
	private static $aimeos;
	private static $context = [];


	public static function bootstrap()
	{
		$aimeos = self::getAimeos();
		\Aimeos\MShop::cache( false );
		\Aimeos\Controller\Frontend::cache( false );
	}


	public static function getContext( $site = 'unittest' )
	{
		if( !isset( self::$context[$site] ) ) {
			self::$context[$site] = self::createContext( $site );
			self::$context[$site]->setView( self::getView( self::$context[$site]->getConfig() ) );
		}

		return clone self::$context[$site];
	}


	public static function getView( \Aimeos\MW\Config\Iface $config )
	{
		$view = new \Aimeos\MW\View\Standard( self::getTemplatePaths() );

		$trans = new \Aimeos\MW\Translation\None( 'en' );
		$helper = new \Aimeos\MW\View\Helper\Translate\Standard( $view, $trans );
		$view->addHelper( 'translate', $helper );

		$helper = new \Aimeos\MW\View\Helper\Url\Standard( $view, 'baseurl' );
		$view->addHelper( 'url', $helper );

		$helper = new \Aimeos\MW\View\Helper\Number\Standard( $view, '.', '' );
		$view->addHelper( 'number', $helper );

		$helper = new \Aimeos\MW\View\Helper\Date\Standard( $view, 'Y-m-d' );
		$view->addHelper( 'date', $helper );

		$helper = new \Aimeos\MW\View\Helper\Config\Standard( $view, $config );
		$view->addHelper( 'config', $helper );

		$psr17Factory = new \Nyholm\Psr7\Factory\Psr17Factory();
		$helper = new \Aimeos\MW\View\Helper\Request\Standard( $view, $psr17Factory->createServerRequest( 'GET', 'https://aimeos.org' ) );
		$view->addHelper( 'request', $helper );

		$helper = new \Aimeos\MW\View\Helper\Response\Standard( $view, $psr17Factory->createResponse() );
		$view->addHelper( 'response', $helper );

		return $view;
	}


	public static function getTemplatePaths()
	{
		return self::getAimeos()->getCustomPaths( 'client/jsonapi/templates' );
	}


	private static function getAimeos()
	{
		if( !isset( self::$aimeos ) )
		{
			require_once 'Bootstrap.php';
			spl_autoload_register( 'Aimeos\\Bootstrap::autoload' );

			$extdir = dirname( dirname( dirname( dirname( __DIR__ ) ) ) );
			self::$aimeos = new \Aimeos\Bootstrap( array( $extdir ), true );
		}

		return self::$aimeos;
	}


	private static function createContext( $site )
	{
		$ctx = new \Aimeos\MShop\Context\Item\Standard();
		$aimeos = self::getAimeos();


		$paths = $aimeos->getConfigPaths();
		$paths[] = __DIR__ . DIRECTORY_SEPARATOR . 'config';
		$file = __DIR__ . DIRECTORY_SEPARATOR . 'confdoc.ser';

		$conf = new \Aimeos\MW\Config\PHPArray( [], $paths );
		$conf = new \Aimeos\MW\Config\Decorator\Memory( $conf );
		$conf = new \Aimeos\MW\Config\Decorator\Documentor( $conf, $file );
		$ctx->setConfig( $conf );


		$dbm = new \Aimeos\MW\DB\Manager\DBAL( $conf );
		$ctx->setDatabaseManager( $dbm );


		$mq = new \Aimeos\MW\MQueue\Manager\Standard( $conf );
		$ctx->setMessageQueueManager( $mq );


		$logger = new \Aimeos\MW\Logger\File( $site . '.log', \Aimeos\MW\Logger\Base::DEBUG );
		$ctx->setLogger( $logger );


		$cache = new \Aimeos\MW\Cache\None();
		$ctx->setCache( $cache );


		$i18n = new \Aimeos\MW\Translation\None( 'en' );
		$ctx->setI18n( array( 'en' => $i18n ) );


		$session = new \Aimeos\MW\Session\None();
		$ctx->setSession( $session );


		$localeManager = \Aimeos\MShop\Locale\Manager\Factory::create( $ctx );
		$locale = $localeManager->bootstrap( $site, '', '', false );
		$ctx->setLocale( $locale );


		$ctx->setEditor( 'ai-client-jsonapi:client/jsonapi' );

		return $ctx;
	}
}
