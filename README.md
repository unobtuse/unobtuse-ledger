<div align="center">

![Unobtuse Ledger](logos/unobtuse-ledger-white-logo.svg)

# Unobtuse Ledger

**AI-Powered Personal Finance Management for the Modern User**

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Livewire](https://img.shields.io/badge/Livewire-3.6+-FB70A9?style=for-the-badge&logo=livewire&logoColor=white)](https://livewire.laravel.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-4.1+-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

[Features](#-features) • [Quick Start](#-quick-start) • [Documentation](#-documentation) • [Roadmap](#-roadmap) • [Contributing](#-contributing)

</div>

---

## 🎯 About

**Unobtuse Ledger** is a mobile-first personal finance SaaS application that helps users manage bill payments aligned with their pay schedule. Never miss another payment, discover forgotten subscriptions, and gain AI-powered insights into your spending habits.

### The Problem We Solve

- 💸 **Late payments** costing users $30-100+ per missed bill
- 📉 **Poor credit scores** from forgotten due dates (30% payment history impact)
- 🔄 **Wasted subscriptions** averaging $2,000+/year per person
- 😰 **Financial stress** affecting 80% of Americans
- 📱 **Lack of mobile-first** solutions with real automation

### Our Solution

✅ **Automatic bill tracking** - No manual entry required  
✅ **Pay-schedule awareness** - Bills organized by your paycheck dates  
✅ **AI-powered insights** - Receipt scanning, subscription detection, spending optimization  
✅ **Mobile-first design** - Built for on-the-go management  
✅ **Transparent pricing** - Clear value at every tier  

---

## ✨ Features

### Phase 1: MVP (Current Development)

- 🔐 **Secure Authentication**
  - Google OAuth integration
  - Two-Factor Authentication (TOTP)
  - Session management with auto-refresh

- 🏦 **Bank Account Linking**
  - Plaid integration for automatic transaction sync
  - Real-time balance updates
  - Support for checking, savings, and credit cards

- 📅 **Smart Bill Tracking**
  - Visual calendar of upcoming bills
  - Payment priority indicators
  - Bills organized by pay schedule
  - Direct payment links

- 💰 **Intelligent Budgeting**
  - Automatic rent allocation (25% per paycheck)
  - Remaining budget after obligations
  - Spending recommendations
  - Transaction categorization

- 📱 **Mobile-Responsive Dashboard**
  - Beautiful, modern UI with Tailwind CSS
  - Flowbite components for consistency
  - Dark mode support
  - Optimized for all screen sizes

### Phase 2: AI Features (Coming Soon)

- 📸 **Receipt Scanning** - AI-powered OCR with 95%+ accuracy
- 🔍 **Subscription Detection** - Automatically identify recurring charges
- 📊 **Spending Insights** - Personalized recommendations and trend analysis
- ⚠️ **Anomaly Detection** - Get alerts for unusual spending patterns

### Phase 3+: Future Enhancements

- 📲 Native iOS & Android apps
- 🤝 Bill negotiation partnerships
- 📈 Investment tracking
- 💎 Net worth aggregation
- 👨‍👩‍👧‍👦 Family accounts
- 🌍 International expansion

---

## 🚀 Quick Start

### Prerequisites

- PHP 8.3+
- Composer
- Node.js 18+ and NPM
- PostgreSQL (via Supabase)
- Redis

### Installation

```bash
# Clone the repository
git clone https://github.com/yourusername/unobtuse-ledger.git
cd unobtuse-ledger

# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your .env file with:
# - Database credentials (Supabase PostgreSQL)
# - Google OAuth credentials
# - Plaid API keys
# - Redis connection

# Run database migrations
php artisan migrate

# Build frontend assets
npm run build

# Start the development server
composer dev
```

The `composer dev` command runs:
- Laravel development server (port 8000)
- Queue worker for background jobs
- Log viewer (Laravel Pail)
- Vite dev server for hot module replacement

---

## 🛠 Technology Stack

### Backend

| Technology | Purpose |
|------------|---------|
| **Laravel 12** | Modern PHP framework with elegant syntax |
| **PHP 8.3+** | Latest PHP features (typed properties, readonly, match expressions) |
| **Livewire 3.6** | Real-time reactive components without JavaScript |
| **PostgreSQL** | Powerful relational database via Supabase |
| **Redis** | Caching, session storage, and queue management |
| **Laravel Socialite** | OAuth authentication (Google) |
| **Laravel Fortify** | Two-factor authentication (TOTP) |

### Frontend

| Technology | Purpose |
|------------|---------|
| **Tailwind CSS 4.1** | Utility-first CSS framework |
| **Flowbite** | Professional UI components |
| **Alpine.js 3.15** | Lightweight JavaScript framework |
| **Vite** | Lightning-fast build tool |

### External Services

| Service | Purpose |
|---------|---------|
| **Plaid** | Bank account linking and transaction sync |
| **Google Cloud Vision** | Receipt OCR scanning (Phase 2) |
| **OpenAI API** | AI insights and categorization (Phase 2) |
| **SendGrid** | Transactional emails |
| **Sentry** | Error tracking and monitoring |

---

## 📚 Documentation

Comprehensive documentation is available in the `/docs` directory:

- **[Executive Summary](EXECUTIVE_SUMMARY.md)** - Project overview, market analysis, and business strategy
- **[Development Plan](PROJECT_DEVELOPMENT_PLAN.md)** - Detailed technical roadmap and architecture
- **[Quick Start Guide](QUICK_START.md)** - Get up and running quickly
- **[Server Setup](SERVER_SETUP.md)** - Production deployment instructions
- **[Design System](design-system.md)** - UI/UX guidelines and component library

### API Documentation

API endpoints are documented using standard RESTful conventions:

```
POST   /api/auth/login              - User authentication
POST   /api/auth/verify-2fa         - Verify two-factor code
GET    /api/accounts                - List linked accounts
POST   /api/accounts/plaid-token    - Get Plaid link token
POST   /api/accounts                - Link new account
GET    /api/bills                   - List bills
GET    /api/transactions            - List transactions
POST   /api/receipts                - Upload receipt (Phase 2)
GET    /api/insights                - Get AI insights (Phase 2)
```

Full API documentation will be available via Postman collection and Swagger/OpenAPI spec.

---

## 🗺 Roadmap

### Q1 2025 - MVP Launch
- ✅ Project initialization and setup
- ✅ Authentication system (Google OAuth + 2FA)
- 🔄 Bank account linking (Plaid integration)
- 🔄 Bill tracking dashboard
- 🔄 Budget calculations
- 📅 Beta launch (100-500 users)

### Q2 2025 - AI Features
- 📋 Receipt scanning with OCR
- 📋 Subscription detection
- 📋 AI-powered spending insights
- 📋 Premium tier launch

### Q3 2025 - Mobile Apps
- 📋 React Native app (iOS & Android)
- 📋 Push notifications
- 📋 Biometric authentication
- 📋 App Store deployment

### Q4 2025 - Scale & Polish
- 📋 Bill negotiation features
- 📋 Investment tracking
- 📋 Admin panel
- 📋 Performance optimization

**Legend:** ✅ Complete | 🔄 In Progress | 📋 Planned

---

## 🏗 Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    CLIENT LAYER                          │
│  Web SPA (Livewire/Alpine)  │  Mobile Apps (React Native)│
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│              LARAVEL API LAYER                           │
│  Authentication  │  Controllers  │  Middleware  │  Routes│
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│           BUSINESS LOGIC LAYER                           │
│  UserService  │  BankingService  │  BudgetService        │
│  AIService    │  NotificationService                     │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│        DATA & INTEGRATION LAYER                          │
│  PostgreSQL  │  Redis  │  Plaid  │  Google Vision  │  AI │
└─────────────────────────────────────────────────────────┘
```

---

## 🔐 Security

Security is our top priority. We implement:

- 🔒 **OAuth 2.0** for secure third-party authentication
- 🔑 **Two-Factor Authentication** (TOTP) for sensitive operations
- 🔐 **AES-256 encryption** for data at rest and TLS 1.3 in transit
- 🛡️ **PCI DSS compliance** for payment data handling
- 🚨 **Rate limiting** to prevent abuse
- 📝 **Comprehensive audit logs** for all financial operations
- 🔍 **Regular security audits** and penetration testing

**Security Disclosure:** If you discover a security vulnerability, please email security@unobtuse.com instead of using the issue tracker.

---

## 🧪 Testing

We maintain high code quality through comprehensive testing:

```bash
# Run all tests
composer test

# Run specific test suite
php artisan test --testsuite=Feature

# Run tests with coverage
php artisan test --coverage

# Run code style checks
composer pint
```

**Testing Strategy:**
- Unit tests for business logic (80%+ coverage target)
- Feature tests for API endpoints
- Integration tests for external services (Plaid, AI)
- E2E tests for critical user flows

---

## 📊 Development Metrics

### Project Status

| Metric | Target | Current |
|--------|--------|---------|
| **Code Coverage** | 80%+ | 🔄 In Progress |
| **API Response Time (p95)** | <200ms | 🔄 In Progress |
| **Uptime** | 99.9%+ | 🔄 In Progress |
| **Test Pass Rate** | 100% | 🔄 In Progress |

### Business Goals (Year 1)

| Metric | Target |
|--------|--------|
| **Beta Users** | 500+ |
| **Monthly Active Users** | 10,000+ |
| **Free-to-Paid Conversion** | 5-7% |
| **Customer Satisfaction (NPS)** | 50+ |
| **App Store Rating** | 4.5+ ⭐ |

---

## 👥 Contributing

We welcome contributions from the community! Here's how you can help:

1. **Fork the repository**
2. **Create a feature branch** (`git checkout -b feature/amazing-feature`)
3. **Commit your changes** (`git commit -m 'feat: add amazing feature'`)
4. **Push to the branch** (`git push origin feature/amazing-feature`)
5. **Open a Pull Request**

### Contribution Guidelines

- Follow PSR-12 coding standards
- Write comprehensive tests for new features
- Update documentation as needed
- Use conventional commit messages (`feat:`, `fix:`, `docs:`, etc.)
- Ensure all tests pass before submitting PR

See [CONTRIBUTING.md](CONTRIBUTING.md) for detailed guidelines.

---

## 📜 License

This project is licensed under the **MIT License** - see the [LICENSE](LICENSE) file for details.

---

## 🤝 Support & Community

- 💬 **Discord:** [Join our community](https://discord.gg/unobtuse)
- 🐦 **Twitter:** [@UnobtuseLedger](https://twitter.com/unobtuseLedger)
- 📧 **Email:** support@unobtuse.com
- 📖 **Documentation:** [docs.unobtuse.com](https://docs.unobtuse.com)
- 🐛 **Bug Reports:** [GitHub Issues](https://github.com/yourusername/unobtuse-ledger/issues)

---

## 🙏 Acknowledgments

### Built With

- [Laravel](https://laravel.com) - The PHP framework for web artisans
- [Livewire](https://livewire.laravel.com) - A full-stack framework for Laravel
- [Tailwind CSS](https://tailwindcss.com) - A utility-first CSS framework
- [Flowbite](https://flowbite.com) - UI component library
- [Plaid](https://plaid.com) - Financial data API
- [Supabase](https://supabase.com) - Open source Firebase alternative

### Developed By

<div align="center">

![GabeMade.it](logos/gabemadeit-white-logo.svg)

**Built with ❤️ by [GabeMade.it](https://gabemade.it)**

</div>

---

## 📸 Screenshots

> Coming soon! Screenshots will be added once the MVP dashboard is complete.

---

## ⚡ Performance

We're committed to delivering a fast, responsive experience:

- **Web App:** <2s page load time
- **API:** <200ms response time (p95)
- **Mobile App:** <3s launch time
- **Database Queries:** <100ms (p95)
- **99.9%+ uptime** guaranteed

---

## 🌟 Star History

If you find Unobtuse Ledger helpful, please consider giving us a star! ⭐

[![Star History Chart](https://api.star-history.com/svg?repos=yourusername/unobtuse-ledger&type=Date)](https://star-history.com/#yourusername/unobtuse-ledger&Date)

---

<div align="center">

**Made with ❤️ for people who deserve financial peace of mind**

[Website](https://unobtuse.com) • [Documentation](https://docs.unobtuse.com) • [Twitter](https://twitter.com/UnobtuseLedger)

© 2025 Unobtuse Ledger. All rights reserved.

</div>

---

