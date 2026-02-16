# Inspector Scheduling API

A RESTful API for managing inspector job assignments across multiple timezones (UK, Mexico, and India).

## Features

- **Authentication**: JWT-based authentication with register and login endpoints
- **Job Management**: Create and list jobs
- **Job Assignment**: Assign jobs with timezone-aware scheduling
- **Job Completion**: Complete jobs with assessment notes
- **Multi-timezone Support**: Automatic conversion between local timezones and UTC

## Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher
- Composer

## Installation

1. Clone the repository:
```bash
cd inspector-scheduling-api
```

2. Install dependencies:
```bash
composer install
```

3. Set up the database:
```bash
mysql -u root -p < database/schema.sql
```

4. Configure environment variables:
```bash
cp .env.example .env
```

Edit `.env` and update the database credentials and JWT secret:
```env
DB_HOST=localhost
DB_NAME=inspector_scheduling
DB_USER=root
DB_PASS=your_password
JWT_SECRET=your-strong-secret-key
JWT_EXPIRY=3600
```

5. Start the development server:
```bash
composer start
```

Or use PHP's built-in server:
```bash
php -S localhost:8000 -t public
```

The API will be available at `http://localhost:8000`

## API Endpoints

### Authentication

#### Register Inspector
```http
POST /api/register
Content-Type: application/json

{
  "email": "inspector@example.com",
  "password": "securepassword",
  "name": "John Doe",
  "location": "UK"
}
```

Valid locations: `UK`, `MEXICO`, `INDIA`

#### Login
```http
POST /api/login
Content-Type: application/json

{
  "email": "inspector@example.com",
  "password": "securepassword"
}
```

Returns:
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "inspector": {
    "id": 1,
    "email": "inspector@example.com",
    "name": "John Doe",
    "location": "UK"
  }
}
```

### Jobs (Requires Authentication)

All job endpoints require the `Authorization` header:
```
Authorization: Bearer <your-jwt-token>
```

#### List Jobs
```http
GET /api/jobs
Authorization: Bearer <token>
```

#### Create Job
```http
POST /api/jobs
Authorization: Bearer <token>
Content-Type: application/json

{
  "title": "Building Inspection",
  "description": "Inspect the foundation and structure"
}
```

#### Assign Job
```http
POST /api/jobs/{id}/assign
Authorization: Bearer <token>
Content-Type: application/json

{
  "scheduled_at": "2026-02-20 14:00"
}
```

The `scheduled_at` time should be in your local timezone. The system will automatically convert it to UTC.

#### Complete Job
```http
PATCH /api/jobs/{id}/complete
Authorization: Bearer <token>
Content-Type: application/json

{
  "assessment": "Building structure is sound. Minor repairs needed on north wall."
}
```

## Timezone Support

The API supports three timezones:
- **UK**: Europe/London (GMT/BST)
- **MEXICO**: America/Mexico_City (CST/CDT)
- **INDIA**: Asia/Kolkata (IST)

When assigning a job, provide the time in your local timezone. The system automatically:
1. Converts the scheduled time to UTC for storage
2. Converts back to the requesting inspector's timezone when displaying

## Project Structure

```
inspector-scheduling-api/
├── public/
│   └── index.php              # Entry point
├── src/
│   ├── Controllers/
│   │   ├── AuthController.php # Authentication logic
│   │   └── JobController.php  # Job management logic
│   ├── Models/
│   │   ├── Assignment.php     # Assignment model
│   │   ├── Inspector.php      # Inspector model
│   │   └── Job.php            # Job model
│   ├── Services/
│   │   ├── JWTService.php     # JWT token handling
│   │   └── TimezoneService.php # Timezone conversions
│   ├── Database.php           # Database connection
│   └── Router.php             # Request router
├── database/
│   └── schema.sql             # Database schema
├── .env.example               # Environment variables template
├── .gitignore
├── composer.json
└── README.md
```

## Database Schema

### inspectors
- `id`: Primary key
- `email`: Unique email address
- `password`: Hashed password
- `name`: Inspector name
- `location`: ENUM('UK', 'MEXICO', 'INDIA')
- `created_at`, `updated_at`: Timestamps

### jobs
- `id`: Primary key
- `title`: Job title
- `description`: Job description
- `status`: ENUM('available', 'assigned', 'completed')
- `created_at`, `updated_at`: Timestamps

### assignments
- `id`: Primary key
- `job_id`: Foreign key to jobs
- `inspector_id`: Foreign key to inspectors
- `scheduled_at`: Scheduled time in local timezone
- `scheduled_at_utc`: Scheduled time in UTC
- `completed_at`: Completion timestamp
- `assessment`: Assessment notes
- `created_at`, `updated_at`: Timestamps

## Example Usage

1. **Register an inspector:**
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "uk.inspector@example.com",
    "password": "password123",
    "name": "Alice Smith",
    "location": "UK"
  }'
```

2. **Login:**
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "uk.inspector@example.com",
    "password": "password123"
  }'
```

3. **Create a job:**
```bash
curl -X POST http://localhost:8000/api/jobs \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "title": "Office Inspection",
    "description": "Annual safety inspection"
  }'
```

4. **Assign the job:**
```bash
curl -X POST http://localhost:8000/api/jobs/1/assign \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "scheduled_at": "2026-02-25 10:00"
  }'
```

5. **Complete the job:**
```bash
curl -X PATCH http://localhost:8000/api/jobs/1/complete \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "assessment": "All safety requirements met."
  }'
```

## Security Notes

- Change the `JWT_SECRET` in `.env` to a strong, random string in production
- Use HTTPS in production
- Passwords are hashed using PHP's `password_hash()` with bcrypt
- JWT tokens expire after the duration set in `JWT_EXPIRY` (default: 3600 seconds / 1 hour)

## Testing

You can test the API using tools like:
- cURL (command line)
- Postman
- Insomnia
- VS Code REST Client extension

## License

MIT License
