# Mini Digital Wallet

A high-performance digital wallet system built with Laravel and Vue.js, featuring real-time transaction updates via Pusher.

## Features

The application provides digital wallet management with the ability to send and receive money with real-time balance updates. Transactions are concurrency-safe using row-level locking to ensure balance consistency under high load. The system provides instant balance and transaction updates via Pusher broadcasting. Users can view all incoming and outgoing transactions in their history. The system automatically applies a 1.5% commission on all transfers. The interface is built with Vue.js 3 and Tailwind CSS for a clean, responsive experience.

## Tech Stack

Backend is built with Laravel 12. The frontend uses Vue.js 3 with the Composition API. The database supports both MySQL and PostgreSQL. Real-time features are implemented using Laravel Broadcasting with Pusher. Authentication is handled by Laravel Sanctum.

## Requirements

You'll need PHP 8.2 or higher, Composer for dependency management, Node.js and npm for frontend assets, and either MySQL 8.0+ or PostgreSQL for the database. A Pusher account is required for real-time features.

## Installation

Start by cloning the repository and navigating into the project directory:

```bash
git clone <repository-url>
cd mini-digital-wallet
```

Install PHP dependencies using Composer:

```bash
composer install
```

Install frontend dependencies:

```bash
npm install
```

Configure your environment by copying the example file and generating an application key:

```bash
cp .env.example .env
php artisan key:generate
```

Update the `.env` file with your configuration:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mini_wallet
DB_USERNAME=your_username
DB_PASSWORD=your_password

BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_app_key
PUSHER_APP_SECRET=your_pusher_app_secret
PUSHER_APP_CLUSTER=mt1
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
```

Run the database migrations:

```bash
php artisan migrate
```

Build the frontend assets:

```bash
npm run build
```

Start the development server. In one terminal, run the Laravel server:

```bash
php artisan serve
```

In another terminal, start the Vite dev server:

```bash
npm run dev
```

## API Endpoints

All API endpoints require authentication via Laravel Sanctum.

### Get Transactions

**GET** `/api/transactions`

Returns all transactions (incoming and outgoing) for the authenticated user, along with current balance.

Query Parameters:

The `per_page` parameter is optional and controls the number of transactions per page. The default is 15.

**Response:**
```json
{
  "balance": 1000.50,
  "transactions": {
    "data": [
      {
        "id": 1,
        "sender_id": 1,
        "receiver_id": 2,
        "amount": "100.00",
        "commission_fee": "1.50",
        "created_at": "2024-11-25T10:00:00.000000Z",
        "sender": {
          "id": 1,
          "name": "John Doe",
          "email": "john@example.com"
        },
        "receiver": {
          "id": 2,
          "name": "Jane Smith",
          "email": "jane@example.com"
        }
      }
    ],
    "current_page": 1,
    "last_page": 1
  }
}
```

### Create Transaction

**POST** `/api/transactions`

Creates a new money transfer transaction.

**Request Body:**
```json
{
  "receiver_id": 2,
  "amount": 100.00
}
```

**Response (201):**
```json
{
  "message": "Transaction completed successfully.",
  "transaction": {
    "id": 1,
    "sender_id": 1,
    "receiver_id": 2,
    "amount": "100.00",
    "commission_fee": "1.50",
    "created_at": "2024-11-25T10:00:00.000000Z",
    "sender": {...},
    "receiver": {...}
  },
  "balance": 898.50
}
```

Error Responses (422):

The API will return a 422 status code with an error message for insufficient balance, invalid receiver, self-transfer attempts, or validation errors.

## Real-Time Broadcasting

The application uses Laravel Broadcasting with Pusher to provide real-time updates. The `TransactionCompleted` event is broadcast on private channels named `user.{userId}` for each user. The event name is `transaction.completed`.

When a transaction is completed, both sender and receiver receive a broadcast event containing the transaction details and updated balances for both users.

## Database Schema

### Users Table

The users table includes an id as the primary key, name, email which must be unique, password, balance stored as decimal 15,2 with a default of 0, and standard timestamps.

### Transactions Table

The transactions table has an id as the primary key, sender_id and receiver_id as foreign keys to the users table, amount and commission_fee both stored as decimal 15,2, and standard timestamps.

## Commission System

The commission rate is 1.5% of the transfer amount. The sender pays the transfer amount plus the 1.5% commission, while the receiver gets the transfer amount only with no commission deducted.

For example, when transferring $100.00, the sender pays $101.50 (the $100 transfer plus $1.50 commission), and the receiver gets the full $100.00.

## Concurrency Safety

The system uses several mechanisms to ensure balance consistency. All operations are wrapped in database transactions. Row-level locking is implemented using `SELECT ... FOR UPDATE` on user rows. The balance is verified after acquiring locks using a double-check pattern. All operations are atomic, meaning they either complete entirely or roll back completely.

This ensures the system can handle hundreds of transfers per second without balance inconsistencies.

## Testing

Run the test suite:

```bash
php artisan test
```

Test coverage includes concurrent transaction handling, balance consistency verification, real-time broadcasting, and error handling.

## Frontend Development

The frontend is built with Vue.js 3 using the Composition API.

Key components include WalletDashboard.vue which serves as the main dashboard container, BalanceDisplay.vue for showing the current balance, TransferForm.vue for the money transfer form, TransactionList.vue for displaying transaction history, and NotificationContainer.vue for showing success and error notifications.

Services include api.js which provides an Axios-based API client, pusher.js for Pusher/Echo integration with real-time updates, and formatters.js for currency and date formatting utilities.

## Environment Variables

### Database

Set `DB_CONNECTION` to your database driver (mysql or pgsql), `DB_HOST` to your database host, `DB_PORT` to the database port, `DB_DATABASE` to your database name, `DB_USERNAME` to your database username, and `DB_PASSWORD` to your database password.

### Broadcasting

Set `BROADCAST_CONNECTION` to pusher. Configure `PUSHER_APP_ID` with your Pusher application ID, `PUSHER_APP_KEY` with your Pusher application key, `PUSHER_APP_SECRET` with your Pusher application secret, and `PUSHER_APP_CLUSTER` with your Pusher cluster (e.g., mt1). The `PUSHER_HOST` is optional, `PUSHER_PORT` defaults to 443, and `PUSHER_SCHEME` should be set to https.

### Frontend (Vite)

For the frontend, set `VITE_PUSHER_APP_KEY` with your Pusher key and `VITE_PUSHER_APP_CLUSTER` with your Pusher cluster.

## Security

API authentication is handled via Laravel Sanctum. Private broadcasting channels require authorization. All endpoints have input validation. SQL injection is prevented through the use of Eloquent ORM. CSRF protection is enabled for web routes.

## Performance

The system is optimized for high concurrency and can handle hundreds of transfers per second. Row-level locking prevents race conditions. Database queries are efficient with eager loading. Real-time updates are provided via Pusher, eliminating the need for polling.

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
