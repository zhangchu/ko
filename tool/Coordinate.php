<?php

class Ko_Tool_Coordinate
{
    const PI = 3.1415926535897932384626;
    const a = 6378245.0;
    const ee = 0.00669342162296594323;

    /**
     * WGS84 转 GCj02
     */
    public static function wgs84ToGcj02($w_lon, $w_lat)
    {
        if (!self::_isOutOfChina($w_lon, $w_lat)) {
            $dlat = self::_transFormLat($w_lon - 105.0, $w_lat - 35.0);
            $dlon = self::_transFormLon($w_lon - 105.0, $w_lat - 35.0);
            $radlat = $w_lat / 180.0 * self::PI;
            $magic = sin($radlat);
            $magic = 1 - self::ee * $magic * $magic;
            $sqrtmagic = sqrt($magic);
            $dlat = ($dlat * 180.0) / ((self::a * (1 - self::ee)) / ($magic * $sqrtmagic) * self::PI);
            $dlon = ($dlon * 180.0) / (self::a / $sqrtmagic * cos($radlat) * self::PI);
            $g_lat = $w_lat + $dlat;
            $g_lon = $w_lon + $dlon;
        } else {
            $g_lat = $w_lat;
            $g_lon = $w_lon;
        }
        return array('lng' => $g_lon, 'lat' => $g_lat);
    }
 
    /**
     * GCJ02 转换为 WGS84
     */
    public static function gcj02ToWgs84($g_lon, $g_lat)
    {
        $threshold = 0.00001;

        $space = 0.5;
        $point = $p = array(
            'lng' => $g_lon,
            'lat' => $g_lat,
        );

        $maxIteration = 30;
        for ($i=0; $i<$maxIteration; $i++) {
            $midPoint = self::wgs84ToGcj02($p['lng'], $p['lat']);
            $delta = abs($midPoint['lng'] - $g_lon) + abs($midPoint['lat'] - $g_lat);
            if ($delta <= $threshold) {
                break;
            }

            $leftBottom = self::wgs84ToGcj02($p['lng'] - $space, $p['lat'] - $space);
            if (self::_isContains($point, $leftBottom, $midPoint)) {
                $space /= 2;
                $p = array(
                    'lng' => $p['lng'] - $space,
                    'lat' => $p['lat'] - $space,
                );
                continue;
            }
            $rightBottom = self::wgs84ToGcj02($p['lng'] + $space, $p['lat'] - $space);
            if (self::_isContains($point, $rightBottom, $midPoint)) {
                $space /= 2;
                $p = array(
                    'lng' => $p['lng'] + $space,
                    'lat' => $p['lat'] - $space,
                );
                continue;
            }
            $leftUp = self::wgs84ToGcj02($p['lng'] - $space, $p['lat'] + $space);
            if (self::_isContains($point, $leftUp, $midPoint)) {
                $space /= 2;
                $p = array(
                    'lng' => $p['lng'] - $space,
                    'lat' => $p['lat'] + $space,
                );
                continue;
            }
            $space /= 2;
            $p = array(
                'lng' => $p['lng'] + $space,
                'lat' => $p['lat'] + $space,
            );
        }
        return $p;
    }

    protected static function _isOutOfChina($lng, $lat)
    {
        return $lng < 72.004 || $lng > 137.8347 || $lat < 0.8293 || $lat > 55.8271;
    }

    protected static function _isContains($point, $p1, $p2)
    {
        return min($p1['lat'], $p2['lat']) <= $point['lat'] && $point['lat'] <= max($p1['lat'], $p2['lat'])
            && min($p1['lng'], $p2['lng']) <= $point['lng'] && $point['lng'] <= max($p1['lng'], $p2['lng']);
    }

    /**
     * 转换纬度
     */
    protected static function _transFormLat($lon, $lat)
    {
        $ret = -100.0 + 2.0 * $lon + 3.0 * $lat + 0.2 * $lat * $lat + 0.1 * $lon * $lat + 0.2 * sqrt(abs($lon));
        $ret += (20.0 * sin(6.0 * $lon * self::PI) + 20.0 * sin(2.0 * $lon * self::PI)) * 2.0 / 3.0;
        $ret += (20.0 * sin($lat * self::PI) + 40.0 * sin($lat / 3.0 * self::PI)) * 2.0 / 3.0;
        $ret += (160.0 * sin($lat / 12.0 * self::PI) + 320 * sin($lat * self::PI / 30.0)) * 2.0 / 3.0;
        return $ret;
    }
 
    /**
     * 转换经度
     */
    protected static function _transFormLon($lon, $lat)
    {
        $ret = 300.0 + $lon + 2.0 * $lat + 0.1 * $lon * $lon + 0.1 * $lon * $lat + 0.1 * sqrt(abs($lon));
        $ret += (20.0 * sin(6.0 * $lon * self::PI) + 20.0 * sin(2.0 * $lon * self::PI)) * 2.0 / 3.0;
        $ret += (20.0 * sin($lon * self::PI) + 40.0 * sin($lon / 3.0 * self::PI)) * 2.0 / 3.0;
        $ret += (150.0 * sin($lon / 12.0 * self::PI) + 300.0 * sin($lon / 30.0 * self::PI)) * 2.0 / 3.0;
        return $ret;
    }
}
