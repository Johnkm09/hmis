# 🏨 HotelHub API

Modern hotel management REST API built with Laravel.

## ✨ Features

- Sanctum Authentication
- Role & Permission Management
- Room & Booking Management
- M-Pesa STK Push Integration
- Reports & Analytics
- AI-powered Booking Assistant
- RESTful API Architecture
- Swagger API Documentation

---

## ⚙️ Tech Stack

- Laravel
- MYSQL
- Sanctum
- Spatie Permission
- Safaricom Daraja API
- OpenAI API

---

## 🚀 Installation

```bash
cd hotelhub-api

composer install

cp .env.example .env

php artisan key:generate

php artisan migrate --seed

php artisan serve

## 📌 API Modules

- Authentication
- Rooms
- Bookings
- Customers
- Payments
- Reports
- AI Assistant

---

## 🔥 Sample Endpoints

```http
POST /api/login
GET  /api/rooms
POST /api/bookings
POST /api/payments/mpesa/stk-push
GET  /api/reports/revenue
```

---

## 🧪 Testing

```bash
php artisan test
```

---

## 📄 License

MIT License
