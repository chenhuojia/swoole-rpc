<?php

namespace chj\Swoole\Library;

class Packet{

    private static $tcpPack = 'length';  // length eof两种方式

    /*
     * @desc : 配置packet的拆包方式
     */
    public static function encode($data, $type = "tcp" ){
        if (!$data) return '';
        if (is_string($data))
        {
            $data = trim($data);
            if( 'tcp' == $type ){
                if( 'eof' == self::$tcpPack ){
                    $data = $data .'\r\n';
                }else if( 'length' == self::$tcpPack ){
                    $data = pack( 'N', strlen( $data ) ).$data;
                }
            }
        }else{
            if( 'tcp' == $type ){
                if( 'eof' == self::$tcpPack ){
                    $data = json_encode( $data ).'\r\n';
                }else if( 'length' == self::$tcpPack ){
                    $data = json_encode( $data,JSON_UNESCAPED_UNICODE );
                    $data = pack( 'N', strlen( $data ) ).$data;
                }
            }else{
                $data = json_encode( $data,JSON_UNESCAPED_UNICODE );
            }
        }
        return $data;

    }

    /*
     * @desc : 配置packet的拆包方式
     */
    public static function decode( $jsonString, $type = "tcp" ){
        if( 'tcp' == $type ){
            if( 'eof' == self::$tcpPack ){
                $jsonString = str_replace( '\r\n', '', $jsonString );
            }else if( 'length' == self::$tcpPack ){
                $header = substr( $jsonString, 0, 4 );
                $len = unpack( 'Nlen', $header );
                $len = $len['len'];
                $jsonString = substr( $jsonString, 4, $len );
            }
        }
        if (SWOOLE_VERSION >= '4.5.6')
        {
            $reuslt = swoole_substr_json_decode( $jsonString, 0,null,true );
        }else{
            $reuslt = json_decode($jsonString,1);
        }
        $reuslt = $reuslt?:$jsonString;
        return $reuslt;
    }

    /*
     * @desc : 配置packet的拆包方式
     */
    public static function setting( array $setting ){
        self::$tcpPack = isset( $setting['tcpPack'] ) ? $setting['tcpPack'] : 'length' ;
    }


    public static function sign($string)
    {

    }

    public static function decrypt($string)
    {

    }

}