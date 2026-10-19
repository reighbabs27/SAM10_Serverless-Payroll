<?php

namespace App;

class CsvReader
{
    private string $dataDir;

    public function __construct(?string $dataDir = null)
    {
        if ($dataDir !== null) {
            $this->dataDir = rtrim($dataDir, '/\\');
        } else {
            $root = $_SERVER['LAMBDA_TASK_ROOT'] ?? dirname(__DIR__);
            $this->dataDir = $root . DIRECTORY_SEPARATOR . 'data';
        }
    }

    /**
     * Read employees from CSV
     *
     * @return array<int, array> keyed by employee id
     */
    public function getEmployees(): array
    {
        $filePath = $this->dataDir . DIRECTORY_SEPARATOR . 'employees.csv';
        if (!file_exists($filePath)) {
            return [];
        }

        $employees = [];
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return [];
        }

        // Header: id,full_name,email,position_id,department_id,manager_id
        $header = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row) || count($row) < 5) {
                continue;
            }

            $id = (int)$row[0];
            $employees[$id] = [
                'id'            => $id,
                'employee_id'   => $id,
                'full_name'     => $row[1] ?? '',
                'email'         => $row[2] ?? '',
                'position_id'   => !empty($row[3]) && is_numeric($row[3]) ? (int)$row[3] : null,
                'department_id' => !empty($row[4]) && is_numeric($row[4]) ? (int)$row[4] : null,
                'manager_id'    => !empty($row[5]) && is_numeric($row[5]) ? (int)$row[5] : null,
            ];
        }

        fclose($handle);
        return $employees;
    }

    /**
     * Read payroll records from CSV
     *
     * @return array<int, array> keyed by payroll id
     */
    public function getPayrollRecords(): array
    {
        $filePath = $this->dataDir . DIRECTORY_SEPARATOR . 'payroll.csv';
        if (!file_exists($filePath)) {
            return [];
        }

        $records = [];
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return [];
        }

        // Header: id,employee_id,pay_period_start,pay_period_end,gross_pay,net_pay
        $header = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row) || count($row) < 6) {
                continue;
            }

            $id = (int)$row[0];
            $records[$id] = [
                'id'               => $id,
                'employee_id'      => (int)$row[1],
                'pay_period_start' => $row[2] ?? '',
                'pay_period_end'   => $row[3] ?? '',
                'gross_pay'        => (float)$row[4],
                'net_pay'          => (float)$row[5],
            ];
        }

        fclose($handle);
        return $records;
    }
}
