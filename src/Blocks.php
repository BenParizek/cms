<?php
namespace Blocks;

/**
 *
 */
class Blocks extends \Yii
{
	private static $_storedBlocksInfo;
	private static $_packages;
	private static $_siteUrl;
	private static $_logger;

	/**
	 * Returns the Blocks version number, as defined by the BLOCKS_VERSION constant.
	 *
	 * @static
	 * @return string
	 */
	public static function getVersion()
	{
		return BLOCKS_VERSION;
	}

	/**
	 * Returns the Blocks version number, as defined in the blx_info table.
	 *
	 * @static
	 * @return string
	 */
	public static function getStoredVersion()
	{
		$storedBlocksInfo = static::_getStoredInfo();
		return $storedBlocksInfo ? $storedBlocksInfo->version : null;
	}

	/**
	 * Returns the Blocks build number, as defined by the BLOCKS_BUILD constant.
	 *
	 * @static
	 * @return string
	 */
	public static function getBuild()
	{
		return BLOCKS_BUILD;
	}

	/**
	 *
	 * Returns the Blocks build number, as defined in the blx_info table.
	 *
	 * @static
	 * @return string
	 */
	public static function getStoredBuild()
	{
		$storedBlocksInfo = static::_getStoredInfo();
		return $storedBlocksInfo ? $storedBlocksInfo->build : null;
	}

	/**
	 * Returns the Blocks release date, as defined by the BLOCKS_RELEASE_DATE constant.
	 *
	 * @static
	 * @return string
	 */
	public static function getReleaseDate()
	{
		return BLOCKS_RELEASE_DATE;
	}

	/**
	 * Returns the Blocks release date, as defined in the blx_info table.
	 *
	 * @static
	 * @return string
	 */
	public static function getStoredReleaseDate()
	{
		$storedBlocksInfo = static::_getStoredInfo();
		return $storedBlocksInfo ? $storedBlocksInfo->releaseDate : null;
	}

	/**
	 * Returns the packages in this Blocks install, as defined by the BLOCKS_PACKAGES constant.
	 *
	 * @static
	 * @return array|null
	 */
	public static function getPackages()
	{
		if (!isset(static::$_packages))
		{
			static::$_packages = array_filter(ArrayHelper::stringToArray(BLOCKS_PACKAGES));
			sort(static::$_packages);
		}

		return static::$_packages;
	}

	/**
	 * Returns the packages in this Blocks install, as defined in the blx_info table.
	 *
	 * @static
	 * @return array|null
	 */
	public static function getStoredPackages()
	{
		$storedBlocksInfo = static::_getStoredInfo();

		if ($storedBlocksInfo)
		{
			$storedPackages = array_filter(ArrayHelper::stringToArray($storedBlocksInfo->packages));
			sort($storedPackages);
			return $storedPackages;
		}

		return null;
	}

	/**
	 * Invalidates the cached Info so it is pulled fresh the next time it is needed.
	 */
	public static function invalidateCachedInfo()
	{
		static::$_storedBlocksInfo = null;
	}

	/**
	 * Returns the minimum required build number, as defined in the BLOCKS_MIN_BUILD_REQUIRED constant.
	 *
	 * @return mixed
	 */
	public static function getMinRequiredBuild()
	{
		return BLOCKS_MIN_BUILD_REQUIRED;
	}

	/**
	 * Returns whether a package is included in this Blocks build.
	 *
	 * @param $packageName
	 * @return bool
	 */
	public static function hasPackage($packageName)
	{
		// If Blocks is already installed, the check the file system AND database to determine if a package is installed or not.
		if (blx()->isInstalled())
		{
			$storedPackages = static::getStoredPackages() == null ? array() : static::getStoredPackages();
			if (in_array($packageName, $storedPackages) && in_array($packageName, static::getPackages()))
			{
				return true;
			}
		}
		else
		{
			// Not installed, so only check the file system.
			if (in_array($packageName, static::getPackages()))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns the site name.
	 *
	 * @static
	 * @return string
	 */
	public static function getSiteName()
	{
		$storedBlocksInfo = static::_getStoredInfo();
		return $storedBlocksInfo ? $storedBlocksInfo->siteName : null;
	}

	/**
	 * Returns the site URL.
	 *
	 * @static
	 * @return string
	 */
	public static function getSiteUrl()
	{
		if (!isset(static::$_siteUrl))
		{
			$storedBlocksInfo = static::_getStoredInfo();
			if ($storedBlocksInfo)
			{
				$port = blx()->request->getPort();

				if ($port == 80)
				{
					$port = '';
				}
				else
				{
					$port = ':'.$port;
				}

				static::$_siteUrl = rtrim($storedBlocksInfo->siteUrl, '/').$port;
			}
			else
			{
				static::$_siteUrl = '';
			}
		}

		return static::$_siteUrl;
	}

	/**
	 * Returns the site language.
	 *
	 * @static
	 * @return string
	 */
	public static function getLanguage()
	{
		$storedBlocksInfo = static::_getStoredInfo();
		return $storedBlocksInfo ? $storedBlocksInfo->language : null;
	}

	/**
	 * Returns the license key.
	 *
	 * @static
	 * @return string
	 */
	public static function getLicenseKey()
	{
		$storedBlocksInfo = static::_getStoredInfo();
		return $storedBlocksInfo ? $storedBlocksInfo->licenseKey : null;
	}

	/**
	 * Returns whether the system is on.
	 *
	 * @static
	 * @return bool
	 */
	public static function isSystemOn()
	{
		$storedBlocksInfo = static::_getStoredInfo();
		return $storedBlocksInfo ? $storedBlocksInfo->on == 1 : false;
	}

	/**
	 * Returns whether the system is in maintenance mode.
	 *
	 * @static
	 * @return bool
	 */
	public static function isInMaintenanceMode()
	{
		// Don't use the the static property $_storedBlocksInfo.  We want the latest info possible.
		// Not using Active Record here to prevent issues with determining maintenance mode status during a migration
		if (blx()->db->getSchema()->getTable('{{info}}')->getColumn('maintenance'))
		{
			$result = blx()->db->createCommand()->
			              select('maintenance')->
			              from('info')->
			              queryRow();

			if ($result['maintenance'] == 1)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Turns the system on.
	 *
	 * @static
	 * @return bool
	 */
	public static function turnSystemOn()
	{
		// Don't use the the static property $_storedBlocksInfo.  We want the latest info possible.
		// Not using Active Record here to prevent issues with turning the site on/off during a migration
		if (blx()->db->createCommand()->update('info', array('on' => 1)) > 0)
		{
			return true;
		}

		return false;
	}

	/**
	 * Turns the system off.
	 *
	 * @static
	 * @return bool
	 */
	public static function turnSystemOff()
	{
		// Don't use the the static property $_storedBlocksInfo.  We want the latest info possible.
		// Not using Active Record here to prevent issues with turning the site on/off during a migration
		if (blx()->db->createCommand()->update('info', array('on' => 0)) > 0)
		{
			return true;
		}

		return false;
	}

	/**
	 * Return the saved stored blocks info.  If it's not set, get it from the database and return it.
	 *
	 * @static
	 * @return InfoRecord
	 */
	private static function _getStoredInfo()
	{
		if (!isset(static::$_storedBlocksInfo))
		{
			if (blx()->isInstalled())
			{
				static::$_storedBlocksInfo = InfoRecord::model()->find();
			}
			else
			{
				static::$_storedBlocksInfo = false;
			}
		}

		return static::$_storedBlocksInfo;
	}

	/**
	 * Returns the Yii framework version.
	 *
	 * @static
	 * @return mixed
	 */
	public static function getYiiVersion()
	{
		return parent::getVersion();
	}

	/**
	 * @static
	 * @param $target
	 * @return string
	 */
	public static function dump($target)
	{
		\CVarDumper::dump($target, 10, true);
	}

	/**
	 * @static
	 * @param string $alias
	 * @param bool   $forceInclude
	 * @throws \Exception
	 * @return string|void
	 */
	public static function import($alias, $forceInclude = false)
	{
		$segs = explode('.', $alias);

		if ($segs)
		{
			$firstSeg = array_shift($segs);

			switch ($firstSeg)
			{
				case 'app':
				{
					$rootPath = BLOCKS_APP_PATH;
					break;
				}
				case 'plugins':
				{
					$rootPath = BLOCKS_PLUGINS_PATH;
					break;
				}
				default:
				{
					throw new \Exception('Unknown alias “'.$alias.'”');
				}
			}
		}
		else
		{
			$rootPath = BLOCKS_APP_PATH;
		}

		$path = $rootPath.implode('/', $segs);

		$folder = (substr($path, -2) == '/*');
		if ($folder)
		{
			$path = substr($path, 0, -1);
			$files = glob($path."*.php");
			if (is_array($files) && count($files) > 0)
			{
				foreach ($files as $file)
				{
					static::_importFile(realpath($file));
				}
			}
		}
		else
		{
			$file = $path.'.php';
			static::_importFile($file);

			if ($forceInclude)
			{
				require_once $file;
			}
		}
	}

	/**
	 * @static
	 * @param string $message
	 * @param array  $variables
	 * @param string $source
	 * @param string $language
	 * @param string $category
	 * @return string|null
	 */
	public static function t($message, $variables = array(), $source = null, $language = null, $category = 'blocks')
	{
		// Normalize the param keys
		$normalizedVariables = array();
		if (is_array($variables))
		{
			foreach ($variables as $key => $value)
			{
				$key = '{'.trim($key, '{}').'}';
				$normalizedVariables[$key] = $value;
			}
		}

		$translation = parent::t($category, $message, $normalizedVariables, $source, $language);
		if (blx()->config->get('translationDebugOutput'))
		{
			$translation = '@'.$translation.'@';
		}

		return $translation;
	}

	/**
	 * Logs a message.
	 * Messages logged by this method may be retrieved via {@link CLogger::getLogs} and may be recorded in different media, such as file, email, database, using {@link CLogRouter}.
	 *
	 * @param string $msg message to be logged
	 * @param string $level level of the message (e.g. 'trace', 'warning', 'error'). It is case-insensitive.
	 * @param string $category category of the message (e.g. 'system.web'). It is case-insensitive.
	 */
	public static function log($msg, $level = \CLogger::LEVEL_INFO, $category = 'application')
	{
		if (YII_DEBUG && YII_TRACE_LEVEL > 0 && $level !== \CLogger::LEVEL_PROFILE)
		{
			$traces = debug_backtrace();
			$count = 0;

			foreach ($traces as $trace)
			{
				if (isset($trace['file'], $trace['line']) && strpos($trace['file'], YII_PATH) !== 0)
				{
					$msg .= "\nin ".$trace['file'].' ('.$trace['line'].')';

					if (++$count >= YII_TRACE_LEVEL)
					{
						break;
					}
				}
			}
		}

		if (blx()->isConsole())
		{
			echo $msg."\n";
		}

		static::getLogger()->log($msg, $level, $category);
	}

	/**
	 * @static
	 * @param $file
	 */
	private static function _importFile($file)
	{
		$class = __NAMESPACE__.'\\'.pathinfo($file, PATHINFO_FILENAME);
		\Yii::$classMap[$class] = $file;
	}
}

/**
 * Returns the current blx() instance.  This is a wrapper function for the Blocks::app() instance.
 * @return App
 */
function blx()
{
	return Blocks::app();
}
