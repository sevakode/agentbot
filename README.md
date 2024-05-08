# AgentHub Telegram Bot Integration

This project integrates Telegram bots with the AgentHub API using the Laravel framework and the BotMan library. It allows for the creation and management of bots through Telegram chat commands, and enables dynamic bot interactions with users.

## Features

- Create and manage bots via Telegram chat commands
- Dynamically create bots with unique tokens
- Integrate with the AgentHub API for user and chat creation
- Handle incoming messages from users and send responses via the AgentHub API
- Middleware for bot and user authentication
- Use migrations to create necessary database tables

## Requirements

- PHP >= 8.2
- Laravel >= 11.0
- BotMan >= 2.8
- SQLite database

## Installation

1. Clone the repository:
   ```
   git clone https://github.com/sevakode/agentbot.git
   ```

2. Install dependencies via Composer:
   ```
   composer install
   ```

3. Copy the `.env.example` file to `.env` and configure the necessary settings, such as database connection and AgentHub API credentials.

4. Run the database migrations to create the required tables:
   ```
   php artisan migrate
   ```

5. Start the development server:
   ```
   php artisan serve
   ```

## Usage

1. Create a Telegram bot using BotFather and obtain the bot token.

2. Send the `create_bot` command to the main bot in the Telegram chat and follow the instructions to create a new bot.

3. The bot will be dynamically created with a unique token and ready for use.

4. Users can interact with the bot by sending messages in the chat.

5. The bot will handle incoming messages, create users and chats in the AgentHub API, and send responses back to the user.

## Configuration

- Configure the database connection in the `.env` file.

- Set the AgentHub API credentials (`AGENTHUB_LOGIN` and `AGENTHUB_PASSWORD`) in the `.env` file.

- Customize the routes for handling incoming bot messages in the `routes/web.php` file.

## Models

- `Bot`: Represents a bot entity with attributes such as token, group ID, authentication code, and user ID.

- `User`: Represents a user entity with attributes such as username, chat ID, messenger ID, driver, email, and password.

## Controllers

- `BotManController`: Handles the main bot interactions, including creating bots, handling dynamic bot messages, and sending responses.

- `AgentBotController`: Handles agent bot interactions, including processing commands and messages, and sending dialogs to a specified URL.

## Middleware

- `BotAuthenticationMiddleware`: Handles bot and user authentication, creating users in the AgentHub API if necessary.

## Services

- `AgentHubApiService`: Provides methods for interacting with the AgentHub API, including user and chat creation, sending messages, and token management.

## Dependencies

- Laravel Framework
- BotMan Library
- AgentHub API

