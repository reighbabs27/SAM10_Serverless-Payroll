# SAM10 - Serverless PHP Payroll Application with AWS Lambda & Bref

**Student Name:** Reighfer Joshua V. Bondoc  
**Student ID:** 19-1290-764  
**Activity:** Serverless PHP Deployment to AWS Lambda using Bref  
**Live Deployed API Base URL:** [https://syn330v61a.execute-api.us-east-1.amazonaws.com](https://syn330v61a.execute-api.us-east-1.amazonaws.com)  
**S3 Bucket:** `bondoc-19-1290-764-payslips`  

---

## 📌 Project Overview
This project is a serverless RESTful API built with **PHP 8.2**, **Bref**, **AWS Lambda**, **API Gateway**, **Bramus Router**, **Dompdf**, and **AWS SDK for PHP**. It reads employee and payroll records from CSV datasets (without requiring a relational database), computes payroll metrics, serves JSON endpoints, and generates PDF payslips on-demand uploaded to an Amazon S3 bucket.

---

## 🚀 Deployed API Endpoints

| HTTP Method | Endpoint | Description | Sample Live Link |
| :--- | :--- | :--- | :--- |
| **GET** | `/` | API Documentation & Student Info | [Live View](https://syn330v61a.execute-api.us-east-1.amazonaws.com/) |
| **GET** | `/api/employees` | Returns all 500 employee records in JSON format | [Live View](https://syn330v61a.execute-api.us-east-1.amazonaws.com/api/employees) |
| **GET** | `/api/reports/payroll` | Computes payroll totals (Gross Pay, Deductions, Net Pay) | [Live View](https://syn330v61a.execute-api.us-east-1.amazonaws.com/api/reports/payroll) |
| **GET** | `/api/payslips/{payroll_id}` | Returns individual payslip details joined with employee data | [Live View (#1)](https://syn330v61a.execute-api.us-east-1.amazonaws.com/api/payslips/1) |
| **POST / GET** | `/api/payslips/{payroll_id}/generate` | Generates a styled PDF payslip with Dompdf, uploads to S3, returns S3 URL | [Generate #1](https://syn330v61a.execute-api.us-east-1.amazonaws.com/api/payslips/1/generate) |

---

## 📊 Sample Responses

### 1. `GET /api/employees`
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "employee_id": 1,
      "full_name": "Sam Dubois",
      "email": "sam.dubois@example.net",
      "position_id": 1,
      "department_id": 8,
      "manager_id": null
    },
    {
      "id": 2,
      "employee_id": 2,
      "full_name": "Nadia Bauer",
      "email": "nadia.bauer@example.org",
      "position_id": 21,
      "department_id": 7,
      "manager_id": 1
    }
  ]
}
```

### 2. `GET /api/reports/payroll`
```json
{
  "success": true,
  "data": {
    "total_payroll_records": 600,
    "total_gross_pay": 4981004.63,
    "total_deductions": 849478.70,
    "total_net_pay": 4131525.93
  }
}
```

### 3. `GET /api/payslips/1`
```json
{
  "success": true,
  "data": {
    "payroll_id": 1,
    "employee_id": 319,
    "employee_name": "Deniz Fischer",
    "employee_email": "deniz.fischer@example.net",
    "pay_period_start": "2023-10-14",
    "pay_period_end": "2023-05-06",
    "gross_pay": 14131.54,
    "deductions": 12626.36,
    "net_pay": 1505.18
  }
}
```

### 4. `POST /api/payslips/1/generate`
```json
{
  "success": true,
  "message": "Payslip generated and uploaded.",
  "data": {
    "payroll_id": 1,
    "payslip_file": "https://bondoc-19-1290-764-payslips.s3.amazonaws.com/payslips/1.pdf"
  }
}
```

---

## 🛠️ Architecture & Tech Stack

- **PHP 8.2** with Bref FPM runtime (`php-82-fpm`) on AWS Lambda.
- **Serverless Framework (v3)** for infrastructure-as-code orchestration.
- **AWS API Gateway (HTTP API v2)** for fast, low-latency endpoint routing.
- **Amazon S3** (`bondoc-19-1290-764-payslips`) for storing generated payslip PDFs.
- **Bramus Router** (`bramus/router`) for clean, flexible REST routing.
- **Dompdf** (`dompdf/dompdf`) for server-side HTML-to-PDF rendering.
- **AWS SDK for PHP** (`aws/aws-sdk-php`) for direct S3 object uploads.

---

## 📁 Directory Structure
```
SAM10_Serverless-Payroll/
├── data/
│   ├── departments.csv
│   ├── employees.csv
│   ├── payroll.csv
│   └── positions.csv
├── public/
│   └── index.php           # API Gateway & Bramus Router Entry Point
├── src/
│   ├── CsvReader.php       # High-performance CSV parser
│   ├── PayrollService.php  # Business logic & calculations
│   └── PayslipPdfGenerator.php # Dompdf generation and S3 upload
├── composer.json
├── composer.lock
├── serverless.yml          # Infrastructure definition & IAM roles
└── README.md
```

---

## 🔧 Local Development & Deployment

### 1. Install Dependencies
```bash
composer install
```

### 2. Run Local Development Server
```bash
php -S localhost:8000 -t public
```

### 3. Deploy to AWS Lambda
```bash
serverless deploy
```

---

## 💡 Issues Encountered & Solutions

1. **Composer Platform Compatibility Issue:**
   - *Problem:* When running Composer on PHP 8.2.12, newer transitive dependencies for Symfony required PHP `>= 8.4`, causing a runtime platform check failure.
   - *Resolution:* Added `"config": { "platform": { "php": "8.2.12" }, "platform-check": false }` in `composer.json` to lock dependency resolution to the exact PHP 8.2 runtime supported by AWS Lambda and Bref.

2. **AWS Lambda Reserved Environment Variable Conflict:**
   - *Problem:* Initial CloudFormation deployment failed with status 400 because `AWS_DEFAULT_REGION` was declared under `provider.environment`. In AWS Lambda, `AWS_DEFAULT_REGION` and `AWS_REGION` are system-reserved keys.
   - *Resolution:* Removed `AWS_DEFAULT_REGION` from `serverless.yml`, allowing Lambda to automatically inject the execution region.

---

## 📝 Learning Reflection

Through this laboratory activity, I gained hands-on experience in building modern serverless architectures using PHP, a language traditionally deployed on monolithic servers with Apache or NGINX. 

Using **Bref**, I learned how PHP-FPM operates within containerized AWS Lambda microVMs, abstracting server provisioning and maintenance while dynamically scaling to incoming HTTP traffic via API Gateway. I also deepened my understanding of AWS IAM role configurations by restricting Lambda execution permissions strictly to `s3:PutObject` on the target payslip prefix, reinforcing the principle of least privilege. Integrating **Dompdf** and the **AWS SDK for PHP** demonstrated how on-demand document generation can be offloaded to serverless compute and stored reliably in cloud object storage without local disk persistence constraints.
