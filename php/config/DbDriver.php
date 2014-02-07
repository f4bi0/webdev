<?php
DbDriver::$default = 'local';
DbDriver::$configs = array(
    'local' => array(
        'user' => 'root',
        'host' => 'localhost',
        'pass' => ''
    ),
    'amazon' => array(
        'host' => 'mysql.traca.com.br',
        'user' => 'traca2',#evita usar o root pra se conectar na amazon
        'pass' => base64_decode('dHJAY0AwMjIwMTAkJmN1cjF0eQ==')

    )
);