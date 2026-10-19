<?php

require __DIR__ . '/../vendor/autoload.php';

use App\PayrollService;
use App\PayslipPdfGenerator;
use Bramus\Router\Router;

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$router = new Router();
$payrollService = new PayrollService();
$pdfGenerator = new PayslipPdfGenerator();

// Handle API Gateway stage prefix if any (e.g. /dev or /prod)
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (preg_match('#^/(dev|prod|stage|default)/#i', $requestUri, $matches)) {
    $router->setBasePath('/' . $matches[1]);
}

/**
 * Root / API Documentation Endpoint
 */
$router->get('/', function () {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'activity' => 'Serverless PHP Application on AWS Lambda with Bref',
        'student' => [
            'name' => 'Reighfer Joshua V. Bondoc',
            'id_number' => '19-1290-764',
        ],
        'endpoints' => [
            'GET  /api/employees'                   => 'Return employee records in JSON',
            'GET  /api/reports/payroll'             => 'Generate a payroll summary (total gross, deductions, net pay)',
            'GET  /api/payslips/{payroll_id}'       => 'Return an individual payslip (e.g. /api/payslips/1)',
            'POST /api/payslips/{payroll_id}/generate' => 'Generate PDF payslip, upload to S3, and return S3 URL',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});

$router->get('/api', function () {
    header('Location: /');
    exit;
});

/**
 * Endpoint 1: GET /api/employees
 * Return employee records in JSON
 */
$router->get('/api/employees', function () use ($payrollService) {
    header('Content-Type: application/json; charset=utf-8');
    $response = $payrollService->getEmployeesResponse();
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});

/**
 * Endpoint 2: GET /api/reports/payroll
 * Generate a payroll summary, including total gross pay, deductions, and net pay
 */
$router->get('/api/reports/payroll', function () use ($payrollService) {
    header('Content-Type: application/json; charset=utf-8');
    $response = $payrollService->getPayrollReportResponse();
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});

/**
 * Endpoint 4: POST /api/payslips/{payroll_id}/generate (defined BEFORE generic payslips/{payroll_id})
 * Generate a PDF payslip, upload it to S3, and return the S3 object key / URL
 */
$router->match('GET|POST', '/api/payslips/([0-9]+)/generate', function ($payroll_id) use ($payrollService, $pdfGenerator) {
    header('Content-Type: application/json; charset=utf-8');
    $payslip = $payrollService->getPayslipResponse((int)$payroll_id);

    if (!$payslip['success']) {
        http_response_code(404);
        echo json_encode($payslip, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        return;
    }

    $uploadResult = $pdfGenerator->generateAndUpload($payslip['data']);

    if (!$uploadResult['success']) {
        http_response_code(500);
    }

    echo json_encode($uploadResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});

/**
 * Endpoint 3: GET /api/payslips/{payroll_id}
 * Return an individual payslip containing employee details and payroll figures
 */
$router->get('/api/payslips/([0-9]+)', function ($payroll_id) use ($payrollService) {
    header('Content-Type: application/json; charset=utf-8');
    $response = $payrollService->getPayslipResponse((int)$payroll_id);

    if (!$response['success']) {
        http_response_code(404);
    }

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});

/**
 * 404 Handler
 */
$router->set404(function () {
    header('HTTP/1.1 404 Not Found');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Endpoint not found. Refer to GET / for available endpoints.',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});

$router->run();
