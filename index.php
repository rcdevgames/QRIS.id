<?php
require_once('src\QRIS.php');
require_once('src\EnvParser.php');

use Rmodz\QRIS as wQris;
use Rmodz\DotEnv;


try {
    (new DotEnv(__DIR__ . '/.env'))->load();

    // inisiasi class
    $qris = new wQris (
        getenv('QRIS_EMAIL'),  //* qris.id email
        getenv('QRIS_PASSWORD'),  //* qris.id password
        '2022-09-28',   // tanggal awal     | format tanggal Y-m-d | default (null) = hari ini
        null,   // tanggal akhir    | format tanggal Y-m-d | default (null) = hari ini
        null,    // limit data mutasi perhalaman, default 20 | min 10 max 300
        null,   // cari nominal transaksi, default (null)
    );


    // fetch data
    $mutasi = $qris->mutasi();

    $result = [
        'status' => true,
        'data' => $mutasi
    ];
} catch (Exception $e) {
    http_response_code(400);
    $result = [
        'status' => false,
        'message' => $e->getMessage()
    ];
} finally {
    header("Content-Type: application/json");
    echo json_encode($result,JSON_PRETTY_PRINT);
}
