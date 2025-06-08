<?php
require __DIR__ . '/vendor/autoload.php';

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\AppFactory;
use GuzzleHttp\Client;

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// Enable CORS
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Main POST endpoint
$app->post('/rates', function (Request $request, Response $response) {
    $data = $request->getParsedBody();

    // Validate input
    if (!isset($data['Unit Name'], $data['Arrival'], $data['Departure'], $data['Occupants'], $data['Ages'])) {
        $response->getBody()->write(json_encode(['error' => 'Invalid payload']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    // Prepare Guests
    $guests = [];
    foreach ($data['Ages'] as $age) {
        $guests[] = [
            'Age Group' => ($age >= 18) ? 'Adult' : 'Child'
        ];
    }

    // Convert dates
    $arrival = date('Y-m-d', strtotime(str_replace('/', '-', $data['Arrival'])));
    $departure = date('Y-m-d', strtotime(str_replace('/', '-', $data['Departure'])));

    // Prepare payload
    $remotePayload = [
        'Unit Type ID' => -2147483637, // testing unit type
        'Arrival' => $arrival,
        'Departure' => $departure,
        'Guests' => $guests
    ];

    // Log what’s being sent
    file_put_contents(__DIR__ . '/debug.log', "Sent to remote:\n" . json_encode($remotePayload) . "\n", FILE_APPEND);

    $client = new Client();
    try {
        $res = $client->post('https://dev.gondwana-collection.com/Web-Store/Rates/Rates.php', [
            'json' => $remotePayload
        ]);

        $remoteResponse = json_decode($res->getBody(), true);

        // Log the remote response
        file_put_contents(__DIR__ . '/debug.log', "Remote response:\n" . print_r($remoteResponse, true) . "\n", FILE_APPEND);

        // Build structured output
        $rates = [];
        if (isset($remoteResponse['Legs'])) {
            foreach ($remoteResponse['Legs'] as $leg) {
                $rates[] = [
                    'Category' => $leg['Category'] ?? 'N/A',
                    'Special Rate' => $leg['Special Rate Description'] ?? 'N/A',
                    'Effective Average Daily Rate' => $leg['Effective Average Daily Rate'] ?? 'N/A',
                    'Total Charge' => $leg['Total Charge'] ?? 'N/A'
                ];
            }
        }

        $output = [
            'Unit Name' => $data['Unit Name'],
            'Date Range' => "{$arrival} to {$departure}",
            'Rates' => $rates
        ];

    } catch (Exception $e) {
        $output = ['error' => $e->getMessage()];
    }

    $response->getBody()->write(json_encode($output));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
