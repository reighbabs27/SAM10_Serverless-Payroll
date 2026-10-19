<?php

namespace App;

use Dompdf\Dompdf;
use Dompdf\Options;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class PayslipPdfGenerator
{
    private string $bucketName;
    private string $region;
    private S3Client $s3Client;

    public function __construct(?string $bucketName = null, ?string $region = null)
    {
        $this->bucketName = $bucketName ?? getenv('S3_BUCKET') ?: 'bondoc-19-1290-764-payslips';
        $this->region     = $region ?? getenv('AWS_REGION') ?: (getenv('AWS_DEFAULT_REGION') ?: 'us-east-1');

        $s3Config = [
            'version' => 'latest',
            'region'  => $this->region,
        ];

        // If local access keys are provided via environment
        $key = getenv('AWS_ACCESS_KEY_ID');
        $secret = getenv('AWS_SECRET_ACCESS_KEY');
        if (!empty($key) && !empty($secret) && !isset($_SERVER['LAMBDA_TASK_ROOT'])) {
            $s3Config['credentials'] = [
                'key'    => $key,
                'secret' => $secret,
            ];
        }

        $this->s3Client = new S3Client($s3Config);
    }

    /**
     * Generate HTML for the payslip
     */
    public function renderHtml(array $payslipData): string
    {
        $payrollId    = htmlspecialchars((string)($payslipData['payroll_id'] ?? ''));
        $empId        = htmlspecialchars((string)($payslipData['employee_id'] ?? ''));
        $empName      = htmlspecialchars((string)($payslipData['employee_name'] ?? ''));
        $empEmail     = htmlspecialchars((string)($payslipData['employee_email'] ?? 'N/A'));
        $periodStart  = htmlspecialchars((string)($payslipData['pay_period_start'] ?? ''));
        $periodEnd    = htmlspecialchars((string)($payslipData['pay_period_end'] ?? ''));
        $grossPay     = number_format((float)($payslipData['gross_pay'] ?? 0), 2);
        $deductions   = number_format((float)($payslipData['deductions'] ?? 0), 2);
        $netPay       = number_format((float)($payslipData['net_pay'] ?? 0), 2);
        $generatedAt  = date('Y-m-d H:i:s T');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip - #{$payrollId}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #2d3748;
            margin: 30px;
            font-size: 13px;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #2b6cb0;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header h1 {
            margin: 0;
            color: #2b6cb0;
            font-size: 24px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #718096;
            font-size: 13px;
        }
        .student-badge {
            background-color: #ebf8ff;
            border: 1px solid #bee3f8;
            border-left: 4px solid #3182ce;
            padding: 10px 15px;
            margin-bottom: 25px;
            border-radius: 4px;
        }
        .student-badge table {
            width: 100%;
        }
        .student-badge td {
            font-size: 12px;
            color: #2c5282;
        }
        .section-title {
            font-size: 15px;
            color: #2b6cb0;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px;
            margin-bottom: 12px;
            font-weight: bold;
        }
        table.info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        table.info-table th, table.info-table td {
            padding: 9px 12px;
            border: 1px solid #e2e8f0;
            text-align: left;
        }
        table.info-table th {
            background-color: #f7fafc;
            color: #4a5568;
            font-weight: 600;
            width: 25%;
        }
        table.financials {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table.financials th, table.financials td {
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
        }
        table.financials th {
            background-color: #edf2f7;
            color: #2d3748;
            text-align: left;
        }
        table.financials td.amount {
            text-align: right;
            font-family: monospace;
            font-size: 14px;
        }
        .net-pay-row {
            background-color: #f0fff4;
            font-weight: bold;
            font-size: 15px;
            color: #22543d;
        }
        .net-pay-row td {
            border-top: 2px solid #38a169;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 11px;
            color: #a0aec0;
            border-top: 1px solid #edf2f7;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Official Employee Payslip</h1>
        <p>AWS Lambda &amp; Bref Serverless Payroll System</p>
    </div>

    <div class="student-badge">
        <table>
            <tr>
                <td><strong>Student Name:</strong> Reighfer Joshua V. Bondoc</td>
                <td style="text-align: right;"><strong>Student ID:</strong> 19-1290-764</td>
            </tr>
        </table>
    </div>

    <div class="section-title">Employee Details</div>
    <table class="info-table">
        <tr>
            <th>Payroll ID</th>
            <td>#{$payrollId}</td>
            <th>Employee ID</th>
            <td>#{$empId}</td>
        </tr>
        <tr>
            <th>Employee Name</th>
            <td>{$empName}</td>
            <th>Email</th>
            <td>{$empEmail}</td>
        </tr>
        <tr>
            <th>Pay Period Start</th>
            <td>{$periodStart}</td>
            <th>Pay Period End</th>
            <td>{$periodEnd}</td>
        </tr>
    </table>

    <div class="section-title">Earnings &amp; Deductions Breakdown</div>
    <table class="financials">
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align: right; width: 35%;">Amount (USD)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total Gross Pay</td>
                <td class="amount">$ {$grossPay}</td>
            </tr>
            <tr>
                <td>Total Deductions (Gross - Net)</td>
                <td class="amount" style="color: #c53030;">- $ {$deductions}</td>
            </tr>
            <tr class="net-pay-row">
                <td>Net Take-Home Pay</td>
                <td class="amount" style="color: #22543d;">$ {$netPay}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Generated automatically by AWS Lambda Function via Bref &bull; Uploaded to S3 Bucket: {$this->bucketName}<br>
        Timestamp: {$generatedAt}
    </div>
</body>
</html>
HTML;
    }

    /**
     * Generate PDF and upload to S3
     *
     * @return array response matching activity requirements
     */
    public function generateAndUpload(array $payslipData): array
    {
        $payrollId = (int)$payslipData['payroll_id'];

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $dompdf = new Dompdf($options);

        $html = $this->renderHtml($payslipData);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfBytes = $dompdf->output();

        $s3Key = "payslips/{$payrollId}.pdf";

        try {
            $result = $this->s3Client->putObject([
                'Bucket'      => $this->bucketName,
                'Key'         => $s3Key,
                'Body'        => $pdfBytes,
                'ContentType' => 'application/pdf',
            ]);

            $bucketUrl = "https://{$this->bucketName}.s3.amazonaws.com/{$s3Key}";

            return [
                'success' => true,
                'message' => 'Payslip generated and uploaded.',
                'data'    => [
                    'payroll_id'   => $payrollId,
                    'payslip_file' => $bucketUrl,
                ],
            ];
        } catch (AwsException $e) {
            return [
                'success' => false,
                'message' => 'Failed to upload payslip to S3: ' . $e->getAwsErrorMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }
}
