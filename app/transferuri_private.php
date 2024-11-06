<?php
header('Content-Type: application/json');

function transferuriCorporate_getPreturiTransfer($from_lat, $from_lng, $to_lat, $to_lng, $id_corporate) {
    // Dummy data
    $transfer_classes = [
        [
            'id_clasa' => 1,
            'nume_clasa' => 'Economic',
            'foto' => 'test.jpg',
            'pret' => 105, // pret
            'distanta' => 15, // km
            'durata' => 20 // minute
        ],
        [
            'id_clasa' => 2,
            'nume_clasa' => 'Standard',
            'foto' => 'test.jpg',
            'pret' => 115,
            'distanta' => 15,
            'durata' => 20
        ],
        [
            'id_clasa' => 3,
            'nume_clasa' => 'Premium',
            'foto' => 'test.jpg',
            'pret' => 140,
            'distanta' => 15,
            'durata' => 20
        ],
        [
            'id_clasa' => 4,
            'nume_clasa' => 'Microbuz',
            'foto' => 'test.jpg',
            'pret' => 120,
            'distanta' => 15,
            'durata' => 20
        ]
    ];

    return json_encode($transfer_classes);
}

if (isset($_GET['from_lat'], $_GET['from_lng'], $_GET['to_lat'], $_GET['to_lng'], $_GET['id_corporate'])) {
    $from_lat = $_GET['from_lat'];
    $from_lng = $_GET['from_lng'];
    $to_lat = $_GET['to_lat'];
    $to_lng = $_GET['to_lng'];
    $id_corporate = $_GET['id_corporate'];

    // get transfer prices
    $result = transferuriCorporate_getPreturiTransfer($from_lat, $from_lng, $to_lat, $to_lng, $id_corporate);
    echo $result;
} else {
    echo json_encode(['error' => 'Missing parameters']);
}
