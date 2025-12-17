# LibraryManagement

LibraryManagement is a web-based application for managing personal book collections, built with **PHP** and **MariaDB**, and enhanced with an **AI-powered assistant** for intelligent queries, analytics, and insights.

## Live Demo
- Production (AI disabled): https://libmanagement.infinityfree.me

> Note: The AI Assistant works **only in a local environment**, as it relies on **free, locally hosted LLMs (Ollama)**.  
> AI functionality is not available in the production deployment.

## Features
- Role-based access control (User / Admin)
- Book management (add, edit, delete)
- Reading status tracking:
  - Planned
  - Reading
  - Completed
- Filtering and organization:
  - By genre
  - By author
- Library insights:
  - Reading habits analysis
  - Top genres
  - Majority reading status
- AI-powered assistant:
  - Natural language queries
  - Summaries and statistics
  - Role-aware responses

## AI Assistant
- LLM-first architecture using **Ollama**
- Supports:
  - Analytics queries (`how many`, `count`)
  - Listings (e.g. list all books by author)
  - Summaries and insights
- Role-aware SQL generation (User / Admin)
- Secure and validated queries
- RAG (Retrieval-Augmented Generation) using embeddings

Models used:
- `gemma3` – language model
- `embeddinggemma` – semantic search embeddings

## Docker
The project is fully Dockerized for consistent and reproducible environments.
- docker compose down -v
- docker compose up -d --build

## Testing
To run tests locally:
- vendor/bin/phpunit

Test coverage includes:
- Unit tests
- Integration tests
- Role and permission validation
- SQL safety checks
- Error handling and debug logging

## Tech Stack

**Backend**
- PHP
- MariaDB

**Frontend**
- HTML
- CSS
- JavaScript (Bootstrap)

**AI & Infrastructure**
- Ollama (local LLM runtime)
- Docker
- Docker Compose

## Deployment
- Hosted on **InfinityFree**
- Production environment runs **without AI Assistant**
- AI functionality is available **only in local development**

## Author
**Donart Ajvazi**
