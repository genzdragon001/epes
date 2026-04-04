# EPES API Documentation
## Employee Performance Evaluation System - RESTful API Reference
### Version 2.0.0

---

## Base URL
```
http://localhost/epes/api/
```

## Authentication
All API requests require authentication via session token or API key.

### Headers
```
Content-Type: application/json
Authorization: Bearer {session_token}
```

---

## Endpoints

### Authentication

#### POST /auth/login
Login user to the system

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "login_type": 0
}
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "user_id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "role": "faculty",
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}
```

#### POST /auth/logout
Logout current user

**Response:**
```json
{
  "status": "success",
  "message": "Logged out successfully"
}
```

---

### Faculty Management

#### GET /faculty
Get list of all faculty members

**Query Parameters:**
- `page` (integer): Page number for pagination
- `limit` (integer): Items per page (default: 20)
- `department_id` (integer): Filter by department
- `search` (string): Search by name or email

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "employee_id": "EMP-001",
      "firstname": "John",
      "lastname": "Doe",
      "email": "john.doe@university.edu",
      "department": "Computer Science",
      "position": "Instructor",
      "designation": "Department Head"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 5,
    "total_items": 100
  }
}
```

#### GET /faculty/{id}
Get specific faculty member details

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "employee_id": "EMP-001",
    "firstname": "John",
    "lastname": "Doe",
    "email": "john.doe@university.edu",
    "department_id": 1,
    "designation_id": 1,
    "position_id": 1,
    "hire_date": "2020-01-15",
    "employment_status": "Permanent"
  }
}
```

#### POST /faculty
Create new faculty member

**Request Body:**
```json
{
  "employee_id": "EMP-002",
  "firstname": "Jane",
  "lastname": "Smith",
  "email": "jane.smith@university.edu",
  "password": "securepassword",
  "department_id": 1,
  "designation_id": 2,
  "position_id": 3,
  "hire_date": "2024-01-01",
  "employment_status": "COS"
}
```

#### PUT /faculty/{id}
Update faculty member

#### DELETE /faculty/{id}
Delete faculty member

---

### Task Management

#### GET /tasks
Get list of tasks

**Query Parameters:**
- `category` (string): Filter by category (Core, Support, Strategic)
- `sub_category` (string): Filter by sub-category
- `is_active` (boolean): Filter active/inactive tasks

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "task": "Develop Course Curriculum",
      "description": "Create comprehensive curriculum for CS101",
      "category": "Core",
      "sub_category": "Instructions",
      "weight": 30.00,
      "success_indicators": "Approved curriculum document",
      "is_active": true
    }
  ]
}
```

#### GET /tasks/{id}/submissions
Get all submissions for a task

#### POST /tasks/{id}/submit
Submit task accomplishment

**Request Body:**
```json
{
  "progress": "For Verification",
  "file_path": "/uploads/document.pdf",
  "file_type": "pdf",
  "notes": "Completed curriculum development"
}
```

---

### Performance Ratings

#### GET /ratings/faculty/{id}
Get ratings for a faculty member

**Query Parameters:**
- `semester` (string): Filter by semester
- `year` (string): Filter by year

**Response:**
```json
{
  "status": "success",
  "data": {
    "faculty_id": 1,
    "faculty_name": "John Doe",
    "period": "1st Semester 2024-2025",
    "ratings": {
      "efficiency": 4.5,
      "timeliness": 4.3,
      "quality": 4.7,
      "overall": 4.5
    },
    "adjectival_rating": "Very Satisfactory",
    "total_tasks": 15,
    "verified_tasks": 12
  }
}
```

#### POST /ratings
Submit or update rating

**Request Body:**
```json
{
  "employee_id": 1,
  "task_id": 5,
  "efficiency": 4.5,
  "timeliness": 4.0,
  "quality": 5.0,
  "remarks": "Excellent work quality"
}
```

---

### IPCR Reports

#### GET /ipcr/faculty/{id}
Generate IPCR report for faculty

**Query Parameters:**
- `semester` (string, required): Semester
- `year` (string, required): Year
- `format` (string): Output format (html, pdf, json)

**Response (JSON):**
```json
{
  "status": "success",
  "data": {
    "faculty": {
      "name": "John Doe",
      "position": "Instructor",
      "department": "Computer Science"
    },
    "period": "1st Semester 2024-2025",
    "ratings": {
      "instruction": 4.5,
      "research": 4.2,
      "extension": 4.0,
      "production": 3.8,
      "overall": 4.125
    },
    "adjectival_rating": "Very Satisfactory",
    "tasks": [...],
    "strengths": "...",
    "areas_for_improvement": "..."
  }
}
```

#### GET /ipcr/export/{id}
Export IPCR as PDF

---

### OPCR Reports

#### GET /opcr/department/{id}
Get OPCR summary for department

**Response:**
```json
{
  "status": "success",
  "data": {
    "department": "Computer Science",
    "period": "1st Semester 2024-2025",
    "total_faculty": 15,
    "metrics": {
      "avg_efficiency": 4.3,
      "avg_timeliness": 4.1,
      "avg_quality": 4.5,
      "overall_average": 4.3
    },
    "faculty_performance": [...]
  }
}
```

---

### Recommendations

#### GET /recommendations
Get all renewal recommendations

#### POST /recommendations
Create renewal recommendation

**Request Body:**
```json
{
  "faculty_id": 1,
  "rating_period": "1st Semester 2024-2025",
  "overall_score": 4.5,
  "recommendation_status": "Recommended",
  "system_generated_reason": "High performance across all criteria"
}
```

#### PUT /recommendations/{id}/decision
Submit dean's decision

**Request Body:**
```json
{
  "dean_decision": "Approved",
  "dean_reason": "Excellent performance record"
}
```

---

### Analytics

#### GET /analytics/dashboard
Get dashboard analytics

**Response:**
```json
{
  "status": "success",
  "data": {
    "total_faculty": 150,
    "total_tasks": 450,
    "verified_tasks": 380,
    "pending_verification": 45,
    "avg_overall_rating": 4.2,
    "department_performance": [...],
    "rating_distribution": {...}
  }
}
```

#### GET /analytics/trends
Get performance trends

**Query Parameters:**
- `type` (string): faculty, department, institutional
- `periods` (integer): Number of periods to include

---

### Notifications

#### GET /notifications
Get user notifications

#### POST /notifications/mark-read
Mark notification as read

**Request Body:**
```json
{
  "notification_id": 123
}
```

---

### System

#### GET /system/settings
Get system settings

#### PUT /system/settings
Update system settings

#### GET /system/backup
Create database backup

#### POST /system/backup/restore
Restore from backup

---

## Error Responses

### Standard Error Format
```json
{
  "status": "error",
  "code": "VALIDATION_ERROR",
  "message": "Invalid input data",
  "errors": [
    {
      "field": "email",
      "message": "Invalid email format"
    }
  ]
}
```

### HTTP Status Codes
- `200` OK - Request successful
- `201` Created - Resource created successfully
- `400` Bad Request - Invalid input
- `401` Unauthorized - Authentication required
- `403` Forbidden - Insufficient permissions
- `404` Not Found - Resource not found
- `500` Internal Server Error - Server error

---

## Rate Limiting
- Maximum 100 requests per minute per user
- Maximum 1000 requests per hour per user

---

## Support
For API support, contact: api-support@epes.edu.ph
