# Mini Digital Wallet

A high-performance digital wallet system built with Laravel and Vue.js, featuring real-time transaction updates via Pusher.

## Features

- 💰 **Digital Wallet Management**: Send and receive money with real-time balance updates
- 🔒 **Concurrency-Safe Transactions**: Row-level locking ensures balance consistency under high load
- ⚡ **Real-Time Updates**: Instant balance and transaction updates via Pusher broadcasting
- 💳 **Transaction History**: View all incoming and outgoing transactions
- 📊 **Commission System**: Automatic 1.5% commission on all transfers
- 🎨 **Modern UI**: Clean, responsive interface built with Vue.js 3 and Tailwind CSS

## Tech Stack

- **Backend**: Laravel 12
- **Frontend**: Vue.js 3 (Composition API)
- **Database**: MySQL/PostgreSQL
- **Real-Time**: Laravel Broadcasting + Pusher
- **Authentication**: Laravel Sanctum

## Requirements

- PHP ^8.2
- Composer
- Node.js & npm
- MySQL 8.0+ or PostgreSQL
- Pusher account (for real-time features)

## Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd mini-digital-wallet
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install frontend dependencies**
   ```bash
   npm install
   ```

4. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Update `.env` file with your configuration:**
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

6. **Run migrations**
   ```bash
   php artisan migrate
   ```

7. **Build frontend assets**
   ```bash
   npm run build
   ```

8. **Start the development server**
   ```bash
   php artisan serve
   npm run dev
   ```

## API Endpoints

All API endpoints require authentication via Laravel Sanctum.

### Get Transactions

**GET** `/api/transactions`

Returns all transactions (incoming and outgoing) for the authenticated user, along with current balance.

**Query Parameters:**
- `per_page` (optional): Number of transactions per page (default: 15)

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

**Error Responses (422):**
- Insufficient balance
- Invalid receiver
- Self-transfer attempt
- Validation errors

## Real-Time Broadcasting

The application uses Laravel Broadcasting with Pusher to provide real-time updates:

- **Event**: `TransactionCompleted`
- **Channels**: Private channels `user.{userId}` for each user
- **Event Name**: `transaction.completed`

When a transaction is completed, both sender and receiver receive a broadcast event with:
- Transaction details
- Updated balances for both users

## Database Schema

### Users Table
- `id` (primary key)
- `name`
- `email` (unique)
- `password`
- `balance` (decimal 15,2, default 0)
- `timestamps`

### Transactions Table
- `id` (primary key)
- `sender_id` (foreign key to users)
- `receiver_id` (foreign key to users)
- `amount` (decimal 15,2)
- `commission_fee` (decimal 15,2)
- `timestamps`

## Commission System

- **Commission Rate**: 1.5% of transfer amount
- **Sender Pays**: Transfer amount + 1.5% commission
- **Receiver Gets**: Transfer amount only (no commission)

Example: Transferring $100.00
- Sender pays: $101.50 ($100 + $1.50 commission)
- Receiver gets: $100.00

## Concurrency Safety

The system uses the following mechanisms to ensure balance consistency:

1. **Database Transactions**: All operations wrapped in transactions
2. **Row-Level Locking**: `SELECT ... FOR UPDATE` on user rows
3. **Double-Check Pattern**: Balance verified after acquiring locks
4. **Atomic Operations**: All-or-nothing execution

This ensures the system can handle hundreds of transfers per second without balance inconsistencies.

## Testing

Run the test suite:

```bash
php artisan test
```

Test coverage includes:
- Concurrent transaction handling
- Balance consistency verification
- Real-time broadcasting
- Error handling

## Frontend Development

The frontend is built with Vue.js 3 using the Composition API.

**Key Components:**
- `WalletDashboard.vue`: Main dashboard container
- `BalanceDisplay.vue`: Shows current balance
- `TransferForm.vue`: Form for sending money
- `TransactionList.vue`: Displays transaction history
- `NotificationContainer.vue`: Shows success/error notifications

**Services:**
- `api.js`: Axios-based API client
- `pusher.js`: Pusher/Echo integration for real-time updates
- `formatters.js`: Currency and date formatting utilities

## Environment Variables

### Database
- `DB_CONNECTION`: Database driver (mysql/pgsql)
- `DB_HOST`: Database host
- `DB_PORT`: Database port
- `DB_DATABASE`: Database name
- `DB_USERNAME`: Database username
- `DB_PASSWORD`: Database password

### Broadcasting
- `BROADCAST_CONNECTION`: pusher
- `PUSHER_APP_ID`: Pusher application ID
- `PUSHER_APP_KEY`: Pusher application key
- `PUSHER_APP_SECRET`: Pusher application secret
- `PUSHER_APP_CLUSTER`: Pusher cluster (e.g., mt1)
- `PUSHER_HOST`: Pusher host (optional)
- `PUSHER_PORT`: Pusher port (default: 443)
- `PUSHER_SCHEME`: https

### Frontend (Vite)
- `VITE_PUSHER_APP_KEY`: Pusher key for frontend
- `VITE_PUSHER_APP_CLUSTER`: Pusher cluster for frontend

## Security

- API authentication via Laravel Sanctum
- Private broadcasting channels with authorization
- Input validation on all endpoints
- SQL injection protection via Eloquent ORM
- CSRF protection for web routes

## Performance

- Optimized for high concurrency (hundreds of transfers/second)
- Row-level locking prevents race conditions
- Efficient database queries with eager loading
- Real-time updates via Pusher (no polling)

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
