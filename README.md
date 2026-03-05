# RealState Admin - Backend for Vapi Integration

This is a Symfony-based real estate management backend designed to provide apartment data to [Vapi](https://vapi.ai) (a Voice AI platform). It allows Vapi agents to query available properties and present them to users during voice calls.

## Features

- **Apartment Management**: Simple entity to track property name, address, price, and availability.
- **Vapi API Endpoints**:
    - `GET /api/vapi/apartments`: Returns a list of all currently available apartments.
    - `POST /api/vapi/webhook`: Handles Vapi function calls (tool use) for the `getAvailableApartments` tool.
- **Data Seeding**: A custom command to quickly populate the database with sample properties.
- **Docker Ready**: Pre-configured with a PostgreSQL database via Docker Compose.

## Tech Stack

- **PHP**: 8.2+
- **Framework**: Symfony 7.4
- **Database**: PostgreSQL 16
- **ORM**: Doctrine
- **Infrastructure**: Docker Compose

## Requirements

- [PHP 8.2+](https://www.php.net/downloads)
- [Composer](https://getcomposer.org/download/)
- [Docker](https://www.docker.com/products/docker-desktop) and Docker Compose
- [Symfony CLI](https://symfony.com/download) (recommended for local development)

## Installation & Setup

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd realstate-admin
   ```

2. **Install dependencies**:
   ```bash
   composer install
   ```

3. **Configure Environment Variables**:
   Copy `.env` to `.env.local` and adjust your database connection if necessary.
   ```bash
   cp .env .env.local
   ```

4. **Start the Database**:
   ```bash
   docker compose up -d
   ```

5. **Create the Database and Schema**:
   ```bash
   php bin/console doctrine:database:create --if-not-exists
   php bin/console doctrine:schema:create
   ```

6. **Seed the Database**:
   Populate the database with sample apartments:
   ```bash
   php bin/console app:seed-apartments
   ```

7. **Start the Web Server**:
   If using the Symfony CLI:
   ```bash
   symfony serve -d
   ```
   Or use the built-in PHP server:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

## API Endpoints

### Get Available Apartments
- **URL**: `/api/vapi/apartments`
- **Method**: `GET`
- **Response**:
  ```json
  {
      "status": "success",
      "data": [
          {
              "id": 1,
              "name": "Piso Centro",
              "address": "Calle Mayor 1",
              "price": 1200
          }
      ],
      "message": "Estos son los pisos disponibles."
  }
  ```

### Vapi Webhook (Function Call)
- **URL**: `/api/vapi/webhook`
- **Method**: `POST`
- **Description**: This endpoint is designed to be used as a Vapi tool. It listens for `function-call` messages where the tool name is `getAvailableApartments`.

## Vapi Configuration

To integrate this backend with Vapi, you can define a tool in your Vapi dashboard:

- **Name**: `getAvailableApartments`
- **Type**: `function`
- **Server URL**: `https://your-public-domain.com/api/vapi/webhook`

When the user asks for available apartments, Vapi will call this endpoint, and the AI will receive the data to read it back to the user.

## Console Commands

- `php bin/console app:seed-apartments`: Populates the database with example data.
- `php bin/console doctrine:schema:update --force`: Updates the database schema manually (use migrations for production).
