# Movie Booking Backend

## Table of Contents
- [Description](#description)
- [Technologies Used](#technologies-used)
- [Main Features](#main-features)
- [Project Setup](#project-setup)
- [Support](#support)

## Description
The Movie Booking Backend project is a powerful web application that allows users to conveniently book movie tickets online. The application provides essential features for both moviegoers and administrators, ensuring a smooth and secure movie ticket booking experience.

## Technologies Used
The Movie Booking Backend is built with the following technologies and tools:

### Programming Language:
- **PHP:** The main language used for developing the application.

### Framework:
- **Laravel:** A powerful PHP framework that helps build web applications quickly and efficiently.

### Database:
- **MySQL or MariaDB:** The database management system used to store information about movies, tickets, and users.

### Package Management:
- **Composer:** The package manager for PHP, used to install and manage third-party libraries.

### API:
- **RESTful API:** The project uses REST architecture to create endpoints for interaction between the frontend and backend.

### Authentication:
- **JWT (JSON Web Token):** Used for user authentication and securing API endpoints.

## Main Features

**Movie Management:**
  - Easily add, edit, or delete movie information.
  - Provide full details about movies: title, description, showtimes, genres, offering a rich discovery experience.

**Online Ticket Booking:**
  - Select movies, showtimes, and number of tickets with just a few clicks.
  - Intuitive seat selection with real-time seat availability checking.

**Showtime Tracking:**
  - View detailed showtimes by date and time, continuously updated.
  - Easily keep track of upcoming shows for movie planning.

**User Authentication:**
  - Fast and secure registration and login.
  - Uses JWT (JSON Web Token) to secure API endpoints and ensure strict access control.

**Email Notifications:**
  - Automatically send booking confirmation emails with full details: movie, time, seat.
  - Provides convenience and professionalism for users after each transaction.

**Ticket Management:**
  - Easily track and manage booking history.
  - Flexible ticket cancellation support where applicable.

**Payment via PayOS:**
  - Integrated secure and fast online payment through PayOS.
  - Transparent transaction information and instant confirmation after successful payment.

**Automatic Payment:**
  - Automatic payment feature via PayOS eliminates manual confirmation steps.
  - Seamless transaction processing with real-time ticket status updates upon payment completion.

**Powerful RESTful API:**
  - Provides flexible RESTful endpoints, easily integrated with frontend or mobile applications.
  - Ensures high performance and scalability.

**Optimal Security:**
   - Implements advanced security measures to protect user data and transaction information.
   - Strict authentication and authorization system for absolute safety.

## Project Setup

#### Step 1: Clone the repository
Open your terminal and run the following command to clone the project:
```bash
git clone https://github.com/Duy0605/movie_booking_be.git
```

#### Step 2: Navigate to the project directory
```bash
cd movie_booking_be
```

#### Step 3: Install dependencies
Run the following command to install all necessary dependencies:
```bash
composer install
```

#### Step 4: Create configuration file
Copy the .env.example file to a new .env file:
```bash
cp .env.example .env
```

#### Step 5: Configure the database
Open the .env file and configure your database connection information:
```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

#### Step 6: Configure Mailer
In the .env file, also configure your email sending information. Example configuration for SMTP:
```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=your_email@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

#### Step 7: Configure PayOS
Add PayOS configuration information to the .env file:
```bash
PAYOS_MERCHANT_ID=your_merchant_id
PAYOS_SECRET_KEY=your_secret_key
PAYOS_API_URL=https://api.payos.vn/v1/transaction
```
Replace your_merchant_id and your_secret_key with your actual PayOS account information.

#### Step 8: Run migrations
Run the following command to create the database tables:
```bash
php artisan migrate
```

#### Step 9: Start the server
Finally, start the server with the command:
```bash
php artisan serve
```

## Support

For support, please send an email to manhduc889@gmail.com.
