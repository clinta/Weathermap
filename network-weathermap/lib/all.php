<?php
/*   PHP Weathermap 0.98
     Copyright Howard Jones, 2005-2014 howie@thingy.com
     http://www.network-weathermap.com/
     Released under the GNU Public License

    one file to include all the others...
*/

    require_once dirname(__FILE__).'/globals.php';
    require_once dirname(__FILE__).'/constants.php';

    require_once dirname(__FILE__).'/php-compat.php';

    // require_once dirname(__FILE__).'/WMDebug.php';
    
    require_once dirname(__FILE__).'/HTML_ImageMap.class.php';

    require_once dirname(__FILE__).'/base-classes.php';
    require_once dirname(__FILE__).'/plugin-base-classes.php';
    require_once dirname(__FILE__).'/WeatherMapDataItem.class.php';

    require_once dirname(__FILE__).'/fonts.php';
    require_once dirname(__FILE__).'/WeatherMapTextItem.class.php';
    require_once dirname(__FILE__).'/WeatherMapScale.class.php';
    require_once dirname(__FILE__).'/Weathermap.class.php';

    require_once dirname(__FILE__).'/WeatherMap.functions.php';
    require_once dirname(__FILE__).'/WMUtility.class.php';
    require_once dirname(__FILE__).'/WMImageUtility.php';

    require_once dirname(__FILE__).'/image-functions.php';
    require_once dirname(__FILE__).'/geometry.php';
    require_once dirname(__FILE__).'/WMPoint.class.php';
    require_once dirname(__FILE__).'/WMVector.class.php';
    require_once dirname(__FILE__).'/WMLine.class.php';
    require_once dirname(__FILE__).'/WMRectangle.class.php';
    require_once dirname(__FILE__).'/WMBoundingBox.class.php';
    require_once dirname(__FILE__).'/CatmullRom.class.php';

    require_once dirname(__FILE__).'/WMSpine.class.php';
    require_once dirname(__FILE__).'/WMLinkGeometry.class.php';
    require_once dirname(__FILE__).'/WMAngledLinkGeometry.class.php';
    require_once dirname(__FILE__).'/WMCurvedLinkGeometry.class.php';
    require_once dirname(__FILE__).'/WMLinkGeometryFactory.class.php';

    require_once dirname(__FILE__).'/WMColour.class.php';
    require_once dirname(__FILE__).'/WMTarget.class.php';

    require_once dirname(__FILE__).'/WeatherMapNode.class.php';
    require_once dirname(__FILE__).'/WeatherMapLink.class.php';
    require_once dirname(__FILE__).'/WeatherMapConfig.php';

    require_once dirname(__FILE__).'/WeatherMapRunner.class.php';

//    $wm_debug = WMDebugFactory::create();
