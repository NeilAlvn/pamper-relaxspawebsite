# 🌸 Pamper & Relax Spa Website

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)

A modern, full-featured wellness spa website that provides clients with a seamless digital experience for exploring services, browsing products, and booking appointments online. Built with PHP and designed to reflect comfort, care, and relaxation.

## ✨ Features

### 🛍️ Client-Facing Features
- **Service Catalog** - Browse and explore spa treatments and wellness services
- **Product Shop** - View and purchase spa products with detailed descriptions
- **Online Booking System** - Schedule appointments with real-time availability checking
- **Shopping Cart & Checkout** - Secure product purchasing with order management
- **Transaction History** - Track bookings and purchase history
- **Responsive Design** - Optimized experience across all devices

### 🔧 Admin Features
- **Dashboard** - Comprehensive overview of business operations
- **Service Management** - Add, edit, and manage spa services
- **Product Management** - Full CRUD operations for products
- **Booking Management** - View and manage customer appointments
- **Sales Analytics** - Track revenue from services and products
- **Content Management** - Update "About Us" and other site content

## 🚀 Technologies Used

- **Backend**: PHP
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Dependency Management**: Composer
- **Libraries**: Various PHP libraries for enhanced functionality

## 📋 Prerequisites

Before you begin, ensure you have the following installed:
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (for dependency management)

## 🔧 Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/NeilAlvn/pamper-relaxspawebsite.git
   cd pamper-relaxspawebsite
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure database**
   - Create a new MySQL database
   - Import the SQL file from the `Database` folder
   - Update database credentials in `db_connection.php`:
     ```php
     $host = 'localhost';
     $dbname = 'your_database_name';
     $username = 'your_username';
     $password = 'your_password';
     ```

4. **Set up file permissions**
   ```bash
   chmod 755 uploads/
   chmod 644 uploads/*
   ```

5. **Start your web server**
   - For Apache: Place files in `htdocs` or `www` directory
   - For development: `php -S localhost:8000`

6. **Access the application**
   - Navigate to `http://localhost/pamper-relaxspawebsite` or `http://localhost:8000`

## 📁 Project Structure

```
pamper-relaxspawebsite/
├── Database/              # SQL database files
├── assets/                # Images, CSS, JS files
├── uploads/               # User-uploaded content
├── vendor/                # Composer dependencies
├── index.php              # Homepage
├── about_us.php           # About page
├── services.php           # Services listing
├── products.php           # Products catalog
├── appointments.php       # Booking page
├── checkout.php           # Shopping cart checkout
├── db_connection.php      # Database configuration
├── process_order.php      # Order processing logic
├── process_appointment.php # Booking processing
├── *_dashboard.php        # Admin dashboard pages
└── README.md              # Project documentation
```

## 🎯 Key Functionalities

### Booking System
- Real-time availability checking
- Service duration calculation
- Conflict prevention for overlapping appointments
- Day-wise booking management

### E-commerce Features
- Product browsing with categories
- Shopping cart functionality
- Secure checkout process
- Order tracking and management

### Admin Panel
- Sales reporting for services and products
- CRUD operations for all content
- Transaction monitoring
- Customer data management

## 🔒 Security Features

- Input validation and sanitization
- Prepared SQL statements to prevent injection
- Secure session management
- Error logging system

## 📱 Pages Overview

| Page | Description |
|------|-------------|
| `index.php` | Landing page with featured services/products |
| `services.php` | Complete service catalog |
| `products.php` | Product listing and shop |
| `appointments.php` | Service booking interface |
| `checkout.php` | Cart and payment processing |
| `transactions.php` | User order history |
| `products_dashboard.php` | Admin product management |
| `sales.php` | Sales analytics dashboard |

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is open source and available for educational and commercial use.

## 👨‍💻 Developer

**Neil Alvin Medallon**
- GitHub: [@NeilAlvn](https://github.com/NeilAlvn)
- LinkedIn: [Neil Alvin Medallon](https://www.linkedin.com/in/neil-alvin-medallon-456931336/)
- Location: Calamba, Laguna, Philippines

IT Specialist and AI Engineer with expertise in networking (MikroTik, routers, firewalls), Python, Java, SQL, and machine learning.

## 📞 Support

For support, please open an issue in the GitHub repository or contact through LinkedIn.

## 🙏 Acknowledgments

- Inspired by modern wellness and spa industry needs
- Built to provide seamless user experience for both clients and administrators
- Designed with scalability and maintainability in mind

---

⭐ If you find this project useful, please consider giving it a star!
