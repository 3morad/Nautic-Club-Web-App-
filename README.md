# Nautic Club Web Project

A comprehensive web application for managing a nautical club's operations, including ticket management, feedback system, and admin functionalities.

## Table of Contents

1. [Project Overview](#project-overview)
2. [System Architecture](#system-architecture)
3. [Entity Relationships](#entity-relationships)
4. [Feature Documentation](#feature-documentation)
5. [Page-by-Page Documentation](#page-by-page-documentation)
6. [API Documentation](#api-documentation)
7. [Database Schema](#database-schema)
8. [Setup & Installation](#setup--installation)
9. [Developer Guidelines](#developer-guidelines)
10. [Command Reference](#command-reference)
11. [Security Implementation](#security-implementation)
12. [Performance Considerations](#performance-considerations)
13. [Testing](#testing)
14. [Deployment](#deployment)
15. [Support](#support)

## Project Overview

This Symfony-based web application provides a complete solution for managing a nautical club's operations, featuring:
- Event and support ticket management
- Customer feedback system with ratings and comments
- Transaction processing with multiple payment methods
- Admin dashboard for complete business oversight
- User management with role-based permissions
- API endpoints for external integrations

### Technology Stack

- **Backend**: Symfony 6.2
- **Frontend**: Twig, JavaScript, Tailwind CSS
- **Database**: MySQL 8.0
- **Security**: Symfony Security Component
- **Asset Management**: Webpack Encore
- **Testing**: PHPUnit

## System Architecture

### Directory Structure

```
/src
│
├── Controller/                # HTTP request handlers
│   ├── Admin/                 # Admin-specific controllers
│   │   ├── FeedbackController.php
│   │   ├── TicketController.php
│   │   └── UserController.php
│   ├── FeedbackController.php
│   ├── TicketController.php
│   └── TransactionController.php
│
├── Entity/                    # Database entity classes
│   ├── Feedback.php
│   ├── Ticket.php
│   ├── Transaction.php
│   └── User.php
│
├── Form/                      # Form handling classes
│   ├── AdminFeedbackType.php
│   ├── FeedbackType.php
│   ├── TicketType.php
│   └── UserType.php
│
├── Repository/                # Database query classes
│   ├── FeedbackRepository.php
│   ├── TicketRepository.php
│   ├── TransactionRepository.php
│   └── UserRepository.php
│
├── Security/                  # Security-related classes
│   └── AdminAuthenticator.php
│
└── Command/                   # Console commands
    ├── CreateAdminCommand.php
    └── AddFeedbackPlaceholdersCommand.php
```

```
/templates
│
├── admin/                     # Admin panel templates
│   ├── base.html.twig         # Admin layout base
│   ├── dashboard/             # Dashboard views
│   ├── feedback/              # Feedback management
│   ├── tickets/               # Ticket management
│   ├── transactions/          # Transaction management
│   └── users/                 # User management
│
├── feedback/                  # Public feedback templates
│   ├── index.html.twig        # Feedback display
│   └── form.html.twig         # Feedback submission
│
├── ticket/                    # Ticket templates
│   ├── index.html.twig        # Ticket listing
│   └── show.html.twig         # Ticket details
│
└── transaction/               # Transaction templates
    └── index.html.twig        # Transaction history
```

## Entity Relationships

### Entity Relationship Diagram

```
┌─────────┐      ┌───────────┐      ┌──────────────┐
│   User  │──┬──<│ Feedback  │      │ Transaction  │
└─────────┘  │   └───────────┘      └──────────────┘
     │       │         │                   │
     │       │         │                   │
     │       │         │                   │
     └───────┴─────────┼───────────────────┘
                       │
                       │
                  ┌─────────┐
                  │ Ticket  │
                  └─────────┘
```

### User Entity (`src/Entity/User.php`)

- **Description**: Represents users of the system (customers and admins)
- **Properties**:
  - `id`: INT - Primary key
  - `email`: VARCHAR(180) - Unique email address
  - `roles`: JSON - User roles (ROLE_USER, ROLE_ADMIN)
  - `password`: VARCHAR(255) - Hashed password
  - `firstName`: VARCHAR(255) - User's first name
  - `lastName`: VARCHAR(255) - User's last name
- **Relationships**:
  - OneToMany with Feedback
  - OneToMany with Transaction
- **Usage**: User authentication, profile management, and determining access privileges

### Feedback Entity (`src/Entity/Feedback.php`)

- **Description**: Customer feedback for events or general service
- **Properties**:
  - `id`: INT - Primary key
  - `user`: ManyToOne - Related User
  - `rating`: INT - 1-5 star rating
  - `comment`: TEXT - Feedback content
  - `createdAt`: DATETIME - Creation timestamp
  - `username`: VARCHAR(255) - Display name
  - `photoPath`: VARCHAR(255) - Optional user photo
  - `eventName`: VARCHAR(255) - Related event name
  - `isAdmin`: BOOLEAN - Whether feedback is from admin
- **Relationships**:
  - ManyToOne with User
  - ManyToOne with Transaction (optional)
- **Usage**: Collecting and displaying customer testimonials and event reviews

### Ticket Entity (`src/Entity/Ticket.php`)

- **Description**: Dual-purpose entity for event tickets and support tickets
- **Properties**:
  - `id`: INT - Primary key
  - `ticketType`: VARCHAR(255) - Type of ticket (Regular, VIP, Exclusive)
  - `price`: DECIMAL(10,2) - Ticket price (for event tickets)
  - `eventName`: VARCHAR(255) - Event name (for event tickets)
  - `eventDate`: DATETIME - Event date (for event tickets)
  - `title`: VARCHAR(255) - Ticket title
  - `description`: TEXT - Detailed description
  - `category`: VARCHAR(255) - Ticket category
  - `status`: VARCHAR(255) - Current status
  - `priority`: VARCHAR(255) - Priority level (for support tickets)
  - `createdBy`: VARCHAR(255) - Creator information
  - `createdAt`: DATETIME - Creation timestamp
  - `isSupport`: BOOLEAN - Whether it's a support ticket
- **Relationships**:
  - ManyToOne with Transaction (optional)
- **Usage**: Managing event ticket sales and handling support requests

### Transaction Entity (`src/Entity/Transaction.php`)

- **Description**: Represents financial transactions in the system
- **Properties**:
  - `id`: INT - Primary key
  - `amount`: DECIMAL(10,2) - Transaction amount
  - `status`: VARCHAR(255) - Transaction status
  - `createdAt`: DATETIME - Creation timestamp
- **Relationships**:
  - ManyToOne with User
  - OneToMany with Ticket
  - OneToMany with Feedback
- **Usage**: Processing payments, tracking financial records, linking purchases to users

## Feature Documentation

### User Management System

#### Role System
- **ROLE_USER**: Basic privileges for regular customers
  - Access to public pages
  - Submit feedback
  - Purchase tickets
  - View purchase history
- **ROLE_ADMIN**: Extended privileges for staff
  - Access to admin dashboard
  - Manage users, feedback, tickets, transactions
  - Generate reports
  - Configure system settings

#### Authentication Flow
1. User navigates to login page
2. Credentials verified against database
3. On success, redirected to appropriate dashboard
4. On failure, error displayed with retry option

#### User Registration Process
1. User completes registration form
2. System validates form data
3. Password hashed before storage
4. Verification email sent (if enabled)
5. User account created with ROLE_USER

#### Admin User Management
- **Path**: `/admin/users/`
- **Controller**: `Admin/UserController.php`
- **Template**: `admin/users/index.html.twig`
- **Features**:
  - List all users with pagination
  - Create new users
  - Edit existing user details
  - Change user roles
  - Delete users

### Feedback System

#### Public Feedback Interface
- **Path**: `/feedback/`
- **Controller**: `FeedbackController.php`
- **Template**: `feedback/index.html.twig`
- **Features**:
  - Display paginated feedback entries
  - Filter by event
  - Star rating visualization
  - Photo display
  - Submit new feedback form
  - Featured feedback carousel

#### Feedback Submission Process
1. User opens feedback form
2. Selects event (if applicable)
3. Provides rating (1-5 stars)
4. Writes comment
5. Optionally uploads photo
6. Submits form
7. Validation performed server-side
8. Feedback saved and displayed

#### Admin Feedback Management
- **Path**: `/admin/feedback/`
- **Controller**: `Admin/FeedbackController.php`
- **Template**: `admin/feedback/index.html.twig`
- **Features**:
  - List all feedback with filtering options
  - Create feedback entries
  - Edit existing feedback
  - Delete feedback
  - Generate placeholder feedback (for testing)

### Ticket Management

#### Event Ticket Types
- **Regular Ticket**: Standard admission
- **VIP Experience**: Premium features and services
- **Exclusive Ticket**: Limited availability special access

#### Support Ticket Categories
- **General**: General inquiries
- **Technical**: Technical assistance
- **Billing**: Payment issues
- **Support**: Other support needs

#### Support Ticket Priority Levels
- **Low**: Non-critical issues
- **Medium**: Standard priority
- **High**: Urgent matters requiring prompt attention
- **Urgent**: Critical issues requiring immediate resolution

#### Ticket Creation Flow
1. User selects ticket type (event or support)
2. For event tickets:
   - Selects event and ticket type
   - Views price information
   - Proceeds to checkout
3. For support tickets:
   - Provides title and description
   - Selects category and priority
   - Submits ticket
4. Confirmation email sent to user
5. Ticket appears in appropriate dashboard

#### Admin Ticket Management
- **Path**: `/admin/tickets/`
- **Controller**: `Admin/TicketController.php`
- **Template**: `admin/tickets/index.html.twig`
- **Features**:
  - List all tickets with filtering options
  - Create new tickets
  - Edit ticket details
  - Change ticket status
  - Delete tickets

### Transaction System

#### Payment Methods
- **Credit Card**: Integrated with payment processor
- **Cash**: Manual transaction recording
- **QR Code**: Mobile payment integration

#### Transaction Statuses
- **Pending**: Initiated but not completed
- **Completed**: Successfully processed
- **Failed**: Processing error occurred
- **Refunded**: Amount returned to customer

#### Transaction Flow
1. User selects items to purchase
2. System calculates total amount
3. User chooses payment method
4. Transaction created with "Pending" status
5. Payment processing occurs
6. Transaction status updated based on result
7. Confirmation sent to user
8. Items linked to transaction (tickets, etc.)

#### Transaction Management
- **Path**: `/admin/transactions/`
- **Controller**: `Admin/TransactionController.php`
- **Template**: `admin/transactions/index.html.twig`
- **Features**:
  - List all transactions
  - View transaction details
  - Update transaction status
  - Process refunds
  - Generate transaction reports

## Page-by-Page Documentation

### Public Pages

#### Homepage (`/`)
- **Controller**: `HomeController::index()`
- **Template**: `home/index.html.twig`
- **Description**: Landing page with featured events, testimonials, and club information
- **Features**:
  - Hero section with call-to-action
  - Featured events slider
  - Customer testimonials
  - Club information and services
  - Newsletter signup

#### Feedback Page (`/feedback/`)
- **Controller**: `FeedbackController::index()`
- **Template**: `feedback/index.html.twig`
- **Description**: Displays customer feedback and allows submission of new feedback
- **Features**:
  - Featured feedback section
  - Event-specific feedback filtering
  - Average rating display
  - Responsive grid layout
  - Feedback submission form
  - Photo upload functionality

#### Ticket Purchase Page (`/tickets/`)
- **Controller**: `TicketController::index()`
- **Template**: `ticket/index.html.twig`
- **Description**: Lists available events with ticket purchasing options
- **Features**:
  - Event listing with details
  - Ticket type selection
  - Price information
  - Event date and time display
  - Purchase button linking to checkout

#### Support Ticket Page (`/support/`)
- **Controller**: `TicketController::support()`
- **Template**: `ticket/support.html.twig`
- **Description**: Interface for submitting support requests
- **Features**:
  - Support ticket form
  - Category selection
  - Priority selection
  - File attachment option
  - Submission confirmation

### Admin Pages

#### Admin Dashboard (`/admin/`)
- **Controller**: `Admin\DashboardController::index()`
- **Template**: `admin/dashboard/index.html.twig`
- **Description**: Overview of system statistics and recent activity
- **Features**:
  - Key metrics display
  - Recent feedback
  - Recent tickets
  - Recent transactions
  - Quick action buttons

#### User Management (`/admin/users/`)
- **Controller**: `Admin\UserController::index()`
- **Template**: `admin/users/index.html.twig`
- **Description**: Interface for managing system users
- **Features**:
  - User listing with pagination
  - Search and filter functionality
  - User creation form
  - Edit user details
  - Delete user option

#### Feedback Management (`/admin/feedback/`)
- **Controller**: `Admin\FeedbackController::index()`
- **Template**: `admin/feedback/index.html.twig`
- **Description**: Interface for managing customer feedback
- **Features**:
  - Feedback listing with pagination
  - Filter by event, rating, date
  - Create feedback entry
  - Edit feedback details
  - Delete feedback option

#### Ticket Management (`/admin/tickets/`)
- **Controller**: `Admin\TicketController::index()`
- **Template**: `admin/tickets/index.html.twig`
- **Description**: Interface for managing tickets (event and support)
- **Features**:
  - Ticket listing with pagination
  - Filter by type, status, priority
  - Create new ticket
  - Edit ticket details
  - Change ticket status
  - Delete ticket option

#### Transaction Management (`/admin/transactions/`)
- **Controller**: `Admin\TransactionController::index()`
- **Template**: `admin/transactions/index.html.twig`
- **Description**: Interface for managing financial transactions
- **Features**:
  - Transaction listing with pagination
  - Filter by status, date, amount
  - View transaction details
  - Update transaction status
  - Process refunds

## API Documentation

### Authentication

All API endpoints require authentication using an API key in the request header:

```
X-API-KEY: your_api_key_here
```

### Endpoints

#### User API

##### Create User
- **Method**: POST
- **URL**: `/api/users`
- **Parameters**:
  - `email` (required): User email address
  - `password` (required): User password
  - `firstName` (required): User's first name
  - `lastName` (required): User's last name
  - `roles` (optional): Array of roles
- **Response**:
  ```json
  {
    "id": 1,
    "email": "user@example.com",
    "firstName": "John",
    "lastName": "Doe"
  }
  ```

##### List Users
- **Method**: GET
- **URL**: `/api/users`
- **Parameters**:
  - `page` (optional): Page number for pagination
  - `limit` (optional): Results per page
- **Response**:
  ```json
  {
    "users": [
      {
        "id": 1,
        "email": "user@example.com",
        "firstName": "John",
        "lastName": "Doe"
      }
    ],
    "total": 1,
    "page": 1,
    "limit": 10
  }
  ```

#### Feedback API

##### Submit Feedback
- **Method**: POST
- **URL**: `/api/feedback`
- **Parameters**:
  - `rating` (required): Integer 1-5
  - `comment` (required): Feedback text
  - `eventName` (required): Event name
  - `photo` (optional): Base64 encoded image
  - `username` (optional): Display name
- **Response**:
  ```json
  {
    "id": 1,
    "rating": 5,
    "comment": "Great event!",
    "eventName": "Summer Sailing"
  }
  ```

##### List Feedback
- **Method**: GET
- **URL**: `/api/feedback`
- **Parameters**:
  - `eventName` (optional): Filter by event
  - `minRating` (optional): Minimum rating
  - `page` (optional): Page number
  - `limit` (optional): Results per page
- **Response**:
  ```json
  {
    "feedback": [
      {
        "id": 1,
        "rating": 5,
        "comment": "Great event!",
        "eventName": "Summer Sailing",
        "createdAt": "2023-06-01T12:00:00Z"
      }
    ],
    "total": 1,
    "page": 1,
    "limit": 10
  }
  ```

#### Ticket API

##### Create Ticket
- **Method**: POST
- **URL**: `/api/tickets`
- **Parameters**:
  - `isSupport` (required): Boolean
  - `title` (required): Ticket title
  - `description` (required): Ticket description
  - For event tickets:
    - `ticketType` (required): Ticket type
    - `price` (required): Ticket price
    - `eventName` (required): Event name
    - `eventDate` (required): Event date
  - For support tickets:
    - `category` (required): Issue category
    - `priority` (required): Issue priority
- **Response**:
  ```json
  {
    "id": 1,
    "title": "Summer Sailing Ticket",
    "ticketType": "VIP Experience",
    "price": 99.99,
    "eventName": "Summer Sailing",
    "eventDate": "2023-07-15T14:00:00Z",
    "status": "open"
  }
  ```

##### List Tickets
- **Method**: GET
- **URL**: `/api/tickets`
- **Parameters**:
  - `isSupport` (optional): Filter by ticket type
  - `status` (optional): Filter by status
  - `eventName` (optional): Filter by event
  - `page` (optional): Page number
  - `limit` (optional): Results per page
- **Response**:
  ```json
  {
    "tickets": [
      {
        "id": 1,
        "title": "Summer Sailing Ticket",
        "ticketType": "VIP Experience",
        "price": 99.99,
        "eventName": "Summer Sailing",
        "eventDate": "2023-07-15T14:00:00Z",
        "status": "open"
      }
    ],
    "total": 1,
    "page": 1,
    "limit": 10
  }
  ```

#### Transaction API

##### Process Payment
- **Method**: POST
- **URL**: `/api/payment/process`
- **Parameters**:
  - `amount` (required): Transaction amount
  - `paymentMethod` (required): Payment method
  - `ticketIds` (optional): Array of ticket IDs
- **Response**:
  ```json
  {
    "id": 1,
    "amount": 99.99,
    "status": "completed",
    "createdAt": "2023-06-01T12:00:00Z"
  }
  ```

##### List Transactions
- **Method**: GET
- **URL**: `/api/transactions`
- **Parameters**:
  - `status` (optional): Filter by status
  - `minAmount` (optional): Minimum amount
  - `maxAmount` (optional): Maximum amount
  - `page` (optional): Page number
  - `limit` (optional): Results per page
- **Response**:
  ```json
  {
    "transactions": [
      {
        "id": 1,
        "amount": 99.99,
        "status": "completed",
        "createdAt": "2023-06-01T12:00:00Z"
      }
    ],
    "total": 1,
    "page": 1,
    "limit": 10
  }
  ```

## Database Schema

### User Table
```sql
CREATE TABLE `user` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(180) NOT NULL,
    roles JSON NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    UNIQUE INDEX UNIQ_8D93D649E7927C74 (email)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
```

### Feedback Table
```sql
CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    rating INT NOT NULL,
    comment TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    username VARCHAR(255),
    photo_path VARCHAR(255),
    transaction_id INT,
    is_admin BOOLEAN,
    event_name VARCHAR(255),
    INDEX IDX_D2294458A76ED395 (user_id),
    INDEX IDX_D22944582FC0CB0F (transaction_id),
    CONSTRAINT FK_D2294458A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id),
    CONSTRAINT FK_D22944582FC0CB0F FOREIGN KEY (transaction_id) REFERENCES transaction (id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
```

### Ticket Table
```sql
CREATE TABLE ticket (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT,
    ticket_type VARCHAR(255),
    price DECIMAL(10,2),
    event_name VARCHAR(255),
    event_date DATETIME,
    created_at DATETIME NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(255),
    status VARCHAR(255),
    priority VARCHAR(255),
    created_by VARCHAR(255),
    is_support BOOLEAN,
    INDEX IDX_97A0ADA32FC0CB0F (transaction_id),
    CONSTRAINT FK_97A0ADA32FC0CB0F FOREIGN KEY (transaction_id) REFERENCES transaction (id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
```

### Transaction Table
```sql
CREATE TABLE transaction (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    payment_method VARCHAR(255),
    INDEX IDX_723705D1A76ED395 (user_id),
    CONSTRAINT FK_723705D1A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
```

## Setup & Installation

### Requirements
- PHP 8.1 or higher
- MySQL 8.0 or higher
- Composer
- Symfony CLI (optional, but recommended)
- Node.js and npm (for frontend assets)

### Step 1: Clone Repository
```bash
git clone [repository-url]
cd nautic-club
```

### Step 2: Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install frontend dependencies
npm install
npm run build
```

### Step 3: Configure Environment
```bash
# Create environment file
cp .env .env.local

# Edit .env.local with your database credentials
# Example:
# DATABASE_URL="mysql://username:password@127.0.0.1:3306/nautic_club?serverVersion=8.0&charset=utf8mb4"
```

### Step 4: Database Setup
```bash
# Create database
php bin/console doctrine:database:create

# Run migrations
php bin/console doctrine:migrations:migrate

# (Optional) Load fixtures
php bin/console doctrine:fixtures:load
```

### Step 5: Create Admin User
```bash
php bin/console app:create-admin admin@example.com password FirstName LastName
```

### Step 6: Start Development Server
```bash
# Using Symfony CLI (preferred)
symfony server:start

# Using PHP's built-in server
php -S 127.0.0.1:8000 -t public/
```

### Step 7: Access Application
- Frontend: http://localhost:8000
- Admin Panel: http://localhost:8000/admin (login with admin credentials)

## Developer Guidelines

### Coding Standards
- Follow PSR-12 coding standards
- Use PHP 8.1 features where appropriate
- Document all classes and methods with PHPDoc
- Use type hints for parameters and return types
- Keep classes focused on single responsibility

### Branch Strategy
- `main`: Production-ready code
- `develop`: Development branch
- Feature branches: `feature/feature-name`
- Bug fix branches: `fix/bug-description`

### Commit Messages
Follow conventional commits format:
```
<type>(<scope>): <description>

[optional body]

[optional footer]
```

Examples:
- `feat(tickets): add VIP ticket pricing`
- `fix(feedback): resolve rating validation error`
- `docs(readme): update installation instructions`

### Pull Request Process
1. Ensure code passes all tests
2. Update documentation as needed
3. Request review from at least one team member
4. Address review comments
5. Merge only when approved

## Command Reference

### User Management
```bash
# Create admin user
php bin/console app:create-admin email password firstName lastName

# Reset user password
php bin/console app:reset-password email newPassword
```

### Fixture Generation
```bash
# Generate feedback placeholders
php bin/console app:add-feedback-placeholders [count]

# Generate event tickets
php bin/console app:generate-event-tickets [eventName] [count]
```

### System Maintenance
```bash
# Clear cache
php bin/console cache:clear

# Warm up cache
php bin/console cache:warmup

# Database migrations
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

## Security Implementation

### Authentication
- Symfony Security component for user authentication
- Password hashing using bcrypt
- Remember me functionality with secure cookies
- Login attempt rate limiting

### Authorization
- Role-based access control (RBAC)
- Route protection with security annotations
- Voter-based permission checks for complex rules

### CSRF Protection
- Automatic CSRF token generation
- Token validation on form submission
- Protection for all state-changing operations

### Secure File Upload
- File type validation
- Size restrictions
- Sanitization of filenames
- Storage in non-public directory

### Input Validation
- Form type constraints
- Server-side validation
- Sanitization of user input
- Prepared statements for database queries

## Performance Considerations

### Caching
- Twig template caching
- Doctrine query result caching
- HTTP caching for static assets
- Redis cache for session storage

### Database Optimization
- Indexed fields for common queries
- Optimized entity repositories
- Doctrine query optimization
- Pagination for large result sets

### Frontend Performance
- Minified CSS and JavaScript
- Optimized image loading
- Lazy loading of content
- Responsive design for all devices

## Testing

### Unit Testing
```bash
# Run all unit tests
php bin/phpunit

# Run specific test suite
php bin/phpunit --testsuite=Unit

# Run specific test class
php bin/phpunit tests/Unit/Entity/UserTest.php
```

### Functional Testing
```bash
# Run all functional tests
php bin/phpunit --testsuite=Functional

# Run specific controller test
php bin/phpunit tests/Functional/Controller/FeedbackControllerTest.php
```

## Deployment

### Production Environment Setup
1. Set up web server (Apache/Nginx)
2. Configure PHP-FPM
3. Set up MySQL database
4. Deploy application code
5. Run migrations and cache warming

### Environment Configuration
```bash
# Configure production environment
APP_ENV=prod
APP_SECRET=your_app_secret
DATABASE_URL="mysql://production_user:password@localhost:3306/nautic_club"
```

### Deployment Process
1. Build assets for production
   ```bash
   npm run build
   ```
2. Clear and warm up cache
   ```bash
   APP_ENV=prod php bin/console cache:clear
   APP_ENV=prod php bin/console cache:warmup
   ```
3. Run database migrations
   ```bash
   APP_ENV=prod php bin/console doctrine:migrations:migrate --no-interaction
   ```

## Support

For technical support, please contact [Your Contact Information].

For bug reports, feature requests, or contributions, please use the project's issue tracker or submit a pull request.

Documentation last updated: [Current Date] 