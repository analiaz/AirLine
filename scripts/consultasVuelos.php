<?php

set_exception_handler(function ($e) {
    $code = $e->getCode() ?: 400;
    header("Content-Type: application/json", NULL, $code);
    echo json_encode(["error" => $e->getMessage()]);
    exit;
});

$verb = $_SERVER['REQUEST_METHOD'];

switch($verb) {
    case 'POST':
        $params = json_decode(file_get_contents("php://input"), true);
        if (!$params || !$params["origen"] || !$params["destino"] || !$params["salida"] || !$params["pasajeros"]) {
            throw new Exception("Data missing or invalid");
        }


        $db = mysqli_connect("localhost","analiaz","foo","mydb");
        if (!$db) {
            throw new Exception("No se pudo conectar a MySQL: " . mysqli_connect_errno() . PHP_EOL);
        } else {
            // echo "conecion OK a:" . mydb;
            $query = "SELECT fecha_llegada, fecha_partida, precio, vuelo_id
                      FROM vuelos
                      WHERE origen='{$params["origen"]}'
                        AND destino='{$params["destino"]}'
                        AND asientos - (SELECT COUNT(*) FROM pasajes WHERE id_vuelo = vuelo_id) >= {$params["pasajeros"]}
                        AND fecha_partida LIKE '{$params["salida"]}%'";

            if ($rows = mysqli_query($db, $query)) {

                $vuelos = mysqli_fetch_all($rows, MYSQLI_ASSOC);
/*                
                while ($row = mysqli_fetch_array($rows, MYSQLI_ASSOC)) {
                    $vuelos[] = $row];
                }
*/
            } else {
                $vuelos = [];
            }

            $data = ['vuelos' => $vuelos];
        }
        break;
    default:
        throw new Exception('Method not supported', 405);
}

header("Content-Type: application/json");
echo json_encode($data);

?>