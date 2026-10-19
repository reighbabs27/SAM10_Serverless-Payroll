<?php

namespace App;

class PayrollService
{
    private CsvReader $csvReader;

    public function __construct(?CsvReader $csvReader = null)
    {
        $this->csvReader = $csvReader ?? new CsvReader();
    }

    /**
     * Endpoint 1: GET /api/employees
     * Returns employee records in JSON.
     */
    public function getEmployeesResponse(): array
    {
        $employees = $this->csvReader->getEmployees();

        return [
            'success' => true,
            'data'    => array_values($employees),
        ];
    }

    /**
     * Endpoint 2: GET /api/reports/payroll
     * Generate a payroll summary, including total gross pay, deductions, and net pay.
     */
    public function getPayrollReportResponse(): array
    {
        $records = $this->csvReader->getPayrollRecords();

        $totalCount = 0;
        $totalGross = 0.0;
        $totalNet = 0.0;

        foreach ($records as $row) {
            $totalCount++;
            $totalGross += $row['gross_pay'];
            $totalNet += $row['net_pay'];
        }

        $totalDeductions = $totalGross - $totalNet;

        return [
            'success' => true,
            'data'    => [
                'total_payroll_records' => $totalCount,
                'total_gross_pay'       => round($totalGross, 2),
                'total_deductions'      => round($totalDeductions, 2),
                'total_net_pay'         => round($totalNet, 2),
            ],
        ];
    }

    /**
     * Endpoint 3: GET /api/payslips/{payroll_id}
     * Return an individual payslip containing employee details and payroll figures.
     */
    public function getPayslipResponse(int $payrollId): array
    {
        $records = $this->csvReader->getPayrollRecords();

        if (!isset($records[$payrollId])) {
            return [
                'success' => false,
                'message' => "Payslip not found for payroll ID {$payrollId}",
            ];
        }

        $payroll = $records[$payrollId];
        $employees = $this->csvReader->getEmployees();
        $empId = $payroll['employee_id'];
        $emp = $employees[$empId] ?? null;

        $empName = $emp ? $emp['full_name'] : 'Unknown Employee';
        $gross = $payroll['gross_pay'];
        $net = $payroll['net_pay'];
        $deductions = round($gross - $net, 2);

        return [
            'success' => true,
            'data'    => [
                'payroll_id'       => $payrollId,
                'employee_id'      => $empId,
                'employee_name'    => $empName,
                'employee_email'   => $emp ? $emp['email'] : null,
                'pay_period_start' => $payroll['pay_period_start'],
                'pay_period_end'   => $payroll['pay_period_end'],
                'gross_pay'        => round($gross, 2),
                'deductions'       => $deductions,
                'net_pay'          => round($net, 2),
            ],
        ];
    }

    public function getPayrollRecord(int $payrollId): ?array
    {
        $records = $this->csvReader->getPayrollRecords();
        return $records[$payrollId] ?? null;
    }
}
