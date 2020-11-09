<?php
// MN
$mn = array(
    'type' => 'MN',
    'requestId' => time(),
    'param' => array(
        'xas' => array(
            'model' => 'Account',
            'method' => 'login',
            'param' => array(
                'username' => 'wahah',
                'password' => 'wahah',
            ),
        ),
        'xxx' => array(
            'model' => 'Account',
            'method' => 'login',
            'param' => array(
                'username' => 'wahah222',
                'password' => 'wahah',
            ),
        ),
    ),
);


$host = '0.0.0.0';
$port = 6667;
Co\run(function()use($host,$port,$mn){
    $client = new Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
    if (!$client->connect($host, $port, 0.5))
    {
        echo "connect failed. Error: {$client->errCode}\n";
    }
    $client->set( array(
        'open_length_check' => 1,
        'package_length_type' => 'N',
        'package_length_offset' => 0,
        'package_body_offset' => 4,
    ) );
    $data = json_encode( $mn );
    $data = pack( 'N', strlen( $data ) ).$data;
    $client->send( $data );
    $jsonString = $client->recv();
    $header = substr( $jsonString, 0, 4 );
    $len = unpack( 'Nlen', $header );
    $len = $len['len'];
    $data = substr( $jsonString, 4, $len );
    print_r( json_decode( $data, true ) );
    $client->close();
});
