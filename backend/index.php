<?php
require __DIR__ . '/vendor/autoload.php';

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\AppFactory;
use GuzzleHttp\Client;

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// CORS: allow all origins and methods
$app->options('/{routes:.+}', fn($req, $res) => $res);
$app->add(fn($req, $handler) => 
    $handler->handle($req)
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With,Content-Type,Accept,Origin,Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,PATCH,OPTIONS')
);

// POST /rates endpoint
$app->post('/rates', function (Request $request, Response $response) {
    $data = $request->getParsedBody();

    // Validate input payload
    if (!isset($data['Unit Name'], $data['Arrival'], $data['Departure'], $data['Occupants'], $data['Ages'])) {
        $response->getBody()->write(json_encode(['error' => 'Invalid payload']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    // Prepare guest array for remote request
    $guests = array_map(fn($age) => ['Age Group' => ($age >= 18 ? 'Adult' : 'Child')], $data['Ages']);

    // Convert dates to yyyy-mm-dd format
    $arrival = date('Y-m-d', strtotime(str_replace('/', '-', $data['Arrival'])));
    $departure = date('Y-m-d', strtotime(str_replace('/', '-', $data['Departure'])));

    // Remote API payload
    $remotePayload = [
        'Unit Type ID' => -2147483637,
        'Arrival' => $arrival,
        'Departure' => $departure,
        'Guests' => $guests
    ];

    // Log the outgoing request
    file_put_contents(__DIR__ . '/debug.log', "Sent to remote:\n" . json_encode($remotePayload) . "\n", FILE_APPEND);

    $client = new Client();
    try {
        // Send to Gondwana remote rates endpoint
        $resRemote = $client->post('https://dev.gondwana-collection.com/Web-Store/Rates/Rates.php', ['json' => $remotePayload]);
        $remoteResponse = json_decode($resRemote->getBody(), true);

        // Log remote response
        file_put_contents(__DIR__ . '/debug.log', "Remote response:\n" . print_r($remoteResponse, true) . "\n", FILE_APPEND);

        // Extract legs data into rates array
        $rates = [];
        foreach ($remoteResponse['Legs'] ?? [] as $leg) {
            $rates[] = [
                'Category' => $leg['Category'] ?? 'N/A',
                'Special Rate' => $leg['Special Rate Description'] ?? 'N/A',
                'Effective Average Daily Rate' => $leg['Effective Average Daily Rate'] ?? 'N/A',
                'Total Charge' => $leg['Total Charge'] ?? 'N/A'
            ];
        }

        $output = [
            'Unit Name' => $data['Unit Name'],
            'Date Range' => "{$arrival} to {$departure}",
            'Rates' => $rates
        ];
    } catch (Exception $e) {
        // Handle exceptions
        $output = ['error' => $e->getMessage()];
    }

    // Return JSON response
    $response->getBody()->write(json_encode($output));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
