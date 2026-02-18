# Invoice System Backend

Laravel REST API for managing invoices, quotations, and customers.

## Features
- Authentication with Laravel Sanctum
- Customer management
- Invoice creation and management
- Quotation creation and management
- PostgreSQL database

## Setup
1. Clone the repository
2. Install dependencies: `composer install`
3. Copy `.env.example` to `.env`
4. Configure database in `.env`
5. Run migrations: `php artisan migrate`
6. Start server: `php artisan serve`

## API Endpoints
- POST `/api/auth/register` - Register user
- POST `/api/auth/login` - Login user
- GET `/api/customers` - Get all customers
- GET `/api/invoices` - Get all invoices
- GET `/api/quotations` - Get all quotations
