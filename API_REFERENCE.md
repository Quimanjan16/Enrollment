# API Reference Documentation

## Overview
This document describes all API endpoints available in the School Enrollment System.

**Base URL**: `http://localhost/school-enrollment/api/`

**Authentication**: All endpoints require active session (login required)

---

## 1. Record Payment

### Endpoint
```
POST /api/record-payment.php
```

### Authentication
- **Required Role**: Cashier

### Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `schedule_id` | Integer | Yes | Payment schedule ID |
| `amount_paid` | Float | Yes | Amount paid (₱) |
| `payment_method` | String | Yes | Cash, Check, Bank Transfer, Card |
| `reference_number` | String | No | Transaction reference |
| `notes` | String | No | Additional notes |

### Example Request
```php
$data = [
    'schedule_id' => 5,
    'amount_paid' => 5000.00,
    'payment_method' => 'Cash',
    'reference_number' => 'TXN123456',
    'notes' => 'Payment for Prelim'
];
```

### Success Response
```json
{
    "success": true,
    "message": "Payment recorded successfully",
    "payment_id": 42,
    "new_status": "paid"
}
```

### Error Response
```json
{
    "success": false,
    "message": "Error recording payment: [error details]"
}
```

### What Happens
1. ✅ Payment amount added to payment schedule
2. ✅ Payment status updated (pending → partial → paid)
3. ✅ Transaction logged in payments table
4. ✅ Activity log entry created
5. ✅ Assessment is notified of change

---

## 2. Create Enrollment

### Endpoint
```
POST /api/create-enrollment.php
```

### Authentication
- **Required Role**: Cashier, Registrar

### Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `student_id` | Integer | Yes | Student ID |
| `academic_year` | String | Yes | Format: "2024-2025" |
| `semester` | Integer | Yes | 1 or 2 |
| `grade_level` | Integer | Yes | 7, 8, 9, or 10 |
| `total_tuition` | Float | Yes | Total tuition (₱) |

### Example Request
```php
$data = [
    'student_id' => 15,
    'academic_year' => '2024-2025',
    'semester' => 1,
    'grade_level' => 8,
    'total_tuition' => 20000.00
];
```

### Success Response
```json
{
    "success": true,
    "message": "Enrollment created successfully",
    "enrollment_id": 87
}
```

### Payment Schedule Auto-Generated
The system automatically creates 4 payment schedules:
- Prelim: ₱5,000.00 (Due: 1st week)
- Midterm: ₱5,000.00 (Due: 2nd week)
- Pre-Final: ₱5,000.00 (Due: 3rd week)
- Final: ₱5,000.00 (Due: 4th week)

---

## 3. Add Additional Fee

### Endpoint
```
POST /api/add-additional-fee.php
```

### Authentication
- **Required Role**: Registrar

### Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `enrollment_id` | Integer | Yes | Enrollment ID |
| `fee_description` | String | Yes | E.g., "Sports Fee" |
| `fee_amount` | Float | Yes | Fee amount (₱) |
| `applicable_grade` | Integer | No | Specific grade (7-10) |

### Example Request
```php
$data = [
    'enrollment_id' => 87,
    'fee_description' => 'School ID Fee',
    'fee_amount' => 500.00,
    'applicable_grade' => 8
];
```

### Success Response
```json
{
    "success": true,
    "message": "Additional fee added and distributed successfully",
    "new_net_amount": 20500.00
}
```

### Auto-Distribution
If Prelim is paid and ₱500 is added:
- Midterm: +₱166.67
- Pre-Final: +₱166.67
- Final: +₱166.66

---

## 4. Approve Scholarship

### Endpoint
```
POST /api/approve-scholarship.php
```

### Authentication
- **Required Role**: Assessment

### Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `scholarship_id` | Integer | Yes | Scholarship ID |
| `student_scholarship_id` | Integer | Yes | Student scholarship ID |
| `approved` | String | Yes | "true" or "false" |
| `notes` | String | No | Approval notes |

### Example Request
```php
$data = [
    'scholarship_id' => 3,
    'student_scholarship_id' => 12,
    'approved' => 'true',
    'notes' => 'Approved - Financial need verified'
];
```

### Success Response
```json
{
    "success": true,
    "message": "Scholarship approved for ₱5000 successfully"
}
```

### What Happens (Approval)
1. ✅ Status changed to "active"
2. ✅ Deduction applied to enrollment net_amount
3. ✅ Remaining payment schedules adjusted
4. ✅ Activity logged

### What Happens (Rejection)
1. ✅ Status changed to "cancelled"
2. ✅ No deduction applied
3. ✅ Activity logged

---

## 5. Common Error Responses

### Unauthorized Access
```json
{
    "success": false,
    "message": "Unauthorized"
}
```

### Invalid Request Method
```json
{
    "success": false,
    "message": "Invalid request method"
}
```

### Missing Required Fields
```json
{
    "success": false,
    "message": "All fields are required"
}
```

### Record Not Found
```json
{
    "success": false,
    "message": "[Entity type] not found"
}
```

### Duplicate Entry
```json
{
    "success": false,
    "message": "Student already enrolled for this semester"
}
```

---

## Integration Examples

### JavaScript/jQuery

```javascript
// Record Payment
$.ajax({
    type: 'POST',
    url: '/school-enrollment/api/record-payment.php',
    data: {
        schedule_id: 5,
        amount_paid: 5000,
        payment_method: 'Cash',
        notes: 'Prelim payment'
    },
    success: function(response) {
        const data = JSON.parse(response);
        if (data.success) {
            alert('Payment recorded: ' + data.payment_id);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    }
});
```

### PHP Form Handler

```php
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Prepare data
    $data = [
        'schedule_id' => $_POST['schedule_id'],
        'amount_paid' => $_POST['amount_paid'],
        'payment_method' => $_POST['method'],
        'notes' => $_POST['notes']
    ];
    
    // Call API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/school-enrollment/api/record-payment.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    echo $result['message'];
}
?>
```

---

## Response Codes

| HTTP Code | Meaning |
|-----------|---------|
| 200 | Request processed (check JSON for success) |
| 400 | Bad request (missing parameters) |
| 401 | Unauthorized (login required) |
| 403 | Forbidden (insufficient permissions) |
| 500 | Server error (check logs) |

---

## Database Transaction Safety

All API endpoints use database transactions:
- **BEGIN**: Transaction starts
- **OPERATION**: INSERT/UPDATE queries execute
- **COMMIT**: Changes saved (if all succeed)
- **ROLLBACK**: All changes undone (if any fails)

This ensures **data consistency** across all related tables.

---

## Activity Logging

Every API call creates an activity log entry with:
- User ID
- Action performed
- Entity type and ID
- Timestamp
- Additional details

**View logs**: Admin Dashboard → Activity Log

---

## Rate Limiting Notes

Currently no rate limiting is implemented. For production:
1. Implement rate limiting (max 100 requests/minute per user)
2. Add CSRF token protection
3. Implement API keys for integrations
4. Log all API access

---

## Webhook Integration (Future)

Once integrations are needed:
```php
// Trigger webhook
$webhook_url = getWebhookUrl('payment_recorded');
triggerWebhook($webhook_url, $payment_data);
```

---

## Versioning

Current API Version: **1.0**

Future versions will maintain backward compatibility:
- `v1` endpoints: Legacy support
- `v2` endpoints: New features

---

**Last Updated**: 2024
**API Version**: 1.0.0
